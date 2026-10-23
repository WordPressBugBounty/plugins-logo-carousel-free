<?php
/**
 * Build Swiper module lists.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SwiperModulesConfig' ) ) {
	class SwiperModulesConfig {
		public function build( array $swiper ) {
			$modules = array( 'a11y' );
			if ( ! empty( $swiper['autoplay']['enabled'] ) ) { $modules[] = 'autoplay'; }
			if ( ! empty( $swiper['navigation']['enabled'] ) ) { $modules[] = 'navigation'; }
			if ( ! empty( $swiper['pagination']['enabled'] ) ) { $modules[] = 'pagination'; }
			if ( ! empty( $swiper['scrollbar']['enabled'] ) ) { $modules[] = 'scrollbar'; }
			if ( ! empty( $swiper['keyboard']['enabled'] ) ) { $modules[] = 'keyboard'; }
			if ( ! empty( $swiper['mousewheel']['enabled'] ) ) { $modules[] = 'mousewheel'; }
			if ( ! empty( $swiper['freeMode']['enabled'] ) ) { $modules[] = 'freeMode'; }
			return array_values( array_unique( $modules ) );
		}
	}
}
