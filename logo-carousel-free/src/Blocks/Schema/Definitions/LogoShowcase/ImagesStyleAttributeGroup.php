<?php
/**
 * Logo showcase image style attribute group.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoImagesAttributeGroup;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ImagesStyleAttributeGroup' ) ) {
	/**
	 * Shared image style attributes for the logo showcase family.
	 */
	class ImagesStyleAttributeGroup {
		/**
		 * Return the shared image style attribute definition.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function definition() {
			return LogoImagesAttributeGroup::logo_images_style_definition();
		}
	}
}
