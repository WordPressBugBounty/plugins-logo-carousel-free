<?php
/**
 * Logo showcase image settings attribute group.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoImagesAttributeGroup;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ImagesSettingsAttributeGroup' ) ) {
	/**
	 * Shared image settings attributes for the logo showcase family.
	 */
	class ImagesSettingsAttributeGroup {
		/**
		 * Return the shared images settings attribute definition.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function definition() {
			return LogoImagesAttributeGroup::images_settings_definition();
		}
	}
}
