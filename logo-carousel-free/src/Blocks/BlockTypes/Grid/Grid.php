<?php
/**
 * Grid block type.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\Grid;

use ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\LogoCarousel\LogoCarouselBlock;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\GridSchema;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Grid' ) ) {
	/**
	 * Static Grid block — paginated multi-column sibling of Carousel.
	 *
	 * Reuses the shared logo-showcase schema/config/source foundation but is NOT a
	 * slider: it runs on the non-Swiper render baseline (no prev/next arrows, no
	 * bullet pagination). Layout is a static CSS grid (Standard Grid; the other
	 * grid styles and pagination are Pro).
	 */
	class Grid extends LogoCarouselBlock {
		/**
		 * Block slug.
		 *
		 * @var string
		 */
		protected $block_name = 'grid';

		/**
		 * Block-core feature set (core tokens only).
		 *
		 * Always-on section tokens are merged in from SectionRegistry by
		 * get_supported_features(); conditional tokens are appended by the pipeline
		 * from attributes. Grid is a static grid, so it does NOT declare
		 * navigation/pagination (Swiper-only). Grid's own page pagination is a
		 * separate, block-specific section wired in a later phase.
		 *
		 * @var array<int, string>
		 */
		protected $supported_features = array(
			'logoSource',
			'logoImages',
		);

		/**
		 * Shared family capability flags.
		 *
		 * Grid uses the shared schema/config/source foundation but not the Swiper
		 * render shell — uses_swiper is false so the shared renderer emits a plain
		 * logo list with no Swiper chrome or swiperConfig payload.
		 *
		 * @var array<string, bool>
		 */
		protected $family_capabilities = array(
			'uses_logo_showcase_schema'   => true,
			'uses_logo_showcase_renderer' => true,
			'uses_swiper'                 => false,
		);

		/**
		 * Return the block schema.
		 *
		 * @return GridSchema
		 */
		protected function get_schema() {
			return new GridSchema();
		}
	}
}
