<?php
/**
 * Shared render pipeline for the logo carousel block family.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\BlockTypes\LogoCarousel;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\BlockConfigBuilder;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\BlockConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\BlockShell;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\CarouselFreeGate;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\GridFreeGate;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoShowcaseRenderer;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\ModuleGate;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\SectionRegistry;
use ShapedPlugin\LogoCarouselFree\Blocks\Sources\LogoShowcaseSource;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CarouselFamilyDynamicCss;
use ShapedPlugin\LogoCarouselFree\Includes\AssetManager;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoCarouselRenderPipeline' ) ) {
	/**
	 * Render shared logo carousel family blocks.
	 */
	class LogoCarouselRenderPipeline {
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
		 * Source facade.
		 *
		 * @var LogoShowcaseSource
		 */
		private $source;

		/**
		 * Markup renderer.
		 *
		 * @var LogoShowcaseRenderer
		 */
		private $renderer;

		/**
		 * Dynamic CSS generator.
		 *
		 * @var CarouselFamilyDynamicCss
		 */
		private $dynamic_css_generator;

		/**
		 * Constructor.
		 *
		 * @param BlockConfigBuilder|null       $config_builder Optional config builder.
		 * @param BlockShell|null               $block_shell Optional shell renderer.
		 * @param LogoShowcaseSource|null       $source Optional source facade.
		 * @param LogoShowcaseRenderer|null     $renderer Optional markup renderer.
		 * @param CarouselFamilyDynamicCss|null $dynamic_css_generator Optional CSS generator.
		 */
		public function __construct( ?BlockConfigBuilder $config_builder = null, ?BlockShell $block_shell = null, ?LogoShowcaseSource $source = null, ?LogoShowcaseRenderer $renderer = null, ?CarouselFamilyDynamicCss $dynamic_css_generator = null ) {
			$this->config_builder        = $config_builder ?: new BlockConfigBuilder();
			$this->block_shell           = $block_shell ?: new BlockShell();
			$this->source                = $source ?: new LogoShowcaseSource();
			$this->renderer              = $renderer ?: new LogoShowcaseRenderer();
			$this->dynamic_css_generator = $dynamic_css_generator ?: new CarouselFamilyDynamicCss();
		}

		/**
		 * Render a logo carousel family block.
		 *
		 * @param string                  $slug Block slug.
		 * @param array<string, mixed>    $attributes Block attributes.
		 * @param array<int, string>|null $features Shared feature tokens; null uses the family default.
		 * @param array<string, bool>     $capabilities Shared family capabilities.
		 * @return string
		 */
		public function render( $slug, array $attributes, ?array $features = null, array $capabilities = array() ) {
			if ( null === $features ) {
				$features = $this->default_features();
			}

			// Neutralize Pro-only and dashboard-disabled modules on a render copy
			// (never saves), so Pro-saved glass values render neutrally while the
			// attributes themselves round-trip untouched.
			$attributes = ModuleGate::apply( $attributes );

			// Free build: the styles, layout options and logo effects that are Pro
			// render neutrally, on the same render copy.
			if ( 'carousel' === $slug ) {
				$attributes = CarouselFreeGate::apply( $attributes );
			} elseif ( 'grid' === $slug ) {
				$attributes = GridFreeGate::apply( $attributes );
			}

			AssetManager::instance()->enqueue_block_frontend_assets();

			$config = $this->build_block_config( $slug, $attributes, $capabilities );

			// Display Elements text, Tooltips, Ajax Filter/Search and Grid
			// Pagination are Pro: a Pro-saved typeface on any of them never
			// renders here, so it must not load a font either.
			AssetManager::instance()->enqueue_google_fonts(
				array_diff_key( $config->get_attributes(), array_flip( array( 'displayElements', 'tooltips', 'ajaxFilter', 'ajaxSearch', 'gridPagination' ) ) )
			);

			if ( $config->has_images() && ! empty( $config->get_images_flags()['uses_popup_click_action'] ) ) {
				AssetManager::instance()->enqueue_popup_assets();
				$features[] = 'logoInfoPopup';
			}

			$render_data = $this->source->get_render_data( $attributes, $config );
			$markup      = $this->renderer->render_from_data( $render_data, $config );

			$dynamic_css = $this->dynamic_css_generator->generate(
				$config->get_attributes(),
				array(
					'blockType' => $slug,
					'features'  => array_values( array_unique( $features ) ),
				)
			);

			return $this->block_shell->render( $config, $markup, '', $dynamic_css );
		}

		/** Default feature tokens for a family render. */
		private function default_features() {
			return array_merge(
				array( 'logoSource', 'logoImages', 'navigation', 'pagination' ),
				SectionRegistry::always_on_css_tokens()
			);
		}

		/**
		 * Build the normalized block config for the render pipeline.
		 *
		 * @param string               $slug Block slug.
		 * @param array<string, mixed> $attributes Block attributes.
		 * @param array<string, bool>  $capabilities Shared family capabilities.
		 * @return BlockConfig
		 */
		private function build_block_config( $slug, array $attributes, array $capabilities ) {
			$block_attributes            = $attributes;
			$block_attributes['heading'] = '';
			$block_attributes['caption'] = '';

			return $this->config_builder->build( $slug, $block_attributes, $capabilities );
		}

	}
}
