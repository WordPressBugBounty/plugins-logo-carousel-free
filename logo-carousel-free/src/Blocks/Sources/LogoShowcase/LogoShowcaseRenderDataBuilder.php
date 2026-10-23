<?php
/**
 * Assemble logo showcase render data payloads.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sources\LogoShowcase;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoShowcaseRenderDataBuilder' ) ) {
	/**
	 * Build render data arrays for logo showcase sources.
	 */
	class LogoShowcaseRenderDataBuilder {
		/**
		 * Build the final render data payload.
		 *
		 * @param array<int, array<string, mixed>> $items Normalized items.
		 * @param array<string, mixed>             $images_settings Normalized image settings.
		 * @param array<string, bool>              $images_flags Derived image flags.
		 * @param array<string, mixed>             $logo_images_style Normalized image style.
		 * @return array<string, mixed>
		 */
		public function build( array $items, array $images_settings, array $images_flags, array $logo_images_style ) {
			return array(
				'items'           => $items,
				'imagesSettings'  => $images_settings,
				'imagesFlags'     => $images_flags,
				'logoImagesStyle' => $logo_images_style,
			);
		}
	}
}
