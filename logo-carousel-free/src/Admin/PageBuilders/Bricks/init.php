<?php
/**
 * Bricks Builder - Initialization
 *
 * Loaded by Manager only when Bricks is active and the integration is enabled.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Bricks;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register the element with Bricks Builder.
 *
 * @return void
 */
function lcpro_bricks_builder_register() {
	if ( ! class_exists( '\Bricks\Elements' ) ) {
		return;
	}

	// The explicit class name keeps Bricks from guessing it from the file.
	\Bricks\Elements::register_element(
		__DIR__ . '/Bricks_Builder.php',
		'lcpro-bricks-logo-carousel',
		__NAMESPACE__ . '\LCPRO_Bricks_Logo_Carousel_Element'
	);
}

// Priority 11 so Bricks is loaded first.
add_action( 'init', __NAMESPACE__ . '\lcpro_bricks_builder_register', 11 );

/**
 * Load the plugin glyph font behind the element's icon class in the Bricks panel.
 *
 * @return void
 */
function lcpro_bricks_enqueue_element_icon() {
	if ( ! function_exists( 'bricks_is_builder_main' ) || ! bricks_is_builder_main() ) {
		return;
	}

	Builder_Icon::enqueue_font();
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\lcpro_bricks_enqueue_element_icon' );
