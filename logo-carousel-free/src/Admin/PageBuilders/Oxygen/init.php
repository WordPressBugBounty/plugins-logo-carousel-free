<?php
/**
 * Oxygen Builder - Initialization
 *
 * Loaded by Manager only when Oxygen is active and the integration is enabled.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Oxygen;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register the element with Oxygen Builder.
 *
 * @return void
 */
function lcpro_oxygen_builder_register() {
	if ( ! class_exists( 'OxyEl' ) ) {
		return;
	}

	require_once __DIR__ . '/Oxygen_Builder.php';

	new LCPRO_Oxygen_Logo_Carousel_Element();
}

// Priority 11, after Manager's own init work at priority 10.
add_action( 'init', __NAMESPACE__ . '\lcpro_oxygen_builder_register', 11 );
