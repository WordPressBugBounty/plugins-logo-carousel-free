<?php
/**
 * Divi 5 Builder Integration
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Divi5;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Assets;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Icon;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Cannot access directly.
}

/**
 * Divi 5 Builder class for initialization.
 *
 * The module itself is registered by server/index.php (loaded from Manager before
 * Divi builds its dependency tree); this class wires the builder assets.
 *
 * @since 4.0.0
 */
class Divi5_Builder {

	/**
	 * Divi 5 module name (module.json `name`).
	 *
	 * @var string
	 */
	const MODULE_NAME = 'lcpro/divi5-logo-carousel';

	/**
	 * Divi 4 shortcode slug the module maps to (module.json `d4Shortcode`).
	 *
	 * @var string
	 */
	const D4_SHORTCODE = 'et_pb_sp_logo_carousel';

	/**
	 * Visual builder bundle, relative to the plugin root.
	 *
	 * @var string
	 */
	const BUNDLE = 'assets/divi5/lcpro-divi5-module.js';

	/**
	 * Initialize the integration.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! self::is_divi_5_active() ) {
			return;
		}

		// Divi 4 compatibility surfaces only — the D5 module list icon ships in the bundle.
		add_filter( 'et_builder_module_icons', array( __CLASS__, 'register_module_icon' ) );

		add_action( 'divi_visual_builder_assets_before_enqueue_scripts', array( __CLASS__, 'enqueue_visual_builder_assets' ) );
	}

	/**
	 * Check if Divi 5 is active.
	 *
	 * @since 4.0.0
	 *
	 * @return bool True if Divi 5.x is active.
	 */
	public static function is_divi_5_active() {
		if ( function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled() ) {
			return true;
		}

		if ( defined( 'ET_BUILDER_VERSION' ) ) {
			return version_compare( ET_BUILDER_VERSION, '5.0', '>=' );
		}

		return false;
	}

	/**
	 * Register the shared plugin icon on Divi's legacy icon array.
	 *
	 * This does not paint the Divi 5 module list — D5 resolves module.json's
	 * `moduleIcon` against a JS registry shipped in the bundle. Kept because Divi 4
	 * rendering surfaces can still resolve the `d4Shortcode` slug on a Divi 5 site.
	 *
	 * @since 4.0.0
	 *
	 * @param array $icons Registered module icons.
	 * @return array
	 */
	public static function register_module_icon( $icons ) {
		return Builder_Icon::divi_module_icons( $icons, array( self::MODULE_NAME, self::D4_SHORTCODE ) );
	}

	/**
	 * Enqueue visual builder assets.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function enqueue_visual_builder_assets() {
		if ( ! function_exists( 'et_core_is_fb_enabled' ) || ! et_core_is_fb_enabled() ) {
			return;
		}

		if ( ! function_exists( 'et_builder_d5_enabled' ) || ! et_builder_d5_enabled() ) {
			return;
		}

		$bundle = dirname( SP_LOGO_CAROUSEL_FREE_FILE ) . '/' . self::BUNDLE;

		if ( file_exists( $bundle ) && class_exists( 'ET\Builder\VisualBuilder\Assets\PackageBuildManager' ) ) {
			\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
				array(
					'name'    => 'lcpro-divi5-module-visual-builder',
					'version' => (string) filemtime( $bundle ),
					'script'  => array(
						'src'                => plugin_dir_url( SP_LOGO_CAROUSEL_FREE_FILE ) . self::BUNDLE,
						'deps'               => array( 'react', 'jquery', 'divi-module-library', 'wp-hooks', 'divi-rest' ),
						'enqueue_app_window' => true,
					),
				)
			);
		}

		// Re-initialise the block frontend after Divi 5 swaps module markup in.
		Builder_Assets::reinit_script(
			'lcpro-divi5-template-wrapper',
			array( 'divi:module:updated', 'divi:ajax:render:success', 'divi:builder:save' )
		);

		// Divi 5 blocks link clicks in its canvas; keep the template actions working.
		Template_Actions::enqueue_canvas_link_handler( Builder_Assets::RUNTIME_HANDLE );
	}
}
