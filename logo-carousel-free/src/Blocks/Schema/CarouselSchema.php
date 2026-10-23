<?php
/**
 * Schema for the Carousel block.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase\LayoutAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase\LogoShowcaseAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase\NavigationAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase\PaginationAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\SectionRegistry;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Util\DeepMerge;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CarouselSchema' ) ) {
	/**
	 * Carousel schema.
	 */
	class CarouselSchema extends BaseSchema {
		/**
		 * Return the Carousel attribute schema.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public function get_attributes(): array {
			$attributes = parent::get_attributes();
			$attributes = DeepMerge::recursive( $attributes, LayoutAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, NavigationAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, PaginationAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, LogoShowcaseAttributeGroup::definition() );

			foreach ( SectionRegistry::schema_definitions() as $section_definition ) {
				$attributes = DeepMerge::recursive( $attributes, $section_definition );
			}

			return $attributes;
		}
	}
}
