<?php
/**
 * Our Plugins page data: ShapedPlugin's other free plugins.
 *
 * Reads the WordPress.org listing (cached a day), adds this site's install
 * state and the one action the current user may take on each, and activates
 * an installed one from the dashboard. Ported from WP Carousel Free.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/admin/dashboard
 * @author     ShapedPlugin<support@shapedplugin.com>
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\Dashboard;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Admin\Dashboard\RecommendedPlugins' ) ) {

	/**
	 * Builds the Our Plugins list and handles its Activate button.
	 */
	class RecommendedPlugins {

		/**
		 * The admin-post action behind each Activate button.
		 *
		 * @var string
		 */
		const ACTION_ACTIVATE = 'lcpro_activate_plugin';

		/**
		 * Query arg set on the redirect back when activation failed.
		 *
		 * @var string
		 */
		const FAILED_ARG = 'lcpro_activate_failed';

		/**
		 * Transient holding the normalized WordPress.org listing.
		 *
		 * @var string
		 */
		const TRANSIENT = 'lcpro_shapedplugin_plugins';

		/**
		 * This plugin's own WordPress.org slug, left out of the list.
		 *
		 * @var string
		 */
		const SELF_SLUG = 'logo-carousel-free';

		/**
		 * Hook the activation handler.
		 *
		 * @return void
		 */
		public static function register_hooks() {
			add_action( 'admin_post_' . self::ACTION_ACTIVATE, array( __CLASS__, 'handle_activate' ) );
		}

		/**
		 * The list handed to the Our Plugins page. The listing is cached; state
		 * and links are computed per request so they never go stale.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function for_js() {
			$can_install  = current_user_can( 'install_plugins' );
			$can_activate = current_user_can( 'activate_plugins' );
			$installed    = self::installed_plugin_files();

			$plugins = array();
			foreach ( self::listing() as $listing ) {
				$slug        = $listing['slug'];
				$plugin_file = isset( $installed[ $slug ] ) ? $installed[ $slug ] : '';

				if ( '' !== $plugin_file && is_plugin_active( $plugin_file ) ) {
					$status = 'active';
					$action = '';
				} elseif ( '' !== $plugin_file ) {
					$status = 'installed';
					$action = $can_activate
						? add_query_arg(
							array(
								'action'   => self::ACTION_ACTIVATE,
								'plugin'   => $slug,
								'_wpnonce' => wp_create_nonce( self::ACTION_ACTIVATE . '_' . $slug ),
							),
							admin_url( 'admin-post.php' )
						)
						: '';
				} else {
					$status = 'not-installed';
					$action = $can_install
						? add_query_arg(
							array(
								'action'   => 'install-plugin',
								'plugin'   => $slug,
								'_wpnonce' => wp_create_nonce( 'install-plugin_' . $slug ),
							),
							self_admin_url( 'update.php' )
						)
						: '';
				}

				$plugins[] = $listing + array(
					'status'         => $status,
					'actionUrl'      => $action,
					'lastUpdatedAgo' => ! empty( $listing['lastUpdated'] ) ? human_time_diff( $listing['lastUpdated'] ) : '',
				);
			}

			return $plugins;
		}

		/**
		 * Normalize a raw plugins_api() result: drop this plugin and anything
		 * without a slug, keep only the fields the page shows, and sort by
		 * active installs, most first.
		 *
		 * @param array $raw_plugins Plugin objects or arrays from plugins_api().
		 * @return array<int, array<string, mixed>>
		 */
		public static function normalize( array $raw_plugins ) {
			$plugins = array();
			foreach ( $raw_plugins as $plugin ) {
				$plugin = (array) $plugin;
				$slug   = isset( $plugin['slug'] ) ? sanitize_key( $plugin['slug'] ) : '';
				if ( '' === $slug || self::SELF_SLUG === $slug ) {
					continue;
				}

				$icons = isset( $plugin['icons'] ) ? (array) $plugin['icons'] : array();
				$icon  = '';
				foreach ( array( '2x', '1x', 'default' ) as $size ) {
					if ( ! empty( $icons[ $size ] ) ) {
						$icon = $icons[ $size ];
						break;
					}
				}

				$plugins[] = array(
					'slug'           => $slug,
					// wp.org sends entity-encoded text; React escapes on output.
					'name'           => html_entity_decode( wp_strip_all_tags( isset( $plugin['name'] ) ? $plugin['name'] : '' ), ENT_QUOTES, 'UTF-8' ),
					'description'    => html_entity_decode( wp_strip_all_tags( isset( $plugin['short_description'] ) ? $plugin['short_description'] : '' ), ENT_QUOTES, 'UTF-8' ),
					'icon'           => esc_url_raw( $icon ),
					'link'           => 'https://wordpress.org/plugins/' . $slug . '/',
					'version'        => sanitize_text_field( isset( $plugin['version'] ) ? $plugin['version'] : '' ),
					'rating'         => (float) ( isset( $plugin['rating'] ) ? $plugin['rating'] : 0 ),
					'numRatings'     => (int) ( isset( $plugin['num_ratings'] ) ? $plugin['num_ratings'] : 0 ),
					'activeInstalls' => (int) ( isset( $plugin['active_installs'] ) ? $plugin['active_installs'] : 0 ),
					'lastUpdated'    => ! empty( $plugin['last_updated'] ) ? (int) strtotime( $plugin['last_updated'] ) : 0,
				);
			}

			usort(
				$plugins,
				static function ( $first, $second ) {
					return $second['activeInstalls'] <=> $first['activeInstalls'];
				}
			);

			return $plugins;
		}

		/**
		 * Admin-post handler: activate an installed ShapedPlugin plugin from
		 * the Our Plugins page, then return there. Accepts slugs in the
		 * WordPress.org listing, or, when that listing is unavailable,
		 * installed plugins authored by ShapedPlugin.
		 *
		 * @return void
		 */
		public static function handle_activate() {
			$slug = isset( $_GET['plugin'] ) ? sanitize_key( wp_unslash( $_GET['plugin'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified on the next line.

			check_admin_referer( self::ACTION_ACTIVATE . '_' . $slug );

			$installed   = self::installed_plugin_files();
			$plugin_file = isset( $installed[ $slug ] ) ? $installed[ $slug ] : '';

			if ( ! current_user_can( 'activate_plugins' ) || ( '' !== $plugin_file && ! current_user_can( 'activate_plugin', $plugin_file ) ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to activate plugins on this site.', 'logo-carousel-free' ), '', array( 'response' => 403 ) );
			}

			// A plugin deleted since the page loaded has no file and just fails.
			$listed = '' !== $plugin_file && in_array( $slug, wp_list_pluck( self::listing(), 'slug' ), true );
			if ( ! $listed && '' !== $plugin_file ) {
				$all    = get_plugins();
				$author = isset( $all[ $plugin_file ]['Author'] ) ? $all[ $plugin_file ]['Author'] : '';
				$listed = false !== stripos( $author, 'shapedplugin' );
			}

			$failed = true;
			if ( $listed && ! is_network_only_plugin( $plugin_file ) ) {
				$failed = is_wp_error( activate_plugin( $plugin_file ) );
			}

			$url = admin_url( Dashboard::PARENT_SLUG . '&page=' . Dashboard::PAGE_SLUG );
			if ( $failed ) {
				$url = add_query_arg( self::FAILED_ARG, $slug, $url );
			}

			wp_safe_redirect( $url . '#our-plugins' );
			exit;
		}

		/**
		 * The cached WordPress.org listing of ShapedPlugin's free plugins.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		private static function listing() {
			$cached = get_transient( self::TRANSIENT );
			if ( is_array( $cached ) ) {
				return $cached;
			}

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			$response = plugins_api(
				'query_plugins',
				array(
					'author'   => 'shapedplugin',
					'per_page' => 60,
					'fields'   => array(
						'icons'             => true,
						'short_description' => true,
						'active_installs'   => true,
						'last_updated'      => true,
					),
				)
			);

			if ( is_wp_error( $response ) || empty( $response->plugins ) ) {
				// Short negative cache, so a wp.org outage does not retry on every visit.
				set_transient( self::TRANSIENT, array(), 10 * MINUTE_IN_SECONDS );
				return array();
			}

			$plugins = self::normalize( (array) $response->plugins );
			set_transient( self::TRANSIENT, $plugins, DAY_IN_SECONDS );

			return $plugins;
		}

		/**
		 * Installed plugin main files keyed by their directory slug, so a
		 * plugin is found whatever its main file is called.
		 *
		 * @return array<string, string>
		 */
		private static function installed_plugin_files() {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$files = array();
			foreach ( array_keys( get_plugins() ) as $plugin_file ) {
				if ( false !== strpos( $plugin_file, '/' ) ) {
					$files[ strtok( $plugin_file, '/' ) ] = $plugin_file;
				}
			}

			return $files;
		}
	}
}
