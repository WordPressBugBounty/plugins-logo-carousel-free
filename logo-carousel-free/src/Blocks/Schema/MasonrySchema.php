<?php
/**
 * Schema for the Masonry block.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase\LogoShowcaseAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\Masonry\MasonryLayoutAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\SectionRegistry;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\GridPagination\GridPaginationSchema;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Util\DeepMerge;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\MasonrySchema', false ) ) {
	/**
	 * Masonry schema.
	 *
	 * Same composition as Grid / List: base + logo-showcase (source + images) +
	 * the block's own Layout branch + the reused page-based `gridPagination`
	 * branch + the always-on registry sections. No Swiper-bound groups.
	 */
	class MasonrySchema extends BaseSchema {
		/**
		 * Return the Masonry attribute schema.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public function get_attributes(): array {
			$attributes = parent::get_attributes();
			$attributes = DeepMerge::recursive( $attributes, LogoShowcaseAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, MasonryLayoutAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, GridPaginationSchema::definition() );

			foreach ( SectionRegistry::schema_definitions() as $section_definition ) {
				$attributes = DeepMerge::recursive( $attributes, $section_definition );
			}

			return $attributes;
		}
	}
}
