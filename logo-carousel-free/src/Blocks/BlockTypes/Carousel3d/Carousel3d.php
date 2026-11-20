<?php
/**
 * 3D Carousel block type.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\Carousel3d;

use ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\LogoCarousel\LogoCarouselBlock;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Carousel3dSchema;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\Carousel3d', false ) ) {
	/**
	 * Dynamic 3D Carousel block — Coverflow, Perspective and Ring styles.
	 *
	 * Runs on the shared Swiper render baseline (arrows, dots, autoplay, keyboard,
	 * mousewheel); the 3D stage itself is drawn by the shared `carousel3d`
	 * effect engine from the `carousel3d` payload in the Swiper config.
	 */
	class Carousel3d extends LogoCarouselBlock {
		/**
		 * Block slug.
		 *
		 * @var string
		 */
		protected $block_name = 'carousel3d';

		/**
		 * Return the block schema.
		 *
		 * @return Carousel3dSchema
		 */
		protected function get_schema() {
			return new Carousel3dSchema();
		}
	}
}
