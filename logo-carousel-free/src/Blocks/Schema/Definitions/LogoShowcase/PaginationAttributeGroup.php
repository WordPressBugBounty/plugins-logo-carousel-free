<?php
/**
 * Logo showcase pagination dots attribute group.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\CommonAttributeGroups;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PaginationAttributeGroup' ) ) {
	/**
	 * Shared pagination dots attributes for the logo showcase family.
	 */
	class PaginationAttributeGroup {
		/**
		 * Return the shared pagination attribute definition.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function definition() {
			return CommonAttributeGroups::pagination();
		}
	}
}
