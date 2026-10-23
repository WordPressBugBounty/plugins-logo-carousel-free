<?php
/**
 * Adapt resolved image payloads for logo showcase sources.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sources\LogoShowcase;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ResolvedImageAdapter' ) ) {
	/**
	 * Normalize raw image-resolution resolver output.
	 */
	class ResolvedImageAdapter {
		/**
		 * Adapt raw resolver output to the canonical image payload shape.
		 *
		 * @param array<string, mixed> $resolved Raw resolved image data.
		 * @return array<string, mixed>
		 */
		public function adapt( array $resolved ) {
			$resolved['url']    = $this->sanitize_url( isset( $resolved['url'] ) ? (string) $resolved['url'] : '' );
			$resolved['width']  = isset( $resolved['width'] ) ? (string) $resolved['width'] : '';
			$resolved['height'] = isset( $resolved['height'] ) ? (string) $resolved['height'] : '';

			return $resolved;
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

			// `LogoImageResolutionResolver::get_placeholder_url()` generates the
			// missing-media placeholder as a `data:image/svg+xml;base64,…` URI.
			// `esc_url_raw()` rejects the `data` scheme by default (not in
			// `wp_allowed_protocols()`), which would silently erase the very
			// placeholder this pipeline just generated, dropping the logo item
			// entirely downstream. Validate the narrower `data:image/` shape
			// ourselves and pass it through as-is — an `<img src>` only ever
			// decodes this as image data, never as an executable document.
			if ( 0 === strpos( $value, 'data:image/' ) ) {
				return $value;
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
