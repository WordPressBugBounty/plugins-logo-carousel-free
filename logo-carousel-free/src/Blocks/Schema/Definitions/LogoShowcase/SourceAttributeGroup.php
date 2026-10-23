<?php
/**
 * Logo showcase source attribute group.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\CommonAttributeGroups;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SourceAttributeGroup' ) ) {
	/**
	 * Shared source attributes for the logo showcase family.
	 */
	class SourceAttributeGroup {
		/**
		 * Return the shared source attribute definition.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function definition() {
			return CommonAttributeGroups::logo_showcase_source();
		}
	}
}
