<?php
/**
 * Schema for the 3D Carousel block.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\Carousel3d\Carousel3dAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase\LayoutAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase\LogoShowcaseAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase\NavigationAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\LogoShowcase\PaginationAttributeGroup;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\SectionRegistry;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Util\DeepMerge;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\Carousel3dSchema', false ) ) {
	/**
	 * 3D Carousel schema.
	 *
	 * The same groups as the Carousel schema (layout, navigation, pagination,
	 * logo showcase, shared sections) — so every shared panel and the shared
	 * Swiper config work unchanged — plus the `carousel3d` branch. Autoplay
	 * starts off and cards default to 4:3, as in the design.
	 */
	class Carousel3dSchema extends BaseSchema {
		/**
		 * Return the 3D Carousel attribute schema.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public function get_attributes(): array {
			$attributes = parent::get_attributes();
			$attributes = DeepMerge::recursive( $attributes, LayoutAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, NavigationAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, PaginationAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, LogoShowcaseAttributeGroup::definition() );
			$attributes = DeepMerge::recursive( $attributes, Carousel3dAttributeGroup::definition() );

			foreach ( SectionRegistry::schema_definitions() as $section_definition ) {
				$attributes = DeepMerge::recursive( $attributes, $section_definition );
			}

			// Autoplay is profile-shaped like every carousel layout field: the
			// flat key mirrors `shared`, so both defaults change together.
			$attributes['layout']['default']['autoplay']           = false;
			$attributes['layout']['default']['shared']['autoplay'] = false;

			if ( isset( $attributes['layout']['properties']['shared']['default'] ) ) {
				$attributes['layout']['properties']['shared']['default']['autoplay'] = false;
			}

			// 3D cards overlap by design, so they read as uniform 4:3 cards (the
			// design's card ratio) out of the box instead of hugging each logo.
			if ( isset( $attributes['imagesSettings']['default'] ) && is_array( $attributes['imagesSettings']['default'] ) ) {
				$attributes['imagesSettings']['default']['aspectRatio'] = '4:3';
			}

			return $attributes;
		}
	}
}
