<?php
/**
 * Shared logo image resolution resolver.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sources;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoImageResolutionResolver' ) ) {
	class LogoImageResolutionResolver {

		/**
		 * Media status constants.
		 */
		const STATUS_FOUND    = 'found';
		const STATUS_MISSING  = 'missing';
		const STATUS_FALLBACK = 'fallback';
		const STATUS_ERROR    = 'error';

		/**
		 * Default placeholder dimensions.
		 */
		const PLACEHOLDER_WIDTH  = 140;
		const PLACEHOLDER_HEIGHT = 52;

		/**
		 * Resolve image URL and metadata from attachment or fallback sources.
		 *
		 * Enhanced with missing-media detection while maintaining backward compatibility.
		 *
		 * @param array  $item        Logo item containing mediaId and additionalInfo.
		 * @param string $resolution  Image resolution to retrieve (thumbnail, medium, large, full).
		 * @param string $fallback_url Optional fallback URL if no attachment found.
		 * @return array Image data with url, width, height, and optional status fields.
		 */
		public function resolve( array $item, $resolution, $fallback_url = '' ) {
			$resolution = is_string( $resolution ) && '' !== $resolution ? $resolution : 'large';
			$candidates = $this->get_resolution_candidates( $resolution );
			$media_id   = isset( $item['mediaId'] ) && is_scalar( $item['mediaId'] ) ? (int) $item['mediaId'] : 0;

			// Try to resolve from WordPress attachment first.
			$attachment_result = $this->resolve_from_attachment( $media_id, $candidates );
			if ( $attachment_result['status'] === self::STATUS_FOUND ) {
				return $this->format_result( $attachment_result );
			}

			// When WordPress confirms the attachment record is gone, do not let cached
			// size metadata mask the missing-media state.
			if ( $this->is_attachment_known_missing( $media_id ) ) {
				$final_result = $this->resolve_final_fallback( $item, '', $media_id, self::STATUS_MISSING );
				return $this->format_result( $final_result );
			}

			// Fall back to additionalInfo sizes (cached metadata).
			$additional_info = isset( $item['additionalInfo'] ) && is_array( $item['additionalInfo'] ) ? $item['additionalInfo'] : array();
			$sizes           = isset( $additional_info['sizes'] ) && is_array( $additional_info['sizes'] ) ? $additional_info['sizes'] : array();

			$fallback_result = $this->resolve_from_sizes( $sizes, $candidates, $item, $additional_info );
			if ( $fallback_result['status'] === self::STATUS_FOUND ) {
				return $this->format_result( $fallback_result );
			}

			// Final fallback to provided URL or generate placeholder.
			$final_result = $this->resolve_final_fallback( $item, $fallback_url, $media_id, $attachment_result['status'] );
			return $this->format_result( $final_result );
		}

		/**
		 * Resolve image from WordPress attachment.
		 *
		 * @param int   $media_id   Attachment ID.
		 * @param array $candidates Resolution candidates in priority order.
		 * @return array Resolution result with status.
		 */
		private function resolve_from_attachment( $media_id, $candidates ) {
			$result = array(
				'url'    => '',
				'width'  => '',
				'height' => '',
				'status' => self::STATUS_MISSING,
			);

			// No valid attachment ID.
			if ( $media_id <= 0 || ! function_exists( 'wp_get_attachment_image_src' ) ) {
				return $result;
			}

			// Check if attachment post exists and is valid.
			$attachment_post = get_post( $media_id );
			if ( ! $attachment_post || 'attachment' !== $attachment_post->post_type ) {
				return $result;
			}

			$reference_dimensions = $this->resolve_reference_dimensions_from_attachment( $media_id );

			// Try each resolution size in priority order.
			foreach ( $candidates as $candidate ) {
				$image = wp_get_attachment_image_src( $media_id, $candidate );

				if ( is_array( $image ) && ! empty( $image[0] ) ) {
					$width  = isset( $image[1] ) ? (int) $image[1] : 0;
					$height = isset( $image[2] ) ? (int) $image[2] : 0;

					if ( ! $this->is_logo_safe_candidate( $width, $height, $reference_dimensions ) ) {
						continue;
					}

					return array(
						'url'    => (string) $image[0],
						'width'  => $width > 0 ? (string) $width : '',
						'height' => $height > 0 ? (string) $height : '',
						'srcset' => $this->resolve_srcset( $media_id, $candidate ),
						'status' => self::STATUS_FOUND,
					);
				}
			}

			// Attachment exists but no valid image sizes.
			return $result;
		}

		/**
		 * Resolve a responsive srcset string for the chosen attachment size.
		 *
		 * Lets the browser pick a crisp candidate on HiDPI/retina displays and a
		 * lighter file on small screens, while the requested resolution stays the baseline.
		 *
		 * @param int    $media_id Attachment ID.
		 * @param string $candidate Resolved size name.
		 * @return string Srcset string, or empty when unavailable.
		 */
		private function resolve_srcset( $media_id, $candidate ) {
			if ( ! function_exists( 'wp_get_attachment_image_srcset' ) ) {
				return '';
			}

			$srcset = wp_get_attachment_image_srcset( $media_id, $candidate );

			return is_string( $srcset ) ? $srcset : '';
		}

		/**
		 * Resolve original attachment dimensions for crop detection.
		 *
		 * @param int $media_id Attachment ID.
		 * @return array{width:int,height:int}
		 */
		private function resolve_reference_dimensions_from_attachment( $media_id ) {
			if ( $media_id <= 0 || ! function_exists( 'wp_get_attachment_image_src' ) ) {
				return array(
					'width'  => 0,
					'height' => 0,
				);
			}

			$image = wp_get_attachment_image_src( $media_id, 'full' );

			if ( ! is_array( $image ) || empty( $image[1] ) || empty( $image[2] ) ) {
				return array(
					'width'  => 0,
					'height' => 0,
				);
			}

			return array(
				'width'  => (int) $image[1],
				'height' => (int) $image[2],
			);
		}

		/**
		 * Determine whether WordPress can positively confirm that an attachment no longer exists.
		 *
		 * This is intentionally stricter than a generic resolution miss: if core APIs are not
		 * available, keep legacy fallback behavior intact.
		 *
		 * @param int $media_id Attachment ID.
		 * @return bool
		 */
		private function is_attachment_known_missing( $media_id ) {
			if ( $media_id <= 0 || ! function_exists( 'get_post' ) ) {
				return false;
			}

			$attachment_post = get_post( $media_id );

			return ! $attachment_post || 'attachment' !== $attachment_post->post_type;
		}

		/**
		 * Resolve image from additionalInfo cached sizes.
		 *
		 * @param array $sizes          Image sizes cached in additionalInfo.
		 * @param array $candidates     Resolution candidates in priority order.
		 * @param array $item           Original logo item.
		 * @param array $additional_info Full additional info array.
		 * @return array Resolution result with status.
		 */
		private function resolve_from_sizes( $sizes, $candidates, $item, $additional_info ) {
			$result = array(
				'url'    => '',
				'width'  => '',
				'height' => '',
				'status' => self::STATUS_FALLBACK,
			);

			$reference_dimensions = $this->resolve_reference_dimensions_from_item( $item, $additional_info );

			// Try each resolution size from cached data.
			foreach ( $candidates as $candidate ) {
				if ( empty( $sizes[ $candidate ] ) || ! is_array( $sizes[ $candidate ] ) ) {
					continue;
				}

				$size   = $sizes[ $candidate ];
				$url    = isset( $size['url'] ) && is_scalar( $size['url'] ) ? trim( (string) $size['url'] ) : '';
				$width  = isset( $size['width'] ) && is_scalar( $size['width'] ) ? trim( (string) $size['width'] ) : '';
				$height = isset( $size['height'] ) && is_scalar( $size['height'] ) ? trim( (string) $size['height'] ) : '';

				if ( '' === $url ) {
					continue;
				}

				if ( ! $this->is_logo_safe_candidate( $width, $height, $reference_dimensions ) ) {
					continue;
				}

				return array(
					'url'    => $url,
					'width'  => $width,
					'height' => $height,
					'srcset' => $this->build_srcset_from_sizes( $sizes, $reference_dimensions ),
					'status' => self::STATUS_FOUND,
				);
			}

			// No valid cached sizes, try to use stored dimensions.
			$result['width']  = isset( $item['imageWidth'] ) && is_scalar( $item['imageWidth'] ) ? trim( (string) $item['imageWidth'] ) : '';
			$result['height'] = isset( $item['imageHeight'] ) && is_scalar( $item['imageHeight'] ) ? trim( (string) $item['imageHeight'] ) : '';

			if ( '' === $result['width'] ) {
				$result['width'] = isset( $additional_info['width'] ) && is_scalar( $additional_info['width'] ) ? trim( (string) $additional_info['width'] ) : '';
			}

			if ( '' === $result['height'] ) {
				$result['height'] = isset( $additional_info['height'] ) && is_scalar( $additional_info['height'] ) ? trim( (string) $additional_info['height'] ) : '';
			}

			return $result;
		}

		/**
		 * Build a responsive srcset string from cached size metadata.
		 *
		 * Mirrors the attachment-path srcset for logos resolved from cached sizes
		 * (e.g. when the live attachment record is unavailable). Only proportional,
		 * non-cropped candidates are included so the browser never swaps in a
		 * distorted thumbnail.
		 *
		 * @param array $sizes                Cached size map ( size_name => {url,width,height} ).
		 * @param array $reference_dimensions Original image dimensions for crop detection.
		 * @return string Srcset string, or empty when fewer than two usable candidates.
		 */
		private function build_srcset_from_sizes( $sizes, $reference_dimensions ) {
			if ( ! is_array( $sizes ) ) {
				return '';
			}

			$candidates = array();

			foreach ( $sizes as $size ) {
				if ( ! is_array( $size ) ) {
					continue;
				}

				$url    = isset( $size['url'] ) && is_scalar( $size['url'] ) ? trim( (string) $size['url'] ) : '';
				$width  = isset( $size['width'] ) && is_scalar( $size['width'] ) ? (int) $size['width'] : 0;
				$height = isset( $size['height'] ) && is_scalar( $size['height'] ) ? (int) $size['height'] : 0;

				if ( '' === $url || $width <= 0 ) {
					continue;
				}

				if ( ! $this->is_logo_safe_candidate( $width, $height, $reference_dimensions ) ) {
					continue;
				}

				$safe_url = $this->sanitize_srcset_url( $url );
				if ( '' === $safe_url ) {
					continue;
				}

				// Key by width to deduplicate candidates that share a pixel width.
				$candidates[ $width ] = $safe_url . ' ' . $width . 'w';
			}

			if ( count( $candidates ) < 2 ) {
				return '';
			}

			ksort( $candidates );

			return implode( ', ', $candidates );
		}

		/**
		 * Sanitize a single srcset candidate URL.
		 *
		 * @param string $url Raw URL.
		 * @return string Safe URL, or empty when invalid.
		 */
		private function sanitize_srcset_url( $url ) {
			$url = trim( (string) $url );

			if ( '' === $url ) {
				return '';
			}

			// A space would break the "url widthw" srcset grammar.
			if ( false !== strpos( $url, ' ' ) ) {
				return '';
			}

			if ( function_exists( '\\esc_url_raw' ) ) {
				return (string) \esc_url_raw( $url );
			}

			return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
		}

		/**
		 * Resolve original item dimensions for crop detection.
		 *
		 * @param array $item Original logo item.
		 * @param array $additional_info Full additional info array.
		 * @return array{width:int,height:int}
		 */
		private function resolve_reference_dimensions_from_item( $item, $additional_info ) {
			$width  = isset( $additional_info['width'] ) && is_scalar( $additional_info['width'] ) ? (int) $additional_info['width'] : 0;
			$height = isset( $additional_info['height'] ) && is_scalar( $additional_info['height'] ) ? (int) $additional_info['height'] : 0;

			if ( $width > 0 && $height > 0 ) {
				return array(
					'width'  => $width,
					'height' => $height,
				);
			}

			return array(
				'width'  => isset( $item['imageWidth'] ) && is_scalar( $item['imageWidth'] ) ? (int) $item['imageWidth'] : 0,
				'height' => isset( $item['imageHeight'] ) && is_scalar( $item['imageHeight'] ) ? (int) $item['imageHeight'] : 0,
			);
		}

		/**
		 * Check whether a generated size preserves the original logo shape.
		 *
		 * WordPress thumbnails are commonly hard-cropped, which can cut wide logos.
		 * Logo rendering should fall forward to a proportional size in that case.
		 *
		 * @param int|string $width Candidate width.
		 * @param int|string $height Candidate height.
		 * @param array      $reference_dimensions Original image dimensions.
		 * @return bool
		 */
		private function is_logo_safe_candidate( $width, $height, $reference_dimensions ) {
			$width            = (int) $width;
			$height           = (int) $height;
			$reference_width  = isset( $reference_dimensions['width'] ) ? (int) $reference_dimensions['width'] : 0;
			$reference_height = isset( $reference_dimensions['height'] ) ? (int) $reference_dimensions['height'] : 0;

			if ( $width <= 0 || $height <= 0 || $reference_width <= 0 || $reference_height <= 0 ) {
				return true;
			}

			$candidate_ratio = $width / $height;
			$reference_ratio = $reference_width / $reference_height;

			if ( $reference_ratio <= 0 ) {
				return true;
			}

			return abs( $candidate_ratio - $reference_ratio ) / $reference_ratio <= 0.15;
		}

		/**
		 * Resolve final fallback to provided URL or generate placeholder.
		 *
		 * @param array  $item            Original logo item.
		 * @param string $fallback_url    Provided fallback URL.
		 * @param int    $media_id        Attachment ID for context.
		 * @param string $attachment_status Status from attachment resolution attempt.
		 * @return array Resolution result with status and placeholder data.
		 */
		private function resolve_final_fallback( $item, $fallback_url, $media_id, $attachment_status ) {
			$fallback_url = is_string( $fallback_url ) ? trim( $fallback_url ) : '';

			// If the attachment ID is known to be broken, do not mask it with a stale URL.
			if ( $media_id > 0 && self::STATUS_MISSING === $attachment_status ) {
				$title           = $this->extract_item_title( $item );
				$placeholder_url = $this->get_placeholder_url( $title, $media_id );

				return array(
					'url'           => $placeholder_url,
					'width'         => (string) self::PLACEHOLDER_WIDTH,
					'height'        => (string) self::PLACEHOLDER_HEIGHT,
					'status'        => self::STATUS_MISSING,
					'missing'       => true,
					'placeholder'   => true,
					'originalTitle' => $title,
				);
			}

			// If we have a valid fallback URL, use it for non-missing legacy cases.
			if ( '' !== $fallback_url ) {
				return array(
					'url'     => $fallback_url,
					'width'   => isset( $item['imageWidth'] ) && is_scalar( $item['imageWidth'] ) ? trim( (string) $item['imageWidth'] ) : self::PLACEHOLDER_WIDTH,
					'height'  => isset( $item['imageHeight'] ) && is_scalar( $item['imageHeight'] ) ? trim( (string) $item['imageHeight'] ) : self::PLACEHOLDER_HEIGHT,
					'status'  => self::STATUS_FALLBACK,
					'missing' => false,
				);
			}

			// Generate placeholder for missing attachment.
			$title           = $this->extract_item_title( $item );
			$placeholder_url = $this->get_placeholder_url( $title, $media_id );

			return array(
				'url'           => $placeholder_url,
				'width'         => (string) self::PLACEHOLDER_WIDTH,
				'height'        => (string) self::PLACEHOLDER_HEIGHT,
				'status'        => $media_id > 0 ? self::STATUS_MISSING : self::STATUS_FALLBACK,
				'missing'       => $media_id > 0,
				'placeholder'   => true,
				'originalTitle' => $title,
			);
		}

		/**
		 * Format result for backward compatibility.
		 *
		 * Ensures existing code continues to work while new code can access extended fields.
		 *
		 * @param array $result Raw result from resolution methods.
		 * @return array Formatted result with backward compatibility.
		 */
		private function format_result( $result ) {
			// Core fields that existing code expects.
			$formatted = array(
				'url'    => isset( $result['url'] ) ? (string) $result['url'] : '',
				'width'  => isset( $result['width'] ) ? (string) $result['width'] : '',
				'height' => isset( $result['height'] ) ? (string) $result['height'] : '',
				'srcset' => isset( $result['srcset'] ) ? (string) $result['srcset'] : '',
			);

			// Extended fields for consumers that support missing-media handling.
			if ( isset( $result['status'] ) ) {
				$formatted['status']         = (string) $result['status'];
				$formatted['isMissing']      = ( $result['status'] === self::STATUS_MISSING );
				$formatted['needsAttention'] = ( $result['status'] === self::STATUS_MISSING || $result['status'] === self::STATUS_ERROR );
			}

			// Placeholder-specific fields.
			if ( isset( $result['placeholder'] ) && $result['placeholder'] ) {
				$formatted['isPlaceholder'] = true;
			}

			// Original title for placeholder display.
			if ( isset( $result['originalTitle'] ) ) {
				$formatted['originalTitle'] = (string) $result['originalTitle'];
			}

			return $formatted;
		}

		/**
		 * Extract title from item for placeholder display.
		 *
		 * @param array $item Logo item.
		 * @return string Extracted title or default.
		 */
		private function extract_item_title( $item ) {
			$title = isset( $item['title'] ) && is_scalar( $item['title'] ) ? trim( (string) $item['title'] ) : '';
			if ( '' !== $title ) {
				return $title;
			}

			$alt_text = isset( $item['altText'] ) && is_scalar( $item['altText'] ) ? trim( (string) $item['altText'] ) : '';
			if ( '' !== $alt_text ) {
				return $alt_text;
			}

			return 'Logo';
		}

		/**
		 * Get placeholder URL for missing media.
		 *
		 * Generates a lightweight SVG data URI placeholder with visual indicators.
		 *
		 * @param string $title    Logo title for display.
		 * @param int    $media_id Attachment ID for reference.
		 * @return string SVG data URI.
		 */
		private function get_placeholder_url( $title = '', $media_id = 0 ) {
			$placeholder_text = $this->sanitize_placeholder_text( $title );

			// Generate SVG placeholder.
			$svg  = '<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">';
			$svg .= '<rect width="200" height="200" fill="#f0f0f0"/>';
			$svg .= '<rect x="2" y="2" width="196" height="196" fill="none" stroke="#e0e0e0" stroke-width="2" rx="8" ry="8"/>';
			$svg .= '<circle cx="100" cy="70" r="25" fill="#ddd"/>';
			$svg .= '<text x="100" y="100" font-family="Arial, sans-serif" font-size="13" fill="#888" text-anchor="middle">Image Missing</text>';

			if ( $placeholder_text ) {
				$truncated = $this->truncate_text( $placeholder_text, 18 );
				$svg      .= sprintf( '<text x="100" y="120" font-family="Arial, sans-serif" font-size="11" font-weight="bold" fill="#666" text-anchor="middle">%s</text>', esc_attr( $truncated ) );
			}

			$svg .= '<text x="100" y="145" font-family="Arial, sans-serif" font-size="10" fill="#999" text-anchor="middle">Upload new image</text>';
			$svg .= '</svg>';

			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		}

		/**
		 * Sanitize text for safe placeholder display.
		 *
		 * @param string $text Text to sanitize.
		 * @return string Sanitized text.
		 */
		private function sanitize_placeholder_text( $text ) {
			if ( function_exists( 'wp_strip_all_tags' ) ) {
				$text = wp_strip_all_tags( $text );
			} else {
				$text = preg_replace( '/<[^>]*>/', '', (string) $text );
				$text = is_string( $text ) ? $text : '';
			}

			$text = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			return trim( $text );
		}

		/**
		 * Truncate text to fit placeholder dimensions.
		 *
		 * @param string $text       Text to truncate.
		 * @param int    $max_length Maximum character length.
		 * @return string Truncated text with ellipsis.
		 */
		private function truncate_text( $text, $max_length = 20 ) {
			if ( strlen( $text ) <= $max_length ) {
				return $text;
			}

			return substr( $text, 0, $max_length - 3 ) . '...';
		}

		/**
		 * Get resolution candidates in priority order.
		 *
		 * Provides fallback chain for each requested resolution.
		 *
		 * @param string $resolution Requested resolution.
		 * @return array Resolution candidates in priority order.
		 */
		private function get_resolution_candidates( $resolution ) {
			switch ( $resolution ) {
				case 'thumbnail':
					return array( 'thumbnail', 'medium', 'large', 'full' );
				case 'medium':
					return array( 'medium', 'large', 'full' );
				case 'large':
					return array( 'large', 'full' );
				case 'full':
				default:
					return array( 'full' );
			}
		}
	}
}
