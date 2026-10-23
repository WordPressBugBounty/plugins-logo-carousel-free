<?php
/**
 * Admin Dashboard backend controller.
 *
 * Owns the React SPA's server side: enqueues the built bundle on the
 * dashboard screen only and hands the app its localized bootstrap data.
 * AJAX handlers for block/module visibility, settings, the changelog and
 * the AI key check are wired here too, with the stores injected.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/admin/dashboard
 * @author     ShapedPlugin<support@shapedplugin.com>
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\Dashboard;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Integration_Settings;
use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\BlocksVisibility;
use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\ModulesVisibility;
use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\VisibilityStoreInterface;
use ShapedPlugin\LogoCarouselFree\Includes\AI\AIFeature;
use ShapedPlugin\LogoCarouselFree\Includes\AI\AIKeyManager;
use ShapedPlugin\LogoCarouselFree\Includes\ProFeatures;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Admin\Dashboard\DashboardController' ) ) {

	/**
	 * Enqueues the dashboard bundle and localizes its bootstrap data.
	 */
	class DashboardController {

		/**
		 * JS global the React app reads its bootstrap data from.
		 *
		 * @var string
		 */
		const JS_OBJECT = 'spLcproAdminDashboard';

		/**
		 * Relative path (from plugin root) to the built dashboard script.
		 *
		 * @var string
		 */
		const SCRIPT = 'assets/admin/index.js';

		/**
		 * Relative path (from plugin root) to the built dashboard style.
		 *
		 * @var string
		 */
		const STYLE = 'assets/admin/index.css';

		/**
		 * The dashboard page hook suffix; only this screen enqueues the bundle.
		 *
		 * @var string
		 */
		private $page_hook = '';

		/**
		 * Block visibility store.
		 *
		 * @var VisibilityStoreInterface
		 */
		private $blocks;

		/**
		 * Module visibility store.
		 *
		 * @var VisibilityStoreInterface
		 */
		private $modules;

		/**
		 * Constructor.
		 *
		 * @param VisibilityStoreInterface|null $blocks  Injected for tests.
		 * @param VisibilityStoreInterface|null $modules Injected for tests.
		 */
		public function __construct( ?VisibilityStoreInterface $blocks = null, ?VisibilityStoreInterface $modules = null ) {
			$this->blocks  = $blocks ? $blocks : new BlocksVisibility();
			$this->modules = $modules ? $modules : new ModulesVisibility();

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'wp_ajax_lcpro_save_options', array( $this, 'ajax_save_options' ) );
			add_action( 'wp_ajax_lcpro_changelog_data', array( $this, 'ajax_changelog_data' ) );
			add_action( 'wp_ajax_lcpro_save_user_consent', array( $this, 'ajax_save_user_consent' ) );
			add_action( 'wp_ajax_lcpro_ai_verify_key', array( $this, 'ajax_verify_ai_key' ) );
			add_action( 'wp_ajax_lcpro_block_usage', array( $this, 'ajax_block_usage' ) );
			add_action( 'wp_ajax_lcpro_recommended_plugins', array( $this, 'ajax_recommended_plugins' ) );
			RecommendedPlugins::register_hooks();
		}

		/**
		 * Record which screen is allowed to load the dashboard bundle.
		 *
		 * @param string $page_hook Hook suffix from add_submenu_page().
		 * @return void
		 */
		public function set_page_hook( $page_hook ) {
			$this->page_hook = (string) $page_hook;
		}

		/**
		 * Enqueue the dashboard bundle on the dashboard screen only.
		 *
		 * @param string $hook_suffix Current admin page hook suffix.
		 * @return void
		 */
		public function enqueue( $hook_suffix ) {
			if ( '' === $this->page_hook || $hook_suffix !== $this->page_hook ) {
				return;
			}

			if ( ! file_exists( $this->asset_path( self::SCRIPT ) ) ) {
				return;
			}

			$deps = array( 'wp-element', 'wp-i18n', 'wp-components' );
			$ver  = SP_LOGO_CAROUSEL_FREE_VERSION;

			$asset_file = $this->asset_path( 'assets/admin/index.asset.php' );
			if ( file_exists( $asset_file ) ) {
				$asset = require $asset_file;

				if ( ! empty( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ) {
					$deps = $asset['dependencies'];
				}

				if ( ! empty( $asset['version'] ) ) {
					$ver = $asset['version'];
				}
			}

			wp_enqueue_script(
				'lcpro-admin-dashboard',
				$this->asset_url( self::SCRIPT ),
				$deps,
				$ver,
				true
			);

			wp_localize_script(
				'lcpro-admin-dashboard',
				self::JS_OBJECT,
				$this->get_bootstrap_data()
			);

			wp_set_script_translations( 'lcpro-admin-dashboard', 'logo-carousel-free' );

			if ( file_exists( $this->asset_path( self::STYLE ) ) ) {
				wp_enqueue_style(
					'lcpro-admin-dashboard',
					$this->asset_url( self::STYLE ),
					array( 'wp-components' ),
					$this->asset_version( self::STYLE )
				);
				wp_style_add_data( 'lcpro-admin-dashboard', 'rtl', 'replace' );
			}
		}

		/**
		 * Bootstrap data handed to the React app.
		 *
		 * Identity, endpoints, nav URLs, visibility maps and settings. The
		 * free plugin sends no license or analytics data: the Analytics tab is
		 * a static preview.
		 *
		 * @return array<string, mixed>
		 */
		private function get_bootstrap_data() {
			$current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
			$user_name    = ( $current_user && ! empty( $current_user->display_name ) ) ? $current_user->display_name : '';
			$first_name   = ( $current_user && ! empty( $current_user->first_name ) ) ? trim( (string) $current_user->first_name ) : '';

			return array(
				'pluginVersion'     => SP_LOGO_CAROUSEL_FREE_VERSION,
				'pluginUrl'         => SP_LOGO_CAROUSEL_FREE_URL,
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'restUrl'           => esc_url_raw( rest_url( 'lcpro/v1/' ) ),
				'restNonce'         => wp_create_nonce( 'wp_rest' ),
				'nonce'             => wp_create_nonce( 'lcpro_dashboard' ),
				'adminUrl'          => admin_url(),
				'homeUrl'           => home_url(),
				// Committed image base (referenced by URL, not bundled). No `src/`
				// prefix: SP_LOGO_CAROUSEL_FREE_URL already ends in `src/`.
				'imagesUrl'         => SP_LOGO_CAROUSEL_FREE_URL . 'Admin/img/dashboard/',
				'userName'          => $user_name,
				// The profile's First Name, for greetings; empty when not set
				// (the greeting then takes the first word of `userName`).
				'userFirstName'     => $first_name,
				// Picks the welcome copy (first visit vs returning). Null until
				// the placements report has been built once.
				'hasShowcases'      => BlockUsage::stored_has_showcases(),
				'newLogoUrl'        => admin_url( 'post-new.php?post_type=sp_logo_carousel' ),
				'newTemplateUrl'    => admin_url( 'post-new.php?post_type=sp_lc_template' ),
				'blockEditorUrl'    => admin_url( 'post-new.php?post_type=page' ),
				'classicEditorUrl'  => admin_url( 'post-new.php?post_type=sp_lc_shortcodes' ),
				'blocksVisibility'  => $this->blocks->all(),
				'modulesVisibility' => $this->modules->all(),
				'pluginSettings'    => $this->get_plugin_settings(),
				'optionsNonce'      => wp_create_nonce( 'splogocarousel_options_nonce' ),
				// Export select options (shortcodes) for the Import & Export panel.
				'toolsExport'       => $this->get_tools_export_lists(),
				'upgradeUrl'        => ProFeatures::UPGRADE_URL,
				// The settings screen is the one place that needs an authoritative
				// connector answer and the provider's real model list, so it is the
				// one place that pays for probing them.
				// Whether the dashboard can offer "Build with AI": the module is on
				// and the site allows AI (the editor registers no builder otherwise).
				'aiAvailable'       => AIFeature::is_available(),
				'aiSettings'        => AIFeature::is_module_enabled()
					? AIKeyManager::get_public_settings( true )
					: array(),
			);
		}

		/**
		 * Capability gate shared by dashboard AJAX handlers.
		 *
		 * @return bool
		 */
		private function can_manage_dashboard() {
			$capability = apply_filters( 'sp_lc_ui_permission', 'manage_options' );
			return current_user_can( $capability );
		}

		/**
		 * Plugin settings option payload for the React Settings page.
		 *
		 * @return array<string, mixed>
		 */
		private function get_plugin_settings() {
			$settings = get_option( '_sp_lcpro_options', array() );
			return is_array( $settings ) ? $settings : array();
		}

		/**
		 * Shortcode list for the Import & Export settings panel's
		 * "Selected Logo Carousels" picker.
		 *
		 * The free `lcp_export_shortcodes` handler exports selected IDs as
		 * `sp_lc_shortcodes` posts only, so no logo list is sent.
		 *
		 * @return array{shortcodes: array<int, array{id: int, title: string}>}
		 */
		private function get_tools_export_lists() {
			return array(
				'shortcodes' => $this->get_post_options_list( 'sp_lc_shortcodes' ),
			);
		}

		/**
		 * Lightweight id/title list for a post type (export multi-select).
		 *
		 * @param string $post_type Post type slug.
		 * @return array<int, array{id: int, title: string}>
		 */
		private function get_post_options_list( $post_type ) {
			$posts = get_posts(
				array(
					'post_type'              => $post_type,
					'post_status'            => array( 'publish', 'inherit' ),
					'posts_per_page'         => -1,
					'orderby'                => 'title',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			$list = array();
			foreach ( $posts as $post ) {
				$list[] = array(
					'id'    => (int) $post->ID,
					'title' => $post->post_title ? $post->post_title : __( '(no title)', 'logo-carousel-free' ),
				);
			}

			return $list;
		}

		/**
		 * Allowed keys that may be written from the React Settings page.
		 *
		 * @return string[]
		 */
		private function allowed_setting_keys() {
			return array(
				'lcpro_data_remove',
				'lcpro_fontawesome_css',
				'lcpro_swiper_css',
				'lcpro_swiper_js',
				'lcpro_custom_css',
				'custom_js',
				'setup_wizard_completed',
				'integrations',
			);
		}

		/**
		 * Sanitize and merge settings into `_sp_lcpro_options`.
		 *
		 * @param array $incoming Incoming settings map.
		 * @return array Updated settings.
		 */
		private function save_plugin_settings( array $incoming ) {
			$current   = $this->get_plugin_settings();
			$allowed   = $this->allowed_setting_keys();
			$bool_keys = array(
				'lcpro_data_remove',
				'lcpro_fontawesome_css',
				'lcpro_swiper_css',
				'lcpro_swiper_js',
				'setup_wizard_completed',
			);

			foreach ( $allowed as $key ) {
				if ( ! array_key_exists( $key, $incoming ) ) {
					continue;
				}

				$value = $incoming[ $key ];

				if ( in_array( $key, $bool_keys, true ) ) {
					$current[ $key ] = (bool) $value;
					continue;
				}

				if ( 'integrations' === $key ) {
					$current[ $key ] = Integration_Settings::sanitize( $value, isset( $current[ $key ] ) ? $current[ $key ] : array() );
					continue;
				}

				if ( 'lcpro_custom_css' === $key || 'custom_js' === $key ) {
					$current[ $key ] = is_string( $value ) ? $value : '';
					continue;
				}

				$current[ $key ] = sanitize_text_field( (string) $value );
			}

			update_option( '_sp_lcpro_options', $current );

			return $current;
		}

		/**
		 * Capability + nonce gate shared by dashboard AJAX handlers.
		 *
		 * @return void Sends JSON error and exits on failure.
		 */
		private function assert_dashboard_ajax() {
			if ( ! check_ajax_referer( 'lcpro_dashboard', 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request.', 'logo-carousel-free' ) ), 403 );
			}

			$capability = apply_filters( 'sp_lc_ui_permission', 'manage_options' );
			if ( ! current_user_can( $capability ) ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'logo-carousel-free' ) ), 403 );
			}
		}

		/**
		 * AJAX: the dashboard's "Logo Showcase Placements" report.
		 *
		 * Answers from the stored report, scanning only on the first visit.
		 * `refresh` — the table's Sync button — scans the site again and
		 * replaces what is stored.
		 *
		 * @return void
		 */
		public function ajax_block_usage() {
			$this->assert_dashboard_ajax();

			$force = isset( $_POST['refresh'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['refresh'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in assert_dashboard_ajax().

			wp_send_json_success( BlockUsage::report( $force ) );
		}

		/**
		 * AJAX: the Our Plugins list.
		 *
		 * Fetched when that tab opens rather than localized with the bundle, so
		 * a cold WordPress.org cache never delays the rest of the dashboard.
		 *
		 * @return void
		 */
		public function ajax_recommended_plugins() {
			$this->assert_dashboard_ajax();

			wp_send_json_success( array( 'plugins' => RecommendedPlugins::for_js() ) );
		}

		/**
		 * AJAX: batch-save dashboard options.
		 *
		 * Phase 3 handles block visibility; later phases extend the payload
		 * (modules, settings) behind the same action.
		 *
		 * @return void
		 */
		public function ajax_save_options() {
			$this->assert_dashboard_ajax();

			if ( isset( $_POST['blocks'] ) ) {
				$decoded = json_decode( wp_unslash( $_POST['blocks'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- values sanitised in BlocksVisibility::sanitize().
				if ( is_array( $decoded ) ) {
					$this->blocks->set( $decoded );
				}
			}

			if ( isset( $_POST['modules'] ) ) {
				$decoded = json_decode( wp_unslash( $_POST['modules'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- values sanitised in ModulesVisibility::sanitize().
				if ( is_array( $decoded ) ) {
					$this->modules->set( $decoded );
					\ShapedPlugin\LogoCarouselFree\Blocks\Rendering\ModuleGate::flush_cache();
				}
			}

			$plugin_settings = $this->get_plugin_settings();
			if ( isset( $_POST['settings'] ) ) {
				$decoded = json_decode( wp_unslash( $_POST['settings'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised in save_plugin_settings().
				if ( is_array( $decoded ) ) {
					$plugin_settings = $this->save_plugin_settings( $decoded );
				}
			}

			// Consent stays out of the settings allow-list: the tabs post the
			// whole map they were seeded with, and a stale copy must never
			// record a sharing choice. It travels on its own only when the
			// admin changed it, through the one write path that knows when to
			// send.
			if ( isset( $_POST['diagnosticConsent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in assert_dashboard_ajax().
				$consent         = sanitize_text_field( wp_unslash( $_POST['diagnosticConsent'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in assert_dashboard_ajax().
				$plugin_settings = DiagnosticData::record_consent( in_array( $consent, array( '1', 'true' ), true ) );
			}

			// A key the provider refuses is not stored, so the response has to
			// carry what happened to it — reporting "saved" for a credential
			// nothing can use is how an unusable key passes for a configured one.
			$ai_key_result = null;
			if ( isset( $_POST['aiSettings'] ) && AIFeature::is_module_enabled() ) {
				$decoded = json_decode( wp_unslash( $_POST['aiSettings'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised in AIKeyManager::save().
				if ( is_array( $decoded ) ) {
					$ai_save       = AIKeyManager::save( $decoded );
					$ai_key_result = array_merge( $ai_save['key'], array( 'provider' => $ai_save['provider'] ) );
				}
			}

			$response = array(
				'blocksVisibility'  => $this->blocks->all(),
				'modulesVisibility' => $this->modules->all(),
				'pluginSettings'    => $plugin_settings,
			);

			if ( AIFeature::is_module_enabled() ) {
				$response['aiSettings'] = AIKeyManager::get_public_settings( true );
			}

			if ( null !== $ai_key_result ) {
				$response['aiKeyResult'] = $ai_key_result;
			}

			wp_send_json_success( $response );
		}

		/**
		 * AJAX: verify one provider's stored API key.
		 *
		 * Split out from the save path because the settings screen asks this
		 * question on its own: a key stored earlier carries a verdict that goes
		 * stale, and re-checking it on every page render would put a provider
		 * round-trip in front of the dashboard.
		 *
		 * @return void
		 */
		public function ajax_verify_ai_key() {
			$this->assert_dashboard_ajax();

			if ( ! AIFeature::is_module_enabled() ) {
				wp_send_json_error( array( 'message' => AIFeature::unavailable_error()->get_error_message() ), 403 );
			}

			$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
			if ( ! in_array( $provider, AIKeyManager::PROVIDERS, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Unknown AI provider.', 'logo-carousel-free' ) ), 400 );
			}

			$force = isset( $_POST['force'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['force'] ) );

			wp_send_json_success( array( 'status' => AIKeyManager::verify_stored_key( $provider, $force ) ) );
		}

		/**
		 * AJAX: record the setup wizard's diagnostic-data choice.
		 *
		 * Called when the wizard finishes. Persists the user's consent answer
		 * and records that the wizard was completed. A switch to yes queues
		 * the first diagnostic send (queued, not sent inline, so the wizard
		 * never waits on a site scan and a remote call); the weekly cron sends
		 * the rest, and a no drops it. Skipping the wizard posts no consent
		 * key, which leaves any earlier answer untouched while still marking
		 * it done.
		 *
		 * @return void
		 */
		public function ajax_save_user_consent() {
			$this->assert_dashboard_ajax();

			$plugin_settings = $this->save_plugin_settings( array( 'setup_wizard_completed' => true ) );

			if ( isset( $_POST['consent'] ) ) {
				$consent         = sanitize_text_field( wp_unslash( $_POST['consent'] ) );
				$plugin_settings = DiagnosticData::record_consent( in_array( $consent, array( '1', 'true' ), true ) );
			}

			wp_send_json_success( array( 'pluginSettings' => $plugin_settings ) );
		}

		/**
		 * AJAX: changelog HTML for the header version drawer.
		 *
		 * @return void
		 */
		public function ajax_changelog_data() {
			$this->assert_dashboard_ajax();

			$changelog = get_transient( 'lcpro_changelogs' );

			if ( empty( $changelog ) ) {
				$changelog = $this->fetch_changelog_from_api();
			}

			wp_send_json_success(
				array(
					'changelog' => $changelog,
				)
			);
		}

		/**
		 * Fetch the changelog from the WordPress.org plugin API (cached one day).
		 *
		 * @return string Sanitized changelog HTML, or empty string on failure.
		 */
		private function fetch_changelog_from_api() {
			$api_url  = add_query_arg(
				array(
					'action'                    => 'plugin_information',
					'request[slug]'             => 'logo-carousel-free',
					'request[fields][sections]' => 1,
				),
				'https://api.wordpress.org/plugins/info/1.2/'
			);
			$response = wp_safe_remote_get(
				esc_url_raw( $api_url ),
				array(
					'timeout' => 15,
				)
			);

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return '';
			}

			$api_data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! isset( $api_data['sections']['changelog'] ) ) {
				return '';
			}

			$changelog = wp_kses_post( $api_data['sections']['changelog'] );
			set_transient( 'lcpro_changelogs', $changelog, DAY_IN_SECONDS );

			return $changelog;
		}

		/**
		 * Absolute filesystem path for a plugin-root-relative asset.
		 *
		 * @param string $relative_path Relative path from plugin root.
		 * @return string
		 */
		private function asset_path( $relative_path ) {
			return dirname( SP_LOGO_CAROUSEL_FREE_FILE ) . '/' . ltrim( $relative_path, '/' );
		}

		/**
		 * Plugin URL for a plugin-root-relative asset.
		 *
		 * @param string $relative_path Relative path from plugin root.
		 * @return string
		 */
		private function asset_url( $relative_path ) {
			return plugin_dir_url( SP_LOGO_CAROUSEL_FREE_FILE ) . ltrim( $relative_path, '/' );
		}

		/**
		 * Cache-busting version from filemtime when available.
		 *
		 * @param string $relative_path Relative path from plugin root.
		 * @return string
		 */
		private function asset_version( $relative_path ) {
			$asset_path = $this->asset_path( $relative_path );

			if ( file_exists( $asset_path ) ) {
				return (string) filemtime( $asset_path );
			}

			return SP_LOGO_CAROUSEL_FREE_VERSION;
		}
	}
}
