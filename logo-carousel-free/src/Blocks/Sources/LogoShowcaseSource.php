<?php
/**
 * Logo showcase source normalizer.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sources;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\BlockConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoImagesConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Sources\LogoShowcase\LogoShowcaseItemNormalizer;
use ShapedPlugin\LogoCarouselFree\Blocks\Sources\LogoShowcase\LogoShowcaseRenderDataBuilder;
use ShapedPlugin\LogoCarouselFree\Blocks\Sources\LogoShowcase\ResolvedImageAdapter;
use ShapedPlugin\LogoCarouselFree\Blocks\Sources\LogoImageResolutionResolver;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoShowcaseSource' ) ) {
	/**
	 * Resolve reusable logo showcase items from block attributes.
	 */
	class LogoShowcaseSource implements SourceInterface {
		/**
		 * Return normalized render data for logo showcase output.
		 *
		 * @param array            $attributes Block attributes.
		 * @param BlockConfig|null $config Normalized block config.
		 * @return array<string, mixed>
		 */
		public function get_render_data( array $attributes, ?BlockConfig $config = null ) {
			$render_context = $this->resolve_render_context( $attributes, $config );

			return $this->build_render_data(
				$render_context['items'],
				$render_context['imagesSettings'],
				$render_context['imagesFlags'],
				$render_context['logoImagesStyle']
			);
		}

		/**
		 * Return normalized logo showcase items.
		 *
		 * @param array $attributes Block attributes.
		 * @return array<int, array<string, mixed>>
		 */
		public function get_items( array $attributes ) {
			$render_data = $this->get_render_data( $attributes );

			return isset( $render_data['items'] ) && is_array( $render_data['items'] ) ? $render_data['items'] : array();
		}

		/**
		 * Normalize logo showcase items for rendering.
		 *
		 * @param array<int, array<string, mixed>> $items Raw items.
		 * @param array<string, mixed>             $images_settings Normalized settings.
		 * @return array<int, array<string, mixed>>
		 */
		private function normalize_items( array $items, array $images_settings ) {
			$resolution = isset( $images_settings['resolution'] ) && is_string( $images_settings['resolution'] ) ? $images_settings['resolution'] : 'large';

			$normalized = array();
			foreach ( $items as $index => $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$normalized_item = $this->normalize_item( $item, count( $normalized ), $index, $resolution );

				// Keep missing-media items; only drop genuinely imageless items.
				$is_missing_media = ! empty( $normalized_item['isMediaMissing'] )
					|| 'missing' === (string) ( isset( $normalized_item['mediaStatus'] ) ? $normalized_item['mediaStatus'] : '' );

				if ( '' === (string) $normalized_item['imageUrl'] && ! $is_missing_media ) {
					continue;
				}

				$normalized[] = $normalized_item;
			}

			return $this->sort_normalized_items( $normalized );
		}

		/**
		 * Resolve the data needed to assemble render output.
		 *
		 * @param array<string, mixed> $attributes Block attributes.
		 * @param BlockConfig|null     $config Normalized block config.
		 * @return array<string, mixed>
		 */
		private function resolve_render_context( array $attributes, ?BlockConfig $config = null ) {
			$images_settings = $config ? $config->get_images_settings() : $this->normalize_images_settings( $attributes );

			return array(
				'items'           => $this->extract_source_items( $attributes ),
				'imagesSettings'  => $images_settings,
				'imagesFlags'     => $config ? $config->get_images_flags() : $this->normalize_images_flags( $attributes, $images_settings ),
				'logoImagesStyle' => $config ? $config->get_logo_images_style() : $this->normalize_logo_images_style( $attributes ),
			);
		}

		/**
		 * Assemble render data from normalized context.
		 *
		 * @param array<int, array<string, mixed>> $items Raw source items.
		 * @param array<string, mixed>             $images_settings Normalized image settings.
		 * @param array<string, bool>              $images_flags Derived image flags.
		 * @param array<string, mixed>             $logo_images_style Normalized image style.
		 * @return array<string, mixed>
		 */
		private function build_render_data( array $items, array $images_settings, array $images_flags, array $logo_images_style ) {
			return ( new LogoShowcaseRenderDataBuilder() )->build(
				$this->normalize_items( $items, $images_settings ),
				$images_settings,
				$images_flags,
				$logo_images_style
			);
		}

		/**
		 * Normalize logo images settings for render when config is unavailable.
		 *
		 * @param array<string, mixed> $attributes Block attributes.
		 * @return array<string, mixed>
		 */
		private function normalize_images_settings( $attributes ) {
			return ( new LogoImagesConfig() )->normalize( $attributes );
		}

		/**
		 * Normalize logo images style for render when config is unavailable.
		 *
		 * @param array<string, mixed> $attributes Block attributes.
		 * @return array<string, mixed>
		 */
		private function normalize_logo_images_style( $attributes ) {
			return ( new LogoImagesConfig() )->normalize_logo_images_style( $attributes );
		}

		/**
		 * Normalize logo images flags when config is unavailable.
		 *
		 * @param array<string, mixed> $attributes Block attributes.
		 * @param array<string, mixed> $images_settings Normalized settings.
		 * @return array<string, bool>
		 */
		private function normalize_images_flags( $attributes, array $images_settings ) {
			return ( new LogoImagesConfig() )->derive_flags( $attributes, $images_settings );
		}

		/**
		 * Normalize one logo item.
		 *
		 * @param array  $item Source item.
		 * @param int    $order Fallback order.
		 * @param int    $index Original item index.
		 * @param string $resolution Selected image resolution.
		 * @return array<string, mixed>
		 */
		private function normalize_item( array $item, $order, $index, $resolution = 'large' ) {
			$image_url = $this->sanitize_url( $this->string_value( $item, 'imageUrl' ) );
			$image     = $this->resolve_image_by_resolution( $item, $image_url, $resolution );

			return ( new LogoShowcaseItemNormalizer() )->normalize( $item, $image, $order, $index, $image_url );
		}

		/**
		 * Extract source items from block attributes.
		 *
		 * @param array<string, mixed> $attributes Block attributes.
		 * @return array<int, array<string, mixed>>
		 */
		private function extract_source_items( array $attributes ) {
			$source = isset( $attributes['logoSource'] ) && is_array( $attributes['logoSource'] ) ? $attributes['logoSource'] : array();

			return isset( $source['items'] ) && is_array( $source['items'] ) ? $source['items'] : array();
		}

		/**
		 * Sort normalized items by order and reset indexes.
		 *
		 * @param array<int, array<string, mixed>> $items Normalized items.
		 * @return array<int, array<string, mixed>>
		 */
		private function sort_normalized_items( array $items ) {
			usort(
				$items,
				static function ( $a, $b ) {
					return $a['order'] <=> $b['order'];
				}
			);

			return array_values( $items );
		}

		/**
		 * Resolve the best image URL for the selected resolution.
		 *
		 * @param array  $item Source item.
		 * @param string $fallback_url Fallback image URL.
		 * @param string $resolution Selected resolution.
		 * @return array<string, string>
		 */
		protected function resolve_image_by_resolution( array $item, $fallback_url, $resolution ) {
			$resolved = ( new LogoImageResolutionResolver() )->resolve( $item, $resolution, $fallback_url );
			$resolved = ( new ResolvedImageAdapter() )->adapt( $resolved );

			return $this->filter_resolved_image( $resolved, $item, $resolution );
		}

		/**
		 * Apply the public image-resolution filter when available.
		 *
		 * @param array<string, mixed> $resolved Adapted resolved image data.
		 * @param array<string, mixed> $item Source item.
		 * @param string               $resolution Selected resolution.
		 * @return array<string, mixed>
		 */
		private function filter_resolved_image( array $resolved, array $item, $resolution ) {
			if ( function_exists( 'apply_filters' ) ) {
				$resolved = apply_filters(
					'sp_logo_carousel_pro_logo_image_resolution',
					$resolved,
					$item,
					$resolution
				);
			}

			return $resolved;
		}

		/**
		 * Return a safe string value.
		 *
		 * @param array  $item Source item.
		 * @param string $key Item key.
		 * @param string $fallback_value Default value.
		 * @return string
		 */
		private function string_value( array $item, $key, $fallback_value = '' ) {
			if ( ! isset( $item[ $key ] ) || is_array( $item[ $key ] ) || is_object( $item[ $key ] ) ) {
				return $fallback_value;
			}

			return $this->sanitize_text( (string) $item[ $key ] );
		}

		/**
		 * Sanitize text in a WordPress-safe way with a PHP fallback.
		 *
		 * @param string $value Raw text value.
		 * @return string
		 */
		private function sanitize_text( $value ) {
			if ( function_exists( '\\sanitize_text_field' ) ) {
				return \sanitize_text_field( $value );
			}

			if ( function_exists( '\\wp_strip_all_tags' ) ) {
				$value = \wp_strip_all_tags( (string) $value );
			} else {
				$value = preg_replace( '/<[^>]*>/', '', (string) $value );
			}

			$value = preg_replace( '/[\r\n\t]+/', ' ', $value );

			return trim( (string) $value );
		}

		/**
		 * Sanitize URLs in a WordPress-safe way with a PHP fallback.
		 *
		 * @param string $value Raw URL value.
		 * @return string
		 */
		private function sanitize_url( $value ) {
			$value = trim( (string) $value );

			if ( '' === $value ) {
				return '';
			}

			$scheme = strtolower( $this->parse_url_scheme( $value ) );
			if ( '' !== $scheme && ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
				return '';
			}

			if ( function_exists( '\\esc_url_raw' ) ) {
				return \esc_url_raw( $value );
			}

			return filter_var( $value, FILTER_VALIDATE_URL ) ? $value : '';
		}

		/**
		 * Parse a URL scheme without relying on parse_url().
		 *
		 * @param string $value Raw URL value.
		 * @return string
		 */
		private function parse_url_scheme( $value ) {
			if ( function_exists( '\\wp_parse_url' ) ) {
				return (string) \wp_parse_url( $value, PHP_URL_SCHEME );
			}

			if ( preg_match( '/^([a-zA-Z][a-zA-Z0-9+.-]*):/', (string) $value, $matches ) ) {
				return isset( $matches[1] ) ? (string) $matches[1] : '';
			}

			return '';
		}
	}
}
