<?php
/**
 * Carousel block type.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\Carousel;

use ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\LogoCarousel\LogoCarouselBlock;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\CarouselSchema;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Carousel' ) ) {
	/**
	 * Dynamic Carousel block for boilerplate verification.
	 */
	class Carousel extends LogoCarouselBlock {
		/**
		 * Block slug.
		 *
		 * @var string
		 */
		protected $block_name = 'carousel';

		/**
		 * Return the block schema.
		 *
		 * @return CarouselSchema
		 */
		protected function get_schema() {
			return new CarouselSchema();
		}
	}
}
