<?php
/**
 * Normalize one logo showcase item into render-ready data.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sources\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Support\RatingStars;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoShowcaseItemNormalizer' ) ) {
	/**
	 * Normalize one logo showcase item.
	 */
	class LogoShowcaseItemNormalizer {
		/**
		 * Maximum field lengths.
		 *
		 * @var array<string, int>
		 */
		private $field_limits = array(
			'title'       => 120,
			'caption'     => 200,
			'tagline'     => 80,
			'description' => 500,
			'category'    => 80,
			'tooltipText' => 200,
			'altText'     => 125,
		);

		/**
		 * Maximum categories a single logo may carry.
		 *
		 * @var int
		 */
		private $max_categories = 20;

		/**
		 * Normalize one source item.
		 *
		 * @param array<string, mixed> $item Source item.
		 * @param array<string, mixed> $image Resolved image data.
		 * @param int                  $order Fallback order.
		 * @param int                  $index Original item index.
		 * @param string               $fallback_image_url Original item image URL.
		 * @return array<string, mixed>
		 */
		public function normalize( array $item, array $image, $order, $index, $fallback_image_url ) {
			return $this->with_media_state_fields(
				$this->build_normalized_item( $item, $image, $order, $index, $fallback_image_url ),
				$image
			);
		}

		/**
		 * Build the normalized item payload before media-state enrichment.
		 *
		 * @param array<string, mixed> $item Source item.
		 * @param array<string, mixed> $image Resolved image data.
		 * @param int                  $order Fallback order.
		 * @param int                  $index Original item index.
		 * @param string               $fallback_image_url Original item image URL.
		 * @return array<string, mixed>
		 */
		private function build_normalized_item( array $item, array $image, $order, $index, $fallback_image_url ) {
			$now = gmdate( 'c' );

			return array(
				'id'             => $this->string_value( $item, 'id', 'logo-' . ( $index + 1 ) ),
				'mediaId'        => $this->string_value( $item, 'mediaId' ),
				'imageUrl'       => isset( $image['url'] ) ? (string) $image['url'] : '',
				'imageSrcset'    => isset( $image['srcset'] ) ? (string) $image['srcset'] : '',
				'imageWidth'     => isset( $image['width'] ) ? (string) $image['width'] : '',
				'imageHeight'    => isset( $image['height'] ) ? (string) $image['height'] : '',
				'title'          => $this->resolve_item_title( $item, $image, $fallback_image_url ),
				'caption'        => $this->limited_text_value( $item, 'caption' ),
				'tagline'        => $this->limited_text_value( $item, 'tagline' ),
				'description'    => $this->limited_text_value( $item, 'description' ),
				'categories'     => $this->normalize_categories( $item ),
				'tooltipText'    => $this->limited_text_value( $item, 'tooltipText' ),
				'url'            => $this->sanitize_url( $this->string_value( $item, 'url' ) ),
				'altText'        => $this->limited_text_value( $item, 'altText' ),
				'rating'         => $this->normalize_rating( isset( $item['rating'] ) ? $item['rating'] : null ),
				'additionalInfo' => $this->normalize_additional_info( isset( $item['additionalInfo'] ) && is_array( $item['additionalInfo'] ) ? $item['additionalInfo'] : array() ),
				'order'          => isset( $item['order'] ) && is_numeric( $item['order'] ) ? (int) $item['order'] : (int) $order,
				'createdAt'      => $this->string_value( $item, 'createdAt', $now ),
				'updatedAt'      => $this->string_value( $item, 'updatedAt', $now ),
			);
		}

		/**
		 * Resolve the display title for one item.
		 *
		 * @param array<string, mixed> $item Source item.
		 * @param array<string, mixed> $image Resolved image data.
		 * @param string               $fallback_image_url Original item image URL.
		 * @return string
		 */
		private function resolve_item_title( array $item, array $image, $fallback_image_url ) {
			$title = $this->attachment_title_value( $item );

			if ( '' === $title ) {
				$image_source = isset( $image['url'] ) ? (string) $image['url'] : '';
				if ( '' === $image_source ) {
					$image_source = $fallback_image_url;
				}

				$title = $this->derive_title_from_url( $image_source );
			}

			if ( '' === $title ) {
				$title = $this->sanitize_text( function_exists( '__' ) ? __( 'Untitled Logo', 'logo-carousel-free' ) : 'Untitled Logo' );
			}

			return $title;
		}

		/**
		 * Apply optional media-state fields from resolved image data.
		 *
		 * @param array<string, mixed> $normalized_item Normalized item payload.
		 * @param array<string, mixed> $image Resolved image data.
		 * @return array<string, mixed>
		 */
		private function with_media_state_fields( array $normalized_item, array $image ) {
			if ( isset( $image['status'] ) ) {
				$normalized_item['mediaStatus'] = (string) $image['status'];
			}
			if ( isset( $image['isMissing'] ) ) {
				$normalized_item['isMediaMissing'] = (bool) $image['isMissing'];
			}
			if ( isset( $image['needsAttention'] ) ) {
				$normalized_item['needsAttention'] = (bool) $image['needsAttention'];
			}
			if ( isset( $image['isPlaceholder'] ) ) {
				$normalized_item['isPlaceholder'] = (bool) $image['isPlaceholder'];
			}
			if ( isset( $image['originalTitle'] ) ) {
				$normalized_item['originalTitle'] = (string) $image['originalTitle'];
			}

			return $normalized_item;
		}

		/**
		 * Prefer attachment metadata when deriving a logo title.
		 *
		 * @param array<string, mixed> $item Source item.
		 * @return string
		 */
		private function attachment_title_value( array $item ) {
			$additional_info = isset( $item['additionalInfo'] ) && is_array( $item['additionalInfo'] ) ? $item['additionalInfo'] : array();
			$title           = $this->limited_text_value( $item, 'title' );

			if ( '' !== $title ) {
				return $title;
			}

			if ( isset( $additional_info['title'] ) && is_scalar( $additional_info['title'] ) ) {
				$title = $this->sanitize_text( (string) $additional_info['title'] );
				if ( '' !== $title ) {
					return $title;
				}
			}

			if ( isset( $additional_info['fileName'] ) && is_scalar( $additional_info['fileName'] ) ) {
				$title = $this->derive_title_from_url( (string) $additional_info['fileName'] );
			}

			return $title;
		}

		/**
		 * Return a safe string value.
		 *
		 * @param array<string, mixed> $item Source item.
		 * @param string               $key Item key.
		 * @param string               $fallback_value Default value.
		 * @return string
		 */
		private function string_value( array $item, $key, $fallback_value = '' ) {
			if ( ! isset( $item[ $key ] ) || is_array( $item[ $key ] ) || is_object( $item[ $key ] ) ) {
				return $fallback_value;
			}

			$value = $this->sanitize_text( (string) $item[ $key ] );

			// An older editor build stored a coerced JS object for media text
			// (description/caption); it was never real content.
			return '[object Object]' === $value ? $fallback_value : $value;
		}

		/**
		 * Normalize optional rating.
		 *
		 * Clamped to 0–5 and rounded to one decimal — the value is carried
		 * through as given. Rounding it to half stars would rewrite the user's
		 * data; that rounding happens at paint time, in `RatingStars::states()`.
		 *
		 * @param mixed $rating Rating value.
		 * @return float|null
		 */
		private function normalize_rating( $rating ) {
			return RatingStars::parse( $rating );
		}

		/**
		 * Normalize additional media info.
		 *
		 * @param array<string, mixed> $info Additional info.
		 * @return array<string, string>
		 */
		private function normalize_additional_info( array $info ) {
			return array(
				'fileName' => $this->string_value( $info, 'fileName' ),
				'mimeType' => $this->string_value( $info, 'mimeType' ),
				'fileSize' => $this->string_value( $info, 'fileSize' ),
				'width'    => $this->string_value( $info, 'width' ),
				'height'   => $this->string_value( $info, 'height' ),
			);
		}

		/**
		 * Build a readable title from an image URL.
		 *
		 * @param string $image_url Image URL.
		 * @return string
		 */
		private function derive_title_from_url( $image_url ) {
			$path = $this->parse_url_path( $image_url );
			if ( ! is_string( $path ) || '' === $path ) {
				return '';
			}

			$filename = pathinfo( $path, PATHINFO_FILENAME );
			$filename = str_replace( array( '-', '_' ), ' ', $filename );

			return $this->sanitize_text( ucwords( $filename ) );
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
		 * Limit and sanitize text.
		 *
		 * @param array<string, mixed> $item Source item.
		 * @param string               $key Item key.
		 * @return string
		 */
		private function limited_text_value( array $item, $key ) {
			$value = $this->string_value( $item, $key );

			if ( '' === $value ) {
				return '';
			}

			$limit = isset( $this->field_limits[ $key ] ) ? (int) $this->field_limits[ $key ] : 0;

			if ( $limit > 0 ) {
				$value = mb_substr( $value, 0, $limit );
			}

			return $value;
		}

		/**
		 * Normalize a logo item's categories into a clean array of strings.
		 *
		 * Prefers the multi-category `categories` array; falls back to the legacy
		 * single `category` string for content saved before multi-category.
		 * Sanitizes + caps each entry, drops empties, dedupes (case-sensitive),
		 * and limits the count. Cannot reuse `string_value()` here — it rejects
		 * arrays by design.
		 *
		 * @param array<string, mixed> $item Source item.
		 * @return array<int, string>
		 */
		private function normalize_categories( array $item ) {
			$source = array();

			if ( isset( $item['categories'] ) && is_array( $item['categories'] ) ) {
				$source = $item['categories'];
			} elseif ( isset( $item['category'] ) && is_string( $item['category'] ) && '' !== trim( $item['category'] ) ) {
				$source = array( $item['category'] );
			}

			$limit = (int) $this->field_limits['category'];
			$seen  = array();

			foreach ( $source as $entry ) {
				if ( ! is_string( $entry ) && ! is_numeric( $entry ) ) {
					continue;
				}

				$value = $this->sanitize_text( (string) $entry );
				if ( $limit > 0 ) {
					$value = mb_substr( $value, 0, $limit );
				}

				if ( '' === $value || in_array( $value, $seen, true ) ) {
					continue;
				}

				$seen[] = $value;

				if ( count( $seen ) >= $this->max_categories ) {
					break;
				}
			}

			return $seen;
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
		 * Parse the URL path in a WordPress-safe way with a PHP fallback.
		 *
		 * @param string $image_url Image URL.
		 * @return string
		 */
		private function parse_url_path( $image_url ) {
			if ( function_exists( '\\wp_parse_url' ) ) {
				return (string) \wp_parse_url( $image_url, PHP_URL_PATH );
			}

			if ( preg_match( '~^(?:[a-zA-Z][a-zA-Z0-9+.-]*:)?//[^/]+(/[^?#]*)~', (string) $image_url, $matches ) ) {
				return isset( $matches[1] ) ? (string) $matches[1] : '';
			}

			if ( preg_match( '~^(/[^?#]*)~', (string) $image_url, $matches ) ) {
				return isset( $matches[1] ) ? (string) $matches[1] : '';
			}

			return '';
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
