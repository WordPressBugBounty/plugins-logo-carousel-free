<?php
/**
 * Logo showcase navigation arrows attribute group.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\CommonAttributeGroups;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'NavigationAttributeGroup' ) ) {
	/**
	 * Shared navigation arrows attributes for the logo showcase family.
	 */
	class NavigationAttributeGroup {
		/**
		 * Return the shared navigation attribute definition.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function definition() {
			return CommonAttributeGroups::navigation();
		}
	}
}
