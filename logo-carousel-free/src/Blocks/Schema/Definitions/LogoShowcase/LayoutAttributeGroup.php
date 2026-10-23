<?php
/**
 * Logo showcase layout attribute group.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\CommonAttributeGroups;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LayoutAttributeGroup' ) ) {
	/**
	 * Shared layout attributes for the logo showcase family.
	 */
	class LayoutAttributeGroup {
		/**
		 * Return the shared layout attribute definition.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function definition() {
			return CommonAttributeGroups::layout();
		}
	}
}
