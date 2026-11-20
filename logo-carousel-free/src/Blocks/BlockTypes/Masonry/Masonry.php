<?php
/**
 * Masonry block type.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\Masonry;

use ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\LogoCarousel\LogoCarouselBlock;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\MasonrySchema;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\Masonry', false ) ) {
	/**
	 * Static Masonry block — columns of logos at their own heights, each logo
	 * dropped into the shortest column.
	 *
	 * Reuses the shared logo-showcase schema/config/source foundation on the
	 * non-Swiper render baseline, like Grid. The server renders
	 * an even CSS grid (`MasonryLayoutCss`); the shared masonry layout
	 * (`shared/masonry/masonryLayout.js`) then packs it into columns.
	 */
	class Masonry extends LogoCarouselBlock {
		/**
		 * Block slug.
		 *
		 * @var string
		 */
		protected $block_name = 'masonry';

		/**
		 * Block-core feature set (core tokens only).
		 *
		 * Always-on section tokens are merged in from SectionRegistry; the
		 * pipeline appends conditional tokens (gridPagination, ajax*) from
		 * attributes. No Swiper navigation/pagination.
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
		 * @return MasonrySchema
		 */
		protected function get_schema() {
			return new MasonrySchema();
		}
	}
}
