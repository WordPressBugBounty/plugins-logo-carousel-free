<?php
/**
 * Composite logo showcase attribute group.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoShowcaseAttributeGroup' ) ) {
	/**
	 * Compose source and image-related schema groups for the logo showcase family.
	 */
	class LogoShowcaseAttributeGroup {
		/**
		 * Return the composed logo showcase attribute definition.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function definition() {
			return array_merge(
				SourceAttributeGroup::definition(),
				ImagesSettingsAttributeGroup::definition(),
				ImagesStyleAttributeGroup::definition()
			);
		}
	}
}
