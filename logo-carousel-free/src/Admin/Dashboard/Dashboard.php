<?php
/**
 * Admin Dashboard menu + React mount node.
 *
 * Thin wrapper: registers the dashboard submenu under the Logo Carousel CPT
 * menu and echoes the empty root node the React SPA (blocks/admin/) mounts
 * into. All asset/enqueue/localize work is delegated to DashboardController.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/admin/dashboard
 * @author     ShapedPlugin<support@shapedplugin.com>
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\Dashboard;

use ShapedPlugin\LogoCarouselFree\Includes\ProFeatures;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Admin\Dashboard\Dashboard' ) ) {

	/**
	 * Registers the admin dashboard page and its React mount node.
	 */
	class Dashboard {

		/**
		 * Parent menu slug the dashboard submenu hangs off (the Logo CPT).
		 *
		 * @var string
		 */
		const PARENT_SLUG = 'edit.php?post_type=sp_logo_carousel';

		/**
		 * Dashboard submenu slug.
		 *
		 * @var string
		 */
		const PAGE_SLUG = 'lcpro_dashboard';

		/**
		 * Backend controller (asset enqueue + localized data).
		 *
		 * @var DashboardController
		 */
		private $controller;

		/**
		 * The page hook suffix returned by add_submenu_page().
		 *
		 * @var string
		 */
		private $page_hook = '';

		/**
		 * Constructor.
		 *
		 * The controller is injected for testability and defaults to a real
		 * instance so callers can `new Dashboard()` with no wiring.
		 *
		 * @param DashboardController|null $controller Optional controller.
		 */
		public function __construct( ?DashboardController $controller = null ) {
			$this->controller = $controller ? $controller : new DashboardController();

			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			// Runs last so it can reorder every submenu (incl. the legacy ones).
			add_action( 'admin_menu', array( $this, 'reorder_submenu' ), 999 );
			// Send legacy Help / Settings / Tools / Analytics page visits to the React tabs.
			add_action( 'admin_init', array( $this, 'redirect_legacy_pages' ) );
			// The dashboard closes with its own footer, as in WP Carousel Free.
			add_action( 'admin_head', array( $this, 'hide_wp_admin_footer' ), 999 );
			// The submenu prints on every admin screen, so its styles and the
			// Upgrade link's new-tab script do too.
			add_action( 'admin_head', array( $this, 'print_menu_styles' ) );
			add_action( 'admin_footer', array( $this, 'print_menu_script' ) );
		}

		/**
		 * Hide core's #wpfooter on the dashboard screen, which the React app's
		 * own footer replaces.
		 *
		 * The app is a full-height flex column with its footer at the bottom, so
		 * #wpbody-content's padding-bottom is zeroed too; otherwise 65px of empty
		 * space would keep the footer off the bottom edge.
		 *
		 * @return void
		 */
		public function hide_wp_admin_footer() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

			if ( '' === $this->page_hook || ! $screen || $screen->id !== $this->page_hook ) {
				return;
			}

			// !important, because this prints before the admin stylesheets
			// enqueued on the same hook.
			echo '<style id="lcpro-hide-wp-footer">#wpfooter{display:none !important}#wpbody-content{padding-bottom:0}</style>';
		}

		/**
		 * Register the dashboard submenu page + the SPA deep-link items.
		 *
		 * @return void
		 */
		public function register_menu() {
			$capability = apply_filters( 'sp_lc_ui_permission', 'manage_options' );

			$this->page_hook = (string) add_submenu_page(
				self::PARENT_SLUG,
				__( 'Logo Carousel', 'logo-carousel-free' ),
				__( 'Getting Started', 'logo-carousel-free' ),
				$capability,
				self::PAGE_SLUG,
				array( $this, 'render' )
			);

			// Tell the controller which screen may enqueue the dashboard bundle.
			$this->controller->set_page_hook( $this->page_hook );

			$dashboard_url = self::PARENT_SLUG . '&page=' . self::PAGE_SLUG;

			// Blocks and Saved Templates are reachable from the dashboard's own
			// tabs; they are deliberately not duplicated here.

			// Analytics — deep-links to the SPA "Analytics" tab, a locked Pro
			// preview in the free plugin (replaces the legacy upsell page).
			add_submenu_page(
				self::PARENT_SLUG,
				__( 'Analytics', 'logo-carousel-free' ),
				$this->menu_badge( __( 'Analytics', 'logo-carousel-free' ) ),
				$capability,
				$dashboard_url . '#analytics'
			);

			// Settings — deep-links to the SPA "Settings" tab (replaces legacy menu).
			add_submenu_page(
				self::PARENT_SLUG,
				__( 'Settings', 'logo-carousel-free' ),
				__( 'Settings', 'logo-carousel-free' ),
				$capability,
				$dashboard_url . '#settings'
			);

			// Lite vs Pro — deep-links to the SPA comparison tab.
			add_submenu_page(
				self::PARENT_SLUG,
				__( 'Lite vs Pro', 'logo-carousel-free' ),
				'<span class="lcpro-menu-lite-vs-pro">' . esc_html__( 'Lite vs Pro', 'logo-carousel-free' ) . '</span>',
				$capability,
				$dashboard_url . '#lite-vs-pro'
			);

			// Upgrade to Pro — a URL as the slug with no callback: WordPress
			// prints the href verbatim, so hovering shows the pricing page.
			add_submenu_page(
				self::PARENT_SLUG,
				__( 'Upgrade to Pro', 'logo-carousel-free' ),
				'<span class="lcpro-menu-upgrade"><span class="lcpro-menu-upgrade-icon" aria-hidden="true"></span>' . esc_html__( 'Upgrade to Pro', 'logo-carousel-free' ) . '</span>',
				$capability,
				ProFeatures::UPGRADE_URL
			);
		}

		/**
		 * Style the Lite vs Pro and Upgrade to Pro rows.
		 *
		 * Every selector is one of this plugin's classes or its own pricing
		 * href, so nothing else in the admin menu is touched. The button takes
		 * the dashboard's brand green.
		 *
		 * @return void
		 */
		public function print_menu_styles() {
			// The browser decodes the rendered href, so the selector matches the raw URL.
			$upgrade_href = str_replace( array( '"', '\\' ), '', esc_url_raw( ProFeatures::UPGRADE_URL ) );

			printf(
				'<style id="lcpro-admin-menu">
				#adminmenu .lcpro-menu-lite-vs-pro{color:#f0b849;font-weight:600}
				#adminmenu .wp-submenu li>a[href="%2$s"]{display:block;box-sizing:border-box;margin:6px;padding:8px 12px;background:#1d8958;border-radius:4px}
				#adminmenu .wp-submenu li>a[href="%2$s"]:hover,#adminmenu .wp-submenu li>a[href="%2$s"]:focus{background:#176d46;box-shadow:none;color:#fff}
				#adminmenu .wp-submenu li>a[href="%2$s"]:focus-visible{outline:2px solid #fff;outline-offset:-2px}
				#adminmenu .lcpro-menu-upgrade{display:flex;align-items:center;justify-content:center;gap:6px;color:#fff;font-size:13px;font-weight:600;line-height:20px;white-space:nowrap}
				#adminmenu .lcpro-menu-upgrade-icon{flex:0 0 auto;width:14px;height:17px;background:url(%1$s) no-repeat center/contain}
				</style>',
				esc_url( SP_LOGO_CAROUSEL_FREE_URL . 'Admin/assets/images/icon-crown.svg' ),
				$upgrade_href // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized with esc_url_raw(); esc_url() would entity-encode it and break the attribute selector.
			);
		}

		/**
		 * Open the Upgrade to Pro row in a new tab.
		 *
		 * WordPress cannot set attributes on a submenu link, and the menu is
		 * already in the page by the time the footer prints.
		 *
		 * @return void
		 */
		public function print_menu_script() {
			printf(
				'<script>(function(){var u=%s,l=document.querySelectorAll("#adminmenu .wp-submenu a");for(var i=0;i<l.length;i++){if(l[i].getAttribute("href")===u){l[i].target="_blank";l[i].rel="noopener noreferrer";}}})();</script>',
				wp_json_encode( esc_url_raw( ProFeatures::UPGRADE_URL ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded URL, safe as a JS string literal.
			);
		}

		/**
		 * Build a menu title with a small "NEW" badge appended.
		 *
		 * @param string $label Base label.
		 * @return string
		 */
		private function menu_badge( $label ) {
			return $label . ' <span class="lcpro-menu-badge" style="display:inline-block;margin-left:4px;color:#f07800;font-size:9px;font-weight:500;line-height:1;vertical-align:middle;text-transform:uppercase;">' . esc_html__( 'NEW!', 'logo-carousel-free' ) . '</span>';
		}

		/**
		 * Reorder the Logo Carousel submenu:
		 * Getting Started · All Logos · Add New Logo · Categories ·
		 * Manage Logo Views · Analytics · Settings · Lite vs Pro · (rest) ·
		 * Upgrade to Pro.
		 *
		 * The legacy free Settings and Tools pages (registered by the old
		 * options framework) are hidden here — the React Settings tab replaces
		 * them and redirect_legacy_pages() forwards their URLs.
		 *
		 * @return void
		 */
		public function reorder_submenu() {
			global $submenu;

			if ( empty( $submenu[ self::PARENT_SLUG ] ) || ! is_array( $submenu[ self::PARENT_SLUG ] ) ) {
				return;
			}

			// Hide legacy pages — SPA tabs replace them.
			foreach ( array_keys( self::legacy_page_map() ) as $legacy_slug ) {
				remove_submenu_page( self::PARENT_SLUG, $legacy_slug );
			}

			$decorated = array();
			foreach ( $submenu[ self::PARENT_SLUG ] as $index => $item ) {
				$decorated[] = array(
					'rank'  => $this->menu_rank( isset( $item[2] ) ? (string) $item[2] : '' ),
					'index' => $index,
					'item'  => $item,
				);
			}

			usort(
				$decorated,
				static function ( $a, $b ) {
					if ( $a['rank'] === $b['rank'] ) {
						return $a['index'] - $b['index'];
					}
					return $a['rank'] - $b['rank'];
				}
			);

			$submenu[ self::PARENT_SLUG ] = array_map(
				static function ( $entry ) {
					return $entry['item'];
				},
				$decorated
			);
		}

		/**
		 * Desired position for a submenu slug (lower = earlier). Unknown items
		 * keep their relative order at the end.
		 *
		 * @param string $slug Submenu slug (item[2]).
		 * @return int
		 */
		private function menu_rank( $slug ) {
			if ( self::PAGE_SLUG === $slug ) {
				return 0; // Getting Started.
			}
			if ( self::PARENT_SLUG === $slug ) {
				return 1; // All Logos.
			}
			if ( false !== strpos( $slug, 'post-new.php?post_type=sp_logo_carousel' ) ) {
				return 2; // Add New Logo.
			}
			if ( false !== strpos( $slug, 'edit-tags.php' ) && false !== strpos( $slug, 'sp_logo_carousel_cat' ) ) {
				return 3; // Categories.
			}
			if ( false !== strpos( $slug, 'post_type=sp_lc_shortcodes' ) ) {
				return 4; // Manage Logo Views.
			}
			if ( false !== strpos( $slug, '#analytics' ) ) {
				return 5;
			}
			if ( false !== strpos( $slug, '#settings' ) ) {
				return 6;
			}
			if ( false !== strpos( $slug, '#lite-vs-pro' ) ) {
				return 7;
			}
			if ( ProFeatures::UPGRADE_URL === $slug ) {
				return 200; // Upgrade to Pro, after anything other plugins add.
			}

			return 100;
		}

		/**
		 * Legacy admin page slugs and the dashboard hash each one maps to.
		 *
		 * `lc_*` / `lcpro_tools` are the 3.x free pages; `lcpro_settings` and
		 * `lcpro_analytics` are Pro's, kept so links shared between the two
		 * plugins still land somewhere useful.
		 *
		 * @return array<string, string>
		 */
		public static function legacy_page_map() {
			return array(
				'lc_help'         => '#dashboard',
				'lc_settings'     => '#settings',
				'lcpro_tools'     => '#settings=import-export',
				'lc_analytics'    => '#analytics',
				'lcpro_settings'  => '#settings',
				'lcpro_analytics' => '#analytics',
			);
		}

		/**
		 * Redirect legacy Help / Settings / Tools / Analytics pages to the
		 * dashboard SPA tabs.
		 *
		 * @return void
		 */
		public function redirect_legacy_pages() {
			if ( ! is_admin() || 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return;
			}

			if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$page       = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$legacy_map = self::legacy_page_map();

			if ( ! isset( $legacy_map[ $page ] ) ) {
				return;
			}

			$capability = apply_filters( 'sp_lc_ui_permission', 'manage_options' );
			if ( ! current_user_can( $capability ) ) {
				return;
			}

			wp_safe_redirect(
				admin_url( self::PARENT_SLUG . '&page=' . self::PAGE_SLUG . $legacy_map[ $page ] )
			);
			exit;
		}

		/**
		 * Redirect legacy `lcpro_settings` page to the dashboard Settings tab.
		 *
		 * @deprecated 4.0.0 Use redirect_legacy_pages().
		 * @return void
		 */
		public function redirect_legacy_settings() {
			$this->redirect_legacy_pages();
		}

		/**
		 * Render the React mount node.
		 *
		 * @return void
		 */
		public function render() {
			echo '<div class="lcpro-admin-dashboard-root"><div id="lcpro-admin-dashboard-wrapper"></div></div>';
		}
	}
}
