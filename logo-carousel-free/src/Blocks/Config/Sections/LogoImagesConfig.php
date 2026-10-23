<?php
/**
 * Logo Images section config normalizer.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase\LogoImagesFlagsConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase\LogoImagesSettingsConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase\LogoImagesStyleConfig;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoImagesConfig' ) ) {
	/**
	 * Normalize and derive config for the Logo Images section.
	 */
	class LogoImagesConfig implements SectionConfigInterface {
		/**
		 * Normalize the section settings.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @return array<string, mixed>
		 */
		public function normalize( array $attributes ) {
			return ( new LogoImagesSettingsConfig() )->normalize( $attributes );
		}

		/**
		 * Normalize the logo images style branch.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @return array<string, mixed>
		 */
		public function normalize_logo_images_style( array $attributes ) {
			return ( new LogoImagesStyleConfig() )->normalize( $attributes );
		}

		/**
		 * Derive section flags from the normalized settings and raw source data.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @param array<string, mixed> $images_settings Normalized settings.
		 * @return array<string, bool>
		 */
		public function derive_flags( array $attributes, array $images_settings ) {
			return ( new LogoImagesFlagsConfig() )->derive( $attributes, $images_settings );
		}
	}
}
