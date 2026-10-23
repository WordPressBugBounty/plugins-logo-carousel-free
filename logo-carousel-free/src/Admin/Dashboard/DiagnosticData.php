<?php
/**
 * Opt-in diagnostic data.
 *
 * Sends ShapedPlugin a small, non-sensitive snapshot of the site (versions,
 * theme, active plugins, which Logo Carousel blocks are in use) when the
 * admin has said yes — in the setup wizard, the consent notice or Settings →
 * Advanced → Contribute to Logo Carousel — once right after they agree, then
 * weekly. Same payload as Logo Carousel Pro, to Free's
 * own collector route.
 *
 * Never sends without a stored `diagnostic_data_consent` of true, and never
 * from a local or private-network site.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/admin/dashboard
 * @author     ShapedPlugin<support@shapedplugin.com>
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\Dashboard;

use ShapedPlugin\LogoCarouselFree\Admin\AdminNotices\DiagnosticConsentNotice;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Admin\Dashboard\DiagnosticData' ) ) {

	/**
	 * Collects and sends the opt-in diagnostic snapshot.
	 */
	class DiagnosticData {

		/**
		 * Collector endpoint. readme.txt discloses this exact URL; WordPress.org
		 * requires the two to match.
		 *
		 * @var string
		 */
		const ENDPOINT = 'https://api.shapedplugin.com/wp-json/spda/v1/logo-carousel-free-collect';

		/**
		 * Plugin settings option that holds the consent answer.
		 *
		 * @var string
		 */
		const OPTION = '_sp_lcpro_options';

		/**
		 * Weekly send. Free has no licence re-check to ride on, so it owns
		 * this event and keeps it scheduled only while consent stands.
		 *
		 * @var string
		 */
		const WEEKLY_HOOK = 'logo_carousel_free_weekly_scheduled_events';

		/**
		 * One-off event queued when the admin agrees, so the wizard's save
		 * request never waits on the site scan or the remote call.
		 *
		 * @var string
		 */
		const SEND_NOW_HOOK = 'lcfree_send_diagnostic_data';

		/**
		 * Hook the weekly and one-off sends. Runs on every request, since
		 * WP-Cron fires outside wp-admin.
		 *
		 * @return void
		 */
		public static function register_hooks() {
			add_action( self::WEEKLY_HOOK, array( __CLASS__, 'maybe_send' ) );
			add_action( self::SEND_NOW_HOOK, array( __CLASS__, 'maybe_send' ) );

			// Picks up consent stored before this class existed (or left by
			// Pro, which shares the option) and drops the event once withdrawn.
			add_action( 'admin_init', array( __CLASS__, 'sync_schedule' ) );

			register_deactivation_hook( SP_LOGO_CAROUSEL_FREE_FILE, array( __CLASS__, 'clear_schedule' ) );
		}

		/**
		 * Queue a send for the next cron run and make sure the weekly one is
		 * scheduled. A no-op while a send is pending, so re-running the wizard
		 * does not stack requests.
		 *
		 * @return void
		 */
		public static function queue_send() {
			if ( ! wp_next_scheduled( self::SEND_NOW_HOOK ) ) {
				wp_schedule_single_event( time(), self::SEND_NOW_HOOK );
			}

			self::sync_schedule();
		}

		/**
		 * Keep the weekly event scheduled exactly while consent stands.
		 *
		 * @return void
		 */
		public static function sync_schedule() {
			$scheduled = wp_next_scheduled( self::WEEKLY_HOOK );

			if ( self::has_consent( get_option( self::OPTION, array() ) ) ) {
				if ( ! $scheduled ) {
					wp_schedule_event( time() + WEEK_IN_SECONDS, 'weekly', self::WEEKLY_HOOK );
				}
			} elseif ( $scheduled ) {
				wp_clear_scheduled_hook( self::WEEKLY_HOOK );
			}
		}

		/**
		 * Drop both events. Runs on deactivation.
		 *
		 * @return void
		 */
		public static function clear_schedule() {
			wp_clear_scheduled_hook( self::WEEKLY_HOOK );
			wp_clear_scheduled_hook( self::SEND_NOW_HOOK );
		}

		/**
		 * Send the snapshot when the admin has agreed and the site is public.
		 *
		 * @return void
		 */
		public static function maybe_send() {
			if ( ! self::has_consent( get_option( self::OPTION, array() ) ) ) {
				return;
			}

			if ( self::is_local_host( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
				return;
			}

			wp_remote_post(
				self::ENDPOINT,
				array(
					'timeout'  => 15,
					'blocking' => false,
					'headers'  => array(
						'Content-Type' => 'application/json',
						'x-api-key'    => SP_LOGO_CAROUSEL_FREE_SDR_KEY,
						'User-Agent'   => 'logo-carousel-free-collect/' . home_url(),
					),
					'body'     => wp_json_encode( self::payload() ),
				)
			);
		}

		/**
		 * Record the admin's sharing choice.
		 *
		 * The one write path for the setup wizard, the consent notice and the
		 * Settings page. Data goes out once when sharing switches from off to
		 * on; the weekly event reports after that, and is dropped as soon as
		 * sharing is switched off.
		 *
		 * @param bool $consent Whether sharing is allowed.
		 * @return array The stored settings after the write.
		 */
		public static function record_consent( $consent ) {
			$settings = get_option( self::OPTION, array() );
			$settings = is_array( $settings ) ? $settings : array();
			$updated  = self::apply_consent( $settings, $consent );

			update_option( self::OPTION, $updated );

			if ( $consent && ! self::has_consent( $settings ) ) {
				self::queue_send();
			} else {
				self::sync_schedule();
			}

			return $updated;
		}

		/**
		 * Settings with a sharing choice recorded.
		 *
		 * Pure. Withdrawing a yes also dismisses the consent notice, so an
		 * admin who just switched sharing off is not asked again a week later.
		 * A first-time no (the wizard's) leaves the notice alone.
		 *
		 * @param array $settings The `_sp_lcpro_options` value.
		 * @param bool  $consent  Whether sharing is allowed.
		 * @return array
		 */
		public static function apply_consent( array $settings, $consent ) {
			$was_allowed = self::has_consent( $settings );

			$settings['diagnostic_data_consent'] = (bool) $consent;

			if ( ! $consent && $was_allowed ) {
				$settings[ DiagnosticConsentNotice::DISMISSED_KEY ] = true;
			}

			return $settings;
		}

		/**
		 * Whether the stored settings record a yes.
		 *
		 * Pure. Only an explicit true counts: a site that never answered
		 * sends nothing.
		 *
		 * @param mixed $settings The `_sp_lcpro_options` value.
		 * @return bool
		 */
		public static function has_consent( $settings ) {
			return is_array( $settings )
				&& isset( $settings['diagnostic_data_consent'] )
				&& true === (bool) $settings['diagnostic_data_consent'];
		}

		/**
		 * Whether a host is a local or private-network address.
		 *
		 * Pure. Dev and staging copies must not show up as real installs.
		 *
		 * @param string $host Host name or IP.
		 * @return bool
		 */
		public static function is_local_host( $host ) {
			$host = strtolower( trim( (string) $host, '[]' ) );

			if ( '' === $host || in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
				return true;
			}

			if ( 1 === preg_match( '/\.(local|localhost|test|dev|example|invalid)$/', $host ) ) {
				return true;
			}

			if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
				return false === filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
			}

			return false;
		}

		/**
		 * Block names, one entry per block on the site (repeats kept, the
		 * same shape Logo Carousel Pro sends).
		 *
		 * Pure.
		 *
		 * @param array<string, int> $counts Slug → count, from BlockUsage::count_blocks().
		 * @return string[] e.g. `sp-logo-carousel/carousel`.
		 */
		public static function block_names( array $counts ) {
			$names = array();

			foreach ( $counts as $slug => $count ) {
				for ( $i = 0; $i < (int) $count; $i++ ) {
					$names[] = BlockUsage::NAMESPACE_PREFIX . $slug;
				}
			}

			return $names;
		}

		/**
		 * The snapshot sent to the collector.
		 *
		 * @return array<string, mixed>
		 */
		private static function payload() {
			return array(
				'user_email'     => get_option( 'admin_email' ),
				'site_url'       => get_option( 'siteurl' ),
				'site_language'  => get_locale(),
				'theme_name'     => wp_get_theme()->get( 'Name' ),
				'plugin_version' => SP_LOGO_CAROUSEL_FREE_VERSION,
				'wp_version'     => function_exists( 'wp_get_wp_version' ) ? wp_get_wp_version() : get_bloginfo( 'version' ),
				'php_version'    => phpversion(),
				'db_version'     => get_option( 'logo_carousel_free_db_version' ),
				'active_plugins' => self::active_plugins(),
				'used_blocks'    => self::block_names( self::count_used_blocks() ),
			);
		}

		/**
		 * Name and version of every active plugin.
		 *
		 * @return array<int, array{name: string, version: string}>
		 */
		private static function active_plugins() {
			// Cron runs outside wp-admin, where get_plugins() is not loaded.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins = get_plugins();
			$active  = array();

			foreach ( (array) get_option( 'active_plugins', array() ) as $path ) {
				if ( isset( $plugins[ $path ] ) ) {
					$active[] = array(
						'name'    => $plugins[ $path ]['Name'],
						'version' => $plugins[ $path ]['Version'],
					);
				}
			}

			return $active;
		}

		/**
		 * Count Logo Carousel blocks in published content and block widgets.
		 *
		 * @return array<string, int> Slug → count.
		 */
		private static function count_used_blocks() {
			global $wpdb;

			$counts  = array();
			$sources = array();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Weekly background job; a fresh read is the point.
			$contents = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_content FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE %s",
					'%' . $wpdb->esc_like( '<!-- wp:' . BlockUsage::NAMESPACE_PREFIX ) . '%'
				)
			);
			foreach ( (array) $contents as $content ) {
				$sources[] = $content;
			}

			foreach ( (array) get_option( 'widget_block', array() ) as $widget ) {
				if ( is_array( $widget ) && ! empty( $widget['content'] ) ) {
					$sources[] = $widget['content'];
				}
			}

			foreach ( $sources as $content ) {
				if ( ! has_blocks( $content ) ) {
					continue;
				}
				foreach ( BlockUsage::count_blocks( parse_blocks( $content ) ) as $slug => $count ) {
					$counts[ $slug ] = ( isset( $counts[ $slug ] ) ? $counts[ $slug ] : 0 ) + $count;
				}
			}

			return $counts;
		}
	}
}
