<?php
/**
 * Divi Builder Integration
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Divi;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Assets;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Icon;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Divi Builder (Divi 4) integration.
 *
 * @since 4.0.0
 */
class Divi_Builder {

	/**
	 * Divi module slug.
	 *
	 * @var string
	 */
	const MODULE_SLUG = 'et_pb_sp_logo_carousel';

	/**
	 * Initialize the integration.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function init() {
		// Register the module once Divi's module API is ready.
		add_action( 'et_builder_ready', array( __CLASS__, 'register_module' ) );

		// Show the shared plugin icon on the module in the Add Module list.
		add_filter( 'et_builder_module_icons', array( __CLASS__, 'register_module_icon' ) );

		// Block assets for the builder editor.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_page_builder_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_page_builder_scripts' ) );
	}

	/**
	 * Register the module with Divi Builder.
	 *
	 * The module class extends `ET_Builder_Module`, so its file is only loaded here,
	 * once that parent class exists.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function register_module() {
		static $registered = false;

		if ( $registered || ! class_exists( 'ET_Builder_Module' ) ) {
			return;
		}

		require_once __DIR__ . '/Divi_Module.php';

		new Divi_Module();

		$registered = true;
	}

	/**
	 * Register the shared plugin icon for the module.
	 *
	 * @since 4.0.0
	 *
	 * @param array $icons Registered module icons.
	 * @return array
	 */
	public static function register_module_icon( $icons ) {
		return Builder_Icon::divi_module_icons( $icons, self::MODULE_SLUG );
	}

	/**
	 * Enqueue block assets inside the Divi builder editor (not the regular frontend).
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function enqueue_page_builder_scripts() {
		if ( ! self::is_builder_editor() ) {
			return;
		}

		// Re-initialise the block frontend after Divi swaps module markup in.
		Builder_Assets::reinit_script(
			'lcpro-divi-template-wrapper',
			array( 'divi:module:updated', 'divi:ajax:render:success', 'divi:builder:save' )
		);

		// Divi blocks link clicks in its canvas; keep the template actions working.
		Template_Actions::enqueue_canvas_link_handler( Builder_Assets::RUNTIME_HANDLE );

		// Native module tiles leave 5px between the 16px glyph and the label;
		// Divi's inline -6px margin on an SVG icon leaves none. The tile class
		// is the slug without its `et_pb_` prefix.
		wp_register_style( 'lcpro-divi-module-icon', false, array(), SP_LOGO_CAROUSEL_FREE_VERSION );
		wp_add_inline_style( 'lcpro-divi-module-icon', 'li.et_fb_' . substr( self::MODULE_SLUG, 6 ) . ' > .et-fb-icon--svg{margin-bottom:-1px!important}' );
		wp_enqueue_style( 'lcpro-divi-module-icon' );
	}

	/**
	 * Check if the current request is for Divi Builder or Visual Builder.
	 *
	 * Detects Frontend Builder (et_fb), Backend Builder (et_bfb), and AJAX rendering modes.
	 *
	 * @since 4.0.0
	 *
	 * @return bool True if in Divi builder context, false otherwise.
	 */
	public static function is_builder_editor() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only builder-context detection, no state change.

		// 1. Divi Frontend Builder (Visual Builder).
		if ( isset( $_GET['et_fb'] ) ) {
			return true;
		}

		// 2. Divi Backend Builder.
		if ( isset( $_GET['et_bfb'] ) && is_admin() ) {
			return true;
		}

		// 3. Divi preview mode.
		if ( isset( $_GET['et_pb_preview'] ) ) {
			return true;
		}

		// 4. Global set during Frontend Builder.
		if ( isset( $GLOBALS['et_fb'] ) && $GLOBALS['et_fb'] ) {
			return true;
		}

		// 5. Divi AJAX requests (module rendering).
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 0 === strpos( sanitize_key( wp_unslash( $_REQUEST['action'] ) ), 'et_' ) ) {
			return true;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// 6. Divi JSON request (Visual Builder module rendering).
		if ( wp_is_json_request() && function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
			return true;
		}

		// 7. Builder loaded (Backend Builder in admin context).
		if ( function_exists( 'et_builder_is_loaded' ) && et_builder_is_loaded() && is_admin() ) {
			return true;
		}

		// 8. Final fallback: Frontend Builder enabled.
		return function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled();
	}
}
