<?php
/**
 * WPBakery Integration
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\WPBakery;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Assets;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Icon;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_List;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPBakery Page Builder integration.
 *
 * @since 4.0.0
 */
class WPBakery_Builder {

	/**
	 * Element / shortcode base.
	 *
	 * @var string
	 */
	const BASE = 'vc_sp_logo_carousel';

	/**
	 * Initialize the integration.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function init() {
		// Register the element after WPBakery has fully loaded.
		add_action( 'vc_before_init', array( __CLASS__, 'register_element' ) );

		// Shortcode handler for the element.
		add_shortcode( self::BASE, array( __CLASS__, 'render_element' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_styles' ) );

		// The frontend editor builds its element list outside admin_enqueue_scripts.
		add_action( 'vc_frontend_editor_enqueue_js_css', array( __CLASS__, 'enqueue_element_icon' ) );
	}

	/**
	 * Register the Logo Carousel element with WPBakery.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function register_element() {
		if ( ! defined( 'WPB_VC_VERSION' ) || ! function_exists( 'vc_map' ) ) {
			return;
		}

		vc_map(
			array(
				'name'        => esc_html__( 'Logo Carousel', 'logo-carousel-free' ),
				'base'        => self::BASE,
				'description' => esc_html__( 'Display logos from a saved template', 'logo-carousel-free' ),
				'category'    => esc_html__( 'Logo Carousel', 'logo-carousel-free' ),
				'icon'        => Builder_Icon::CSS_CLASS,
				'params'      => array(
					array(
						'type'        => 'dropdown',
						'heading'     => esc_html__( 'Saved Template', 'logo-carousel-free' ),
						'param_name'  => 'template_id',
						'value'       => Template_List::flipped_options( 'none' ),
						'std'         => '0',
						'description' => esc_html__( 'Select a saved template to display', 'logo-carousel-free' ),
						'save_always' => true,
					),
				),
			)
		);
	}

	/**
	 * Enqueue block assets for the WPBakery frontend editor.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		if ( ! self::is_builder_editor() ) {
			return;
		}

		// Re-initialise the block frontend after WPBakery re-renders the element.
		Builder_Assets::reinit_script(
			'lcpro-wpbakery-template-wrapper',
			array( 'vc_js_reload', 'vc_frontend_default_editor_loaded', 'vc_frontend_render' )
		);
	}

	/**
	 * Enqueue block assets and the element icon for the WPBakery backend editor.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function admin_enqueue_styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		Builder_Assets::enqueue();
		self::enqueue_element_icon();
	}

	/**
	 * Paint the shared plugin icon on the element in the WPBakery element list.
	 *
	 * WPBakery only accepts a class name for `icon`, and it already sizes the
	 * `.vc_element-icon` box itself, so no sizing box is emitted.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function enqueue_element_icon() {
		// Compound selector so the rule outranks WPBakery's own `.vc_element-icon` background.
		$selector = sprintf( '.vc_element-icon.%1$s,.%1$s', Builder_Icon::CSS_CLASS );

		Builder_Icon::enqueue_css( 'lcpro-wpbakery-element-icon', $selector, false );
	}

	/**
	 * Shortcode handler for the WPBakery element.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Inner content (not used).
	 * @return string HTML output.
	 */
	public static function render_element( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'template_id' => '',
			),
			$atts,
			self::BASE
		);

		return Template_Renderer::render( (int) $atts['template_id'], 'lcpro-wpbakery-template-wrapper', self::is_builder_editor() );
	}

	/**
	 * Check if the current request is for the WPBakery builder editor.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public static function is_builder_editor() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only builder-context detection, no state change.
		if ( isset( $_GET['vc_editable'] ) || isset( $_GET['vc_preview'] ) ) {
			return true;
		}

		return function_exists( 'vc_is_inline' ) && vc_is_inline();
	}
}
