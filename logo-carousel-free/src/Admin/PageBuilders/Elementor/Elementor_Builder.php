<?php
/**
 * Elementor Builder Integration
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Elementor;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Assets;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor builder integration.
 *
 * Registers the Logo Carousel saved template widget with Elementor.
 *
 * @since 4.0.0
 */
class Elementor_Builder {

	/**
	 * Initialize the integration.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widget' ) );

		// Enqueue styles and scripts for the Elementor preview.
		add_action( 'elementor/preview/enqueue_styles', array( __CLASS__, 'enqueue_preview_styles' ) );
		add_action( 'elementor/preview/enqueue_scripts', array( __CLASS__, 'enqueue_preview_scripts' ) );

		// Widget icon in the editor panel.
		add_action( 'elementor/editor/before_enqueue_scripts', array( __CLASS__, 'enqueue_admin_icon' ) );

		// Register the block asset set so the widget's declared dependencies resolve.
		add_action( 'elementor/editor/before_enqueue_scripts', array( Builder_Assets::class, 'register' ) );
	}

	/**
	 * Register the widget with Elementor.
	 *
	 * @since 4.0.0
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public static function register_widget( $widgets_manager ) {
		require_once __DIR__ . '/Elementor_Widget.php';
		$widgets_manager->register( new Elementor_Widget() );
	}

	/**
	 * Enqueue block styles in the Elementor preview.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function enqueue_preview_styles() {
		Builder_Assets::enqueue();
	}

	/**
	 * Enqueue block scripts in the Elementor preview.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function enqueue_preview_scripts() {
		// Re-initialise the block frontend after Elementor re-renders the widget.
		Builder_Assets::reinit_script(
			'lcpro-elementor-template-wrapper',
			array( 'elementor/render/start', 'elementor/frontend/init' )
		);
	}

	/**
	 * Paint the shared plugin icon on the widget in the Elementor panel.
	 *
	 * Elementor renders the widget icon as `<i class="…">`, so the plugin's glyph
	 * font is loaded and the icon takes the panel's own icon colour.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function enqueue_admin_icon() {
		Builder_Icon::enqueue_font();
	}
}
