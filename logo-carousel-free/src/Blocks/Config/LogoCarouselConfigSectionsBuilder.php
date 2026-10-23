<?php
/**
 * Build shared config sections for the logo carousel family.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoImagesConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\SwiperConfig;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoCarouselConfigSectionsBuilder' ) ) {
	/**
	 * Build normalized section payloads for logo carousel family blocks.
	 */
	class LogoCarouselConfigSectionsBuilder {
		/**
		 * Build normalized section payloads.
		 *
		 * Logo images normalization is Swiper-independent. The `swiperConfig`
		 * payload is only produced when the block uses the Swiper engine, so a
		 * non-Swiper family block (e.g. Grid) reuses logo images without a dead
		 * Swiper payload.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @param bool                 $uses_swiper Whether to normalize the Swiper config. Defaults true for back-compat.
		 * @return array<string, mixed>
		 */
		public function build( array $attributes, $uses_swiper = true ) {
			$logo_images       = new LogoImagesConfig();
			$images_settings   = $logo_images->normalize( $attributes );
			$images_flags      = $logo_images->derive_flags( $attributes, $images_settings );
			$logo_images_style = $logo_images->normalize_logo_images_style( $attributes );

			$payloads = array(
				'imagesSettings'  => $images_settings,
				'imagesFlags'     => $images_flags,
				'logoImagesStyle' => $logo_images_style,
			);

			if ( $uses_swiper ) {
				$payloads['swiperConfig'] = ( new SwiperConfig() )->normalize( $attributes );
			}

			return $payloads;
		}

		/**
		 * Merge normalized section payloads back into the attribute bag.
		 *
		 * @param array<string, mixed> $attributes Raw or partially normalized attributes.
		 * @param array<string, mixed> $section_payloads Normalized section payloads.
		 * @return array<string, mixed>
		 */
		public function merge_into_attributes( array $attributes, array $section_payloads ) {
			$attributes['imagesSettings']  = isset( $section_payloads['imagesSettings'] ) ? $section_payloads['imagesSettings'] : array();
			$attributes['logoImagesStyle'] = isset( $section_payloads['logoImagesStyle'] ) ? $section_payloads['logoImagesStyle'] : array();

			// Only merge the Swiper payload for Swiper-driven blocks; a non-Swiper
			// block never produces it, and must not have an empty stub written back.
			if ( isset( $section_payloads['swiperConfig'] ) ) {
				$attributes['swiperConfig'] = $section_payloads['swiperConfig'];
			}

			if ( isset( $attributes['logoImageStyle'] ) && is_array( $attributes['logoImageStyle'] ) ) {
				$attributes['logoImageStyle'] = isset( $section_payloads['logoImagesStyle'] ) ? $section_payloads['logoImagesStyle'] : array();
			}

			return $attributes;
		}
	}
}
