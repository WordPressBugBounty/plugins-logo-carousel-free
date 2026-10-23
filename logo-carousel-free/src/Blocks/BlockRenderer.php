<?php
/**
 * Shared renderer for demo blocks in the native block app.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks;

use ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\LogoCarousel\LogoCarouselRenderPipeline;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\BlockConfigBuilder;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\BlockShell;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BlockRenderer' ) ) {
	/**
	 * Render demo block markup from native block attributes only.
	 */
	class BlockRenderer {
		/**
		 * Shared config builder.
		 *
		 * @var BlockConfigBuilder
		 */
		private $config_builder;

		/**
		 * Shared shell renderer.
		 *
		 * @var BlockShell
		 */
		private $block_shell;

		/**
		 * Shared logo carousel family render pipeline.
		 *
		 * @var LogoCarouselRenderPipeline
		 */
		private $logo_carousel_render_pipeline;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->config_builder               = new BlockConfigBuilder();
			$this->block_shell                  = new BlockShell();
			$this->logo_carousel_render_pipeline = new LogoCarouselRenderPipeline( $this->config_builder, $this->block_shell );
		}

		/**
		 * Render the demo Carousel block.
		 *
		 * @param array $attributes Block attributes.
		 * @return string
		 */
		public function render_carousel( array $attributes ) {
			return $this->render_logo_carousel_block(
				'carousel',
				$attributes,
				array(
					'logoSource',
					'logoImages',
					'navigation',
					'pagination',
					'logoCard',
				),
				array(
					'uses_logo_showcase_schema'   => true,
					'uses_logo_showcase_renderer' => true,
				)
			);
		}

		/**
		 * Render a shared logo carousel family block.
		 *
		 * @param string               $slug Block slug.
		 * @param array<string, mixed> $attributes Block attributes.
		 * @param array<int, string>|null $features Optional block feature manifest.
		 * @param array<string, bool>  $capabilities Optional block family capabilities.
		 * @return string
		 */
		public function render_logo_carousel_block( $slug, array $attributes, ?array $features = null, array $capabilities = array() ) {
			return $this->logo_carousel_render_pipeline->render( $slug, $attributes, $features, $capabilities );
		}
	}
}
