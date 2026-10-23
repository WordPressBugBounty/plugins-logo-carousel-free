<?php
/**
 * Schema for the Grid block.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase\LogoShowcaseAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\Grid\GridLayoutAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\SectionRegistry;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\GridPagination\GridPaginationSchema;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Util\DeepMerge;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GridSchema' ) ) {
	/**
	 * Grid schema.
	 *
	 * Shares the base + logo-showcase (source + images) foundation with Carousel,
	 * plus the always-on registry sections. It deliberately omits the Swiper-bound
	 * Navigation/Pagination groups (Grid is a static grid, not a slider). Its own
	 * profile-shaped Layout branch (standard / strip / staggered / zigzag) and the
	 * page-based Grid Pagination branch (`gridPagination` — Grid-only, composed
	 * here rather than via `SectionRegistry` since carousel has no page
	 * model) are both landed; the Staggered/Zigzag layout styles and the
	 * Pagination config/render/CSS/runtime layers land in later phases.
	 */
	class GridSchema extends BaseSchema {
		/**
		 * Return the Grid attribute schema.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public function get_attributes(): array {
			$attributes = parent::get_attributes();
			$attributes = DeepMerge::recursive( $attributes, LogoShowcaseAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, GridLayoutAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, GridPaginationSchema::definition() );

			foreach ( SectionRegistry::schema_definitions() as $section_definition ) {
				$attributes = DeepMerge::recursive( $attributes, $section_definition );
			}

			return $attributes;
		}
	}
}
