<?php
/**
 * Build normalized block config from native attributes.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config;

use ShapedPlugin\LogoCarouselFree\Blocks\Support\UniqueId;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BlockConfigBuilder' ) ) {
	/**
	 * Build shared block config for demo/native block rendering.
	 *
	 * This class stays thin and delegates section-specific normalization to
	 * dedicated section config classes.
	 */
	class BlockConfigBuilder {
		/**
		 * Shared logo carousel section builder.
		 *
		 * @var LogoCarouselConfigSectionsBuilder
		 */
		private $logo_carousel_sections_builder;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->logo_carousel_sections_builder = new LogoCarouselConfigSectionsBuilder();
		}

		/**
		 * Build a normalized config object.
		 *
		 * @param string             $slug Block slug.
		 * @param array              $attributes Raw attributes.
		 * @param array<string, bool> $capabilities Optional block family capabilities.
		 * @return BlockConfig
		 */
		public function build( $slug, array $attributes, array $capabilities = array() ) {
			$defaults              = $this->get_defaults();
			$content_state         = $this->resolve_content_state( $attributes, $defaults );
			$unique_id             = UniqueId::from_attributes( $attributes );
			$normalized_attributes = $attributes;
			$images_settings       = array();
			$images_flags          = array();
			$logo_images_style     = array();
			$swiper_config         = array();
			$uses_swiper           = $this->uses_swiper( $capabilities );

			if ( $this->uses_logo_carousel_sections( $slug, $capabilities ) ) {
				$section_payloads      = $this->logo_carousel_sections_builder->build( $attributes, $uses_swiper );
				$images_settings       = isset( $section_payloads['imagesSettings'] ) && is_array( $section_payloads['imagesSettings'] ) ? $section_payloads['imagesSettings'] : array();
				$images_flags          = isset( $section_payloads['imagesFlags'] ) && is_array( $section_payloads['imagesFlags'] ) ? $section_payloads['imagesFlags'] : array();
				$logo_images_style     = isset( $section_payloads['logoImagesStyle'] ) && is_array( $section_payloads['logoImagesStyle'] ) ? $section_payloads['logoImagesStyle'] : array();
				$swiper_config         = isset( $section_payloads['swiperConfig'] ) && is_array( $section_payloads['swiperConfig'] ) ? $section_payloads['swiperConfig'] : array();
				$normalized_attributes = $this->logo_carousel_sections_builder->merge_into_attributes( $normalized_attributes, $section_payloads );
			}

			return new BlockConfig(
				$slug,
				$content_state['heading'],
				$content_state['caption'],
				$unique_id,
				$normalized_attributes,
				$images_settings,
				$images_flags,
				$logo_images_style,
				$swiper_config,
				$uses_swiper
			);
		}

		/**
		 * Shared defaults for the demo/native architecture.
		 *
		 * @return array<string, string>
		 */
		private function get_defaults() {
			return array(
				'heading' => __( 'Carousel', 'logo-carousel-free' ),
				'caption' => __( 'Carousel block is working.', 'logo-carousel-free' ),
			);
		}

		/**
		 * Resolve normalized heading and caption state.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @param array<string, string> $defaults Shared defaults.
		 * @return array<string, string>
		 */
		private function resolve_content_state( array $attributes, array $defaults ) {
			$content             = isset( $attributes['content'] ) && is_array( $attributes['content'] ) ? $attributes['content'] : array();
			$has_content_heading = array_key_exists( 'heading', $content );
			$has_content_caption = array_key_exists( 'caption', $content );
			$has_heading_attr    = array_key_exists( 'heading', $attributes );
			$has_caption_attr    = array_key_exists( 'caption', $attributes );
			$heading             = isset( $content['heading'] ) ? trim( (string) $content['heading'] ) : '';
			$caption             = isset( $content['caption'] ) ? trim( (string) $content['caption'] ) : '';

			if ( '' === $heading && isset( $attributes['heading'] ) ) {
				$heading = trim( (string) $attributes['heading'] );
			}

			if ( '' === $caption && isset( $attributes['caption'] ) ) {
				$caption = trim( (string) $attributes['caption'] );
			}

			if ( '' === $heading && ! $has_content_heading && ! $has_heading_attr ) {
				$heading = $defaults['heading'];
			}

			if ( '' === $caption && ! $has_content_caption && ! $has_caption_attr ) {
				$caption = $defaults['caption'];
			}

			return array(
				'heading' => $heading,
				'caption' => $caption,
			);
		}

		/**
		 * Determine whether the block slug uses the shared logo carousel section builders.
		 *
		 * @param string             $slug Block slug.
		 * @param array<string, bool> $capabilities Block family capabilities.
		 * @return bool
		 */
		private function uses_logo_carousel_sections( $slug, array $capabilities ) {
			if ( ! empty( $capabilities['uses_logo_showcase_schema'] ) ) {
				return true;
			}

			return in_array( $slug, array( 'carousel' ), true );
		}

		/**
		 * Determine whether the block uses the Swiper engine.
		 *
		 * Defaults to true so legacy/slug-based callers keep Swiper behavior. A
		 * block opts out only by declaring `uses_swiper => false` in its family
		 * capabilities (e.g. Grid).
		 *
		 * @param array<string, bool> $capabilities Block family capabilities.
		 * @return bool
		 */
		private function uses_swiper( array $capabilities ) {
			return ! array_key_exists( 'uses_swiper', $capabilities ) || ! empty( $capabilities['uses_swiper'] );
		}
	}
}
