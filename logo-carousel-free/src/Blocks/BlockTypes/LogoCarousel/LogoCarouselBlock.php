<?php
/**
 * Shared thin wrapper for the logo carousel block family.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\LogoCarousel;

use ShapedPlugin\LogoCarouselFree\Blocks\AbstractBlock;
use ShapedPlugin\LogoCarouselFree\Blocks\BlockRenderer;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\SectionRegistry;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoCarouselBlock' ) ) {
	/**
	 * Shared family wrapper for logo showcase-style blocks.
	 */
	abstract class LogoCarouselBlock extends AbstractBlock {
		/**
		 * Block-specific feature set.
		 *
		 * Always-on section tokens are NOT listed here; they are merged in from
		 * SectionRegistry by get_supported_features() so the registry stays the
		 * single source of truth (adding an always-on section flows in
		 * automatically, no per-block edit).
		 *
		 * @var array<int, string>
		 */
		protected $supported_features = array(
			'logoSource',
			'logoImages',
			'navigation',
			'pagination',
		);

		/**
		 * Shared family capability flags.
		 *
		 * @var array<string, bool>
		 */
		protected $family_capabilities = array(
			'uses_logo_showcase_schema'   => true,
			'uses_logo_showcase_renderer' => true,
			'uses_swiper'                 => true,
		);

		/**
		 * Render the block.
		 *
		 * @param array<string, mixed>|mixed $attributes Block attributes.
		 * @param string                     $content Block content.
		 * @return string
		 */
		public function render( $attributes, $content = '' ) {
			unset( $content );

			return $this->get_block_renderer()->render_logo_carousel_block(
				$this->get_logo_carousel_family_slug(),
				is_array( $attributes ) ? $attributes : array(),
				$this->get_supported_features(),
				$this->get_family_capabilities()
			);
		}

		/**
		 * Return the family block slug used by the shared pipeline.
		 *
		 * @return string
		 */
		protected function get_logo_carousel_family_slug() {
			return $this->block_name;
		}

		/**
		 * Return the shared family feature set.
		 *
		 * @return array<int, string>
		 */
		protected function get_supported_features() {
			return array_values(
				array_unique(
					array_merge( $this->supported_features, SectionRegistry::always_on_css_tokens() )
				)
			);
		}

		/**
		 * Return the shared family capability flags.
		 *
		 * @return array<string, bool>
		 */
		protected function get_family_capabilities() {
			return $this->family_capabilities;
		}

		/**
		 * Return the renderer used by thin family wrappers.
		 *
		 * @return BlockRenderer
		 */
		protected function get_block_renderer() {
			return new BlockRenderer();
		}
	}
}
