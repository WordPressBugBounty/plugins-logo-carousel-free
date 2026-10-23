<?php
/**
 * Base schema for the demo carousel-family blocks.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\CommonAttributeGroups;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Util\DeepMerge;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BaseSchema' ) ) {
	/**
	 * Shared schema base for related blocks.
	 */
	class BaseSchema implements AttributeSchema {
		/**
		 * Return the shared base attributes.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public function get_attributes(): array {
			$attributes = array(
				'uniqueId'  => Factories\AttributeFactory::string(),
				// Set only by `block.json` `example.attributes`, so the inserter
				// preview renders a static illustration instead of mounting the
				// live editor. Never written by a control, never rendered.
				'isPreview' => Factories\AttributeFactory::boolean( false ),
			);

			$attributes = DeepMerge::recursive( $attributes, CommonAttributeGroups::section() );
			$attributes = DeepMerge::recursive( $attributes, CommonAttributeGroups::visibility() );
			$attributes = DeepMerge::recursive( $attributes, CommonAttributeGroups::advanced() );

			return $attributes;
		}
	}
}
