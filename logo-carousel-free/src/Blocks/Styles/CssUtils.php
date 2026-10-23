<?php
/**
 * Shared CSS string utilities for the block-family server-side dynamic CSS.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CssUtils' ) ) {
	/**
	 * Low-level CSS helpers for rule merging and string emission.
	 */
	class CssUtils {
		/**
		 * Sanitize one wrapper/id/class token used in selectors and class lists.
		 *
		 * @param mixed $value Raw token.
		 * @return string
		 */
		public static function sanitize_wrapper_token( $value ) {
			if ( ! is_scalar( $value ) ) {
				return '';
			}

			$value = (string) $value;

			if ( function_exists( 'sanitize_html_class' ) ) {
				return sanitize_html_class( $value );
			}

			return preg_replace( '/[^A-Za-z0-9_-]/', '', $value );
		}

		/**
		 * Sanitize a CSS color value.
		 *
		 * @param mixed  $value Raw color value.
		 * @param string $fallback Fallback color.
		 * @return string
		 */
		public static function sanitize_css_color( $value, $fallback = '' ) {
			if ( ! is_scalar( $value ) ) {
				return $fallback;
			}

			$value = trim( (string) $value );
			if ( '' === $value ) {
				return $fallback;
			}

			if ( preg_match( '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
				return $value;
			}

			if ( preg_match( '/^(?:rgb|rgba|hsl|hsla)\((?:[^()]*)\)$/', $value ) ) {
				return $value;
			}

			if ( preg_match( '/^var\(--[A-Za-z0-9_-]+\)$/', $value ) ) {
				return $value;
			}

			if ( in_array( strtolower( $value ), array( 'transparent', 'currentcolor', 'inherit' ), true ) ) {
				return $value;
			}

			return $fallback;
		}

		/**
		 * Sanitize a transition duration in seconds.
		 *
		 * @param mixed $value Raw duration.
		 * @param float $fallback Fallback duration.
		 * @return float
		 */
		public static function sanitize_transition_duration( $value, $fallback = 0.3 ) {
			if ( ! is_numeric( $value ) ) {
				return (float) $fallback;
			}

			$value = (float) $value;

			if ( $value < 0 ) {
				return 0.0;
			}

			if ( $value > 30 ) {
				return 30.0;
			}

			return $value;
		}

		/**
		 * Build a safe transition shorthand.
		 *
		 * @param mixed  $duration Raw duration in seconds.
		 * @param string $property CSS property name.
		 * @param string $timing Timing function.
		 * @return string
		 */
		public static function build_transition_shorthand( $duration, $property = 'all', $timing = 'ease' ) {
			$safe_property = in_array( $property, array( 'all', 'opacity', 'transform', 'background', 'color', 'border-color' ), true ) ? $property : 'all';
			$safe_timing   = in_array( $timing, array( 'ease', 'linear', 'ease-in', 'ease-out', 'ease-in-out' ), true ) ? $timing : 'ease';
			$safe_duration = self::sanitize_transition_duration( $duration );

			return $safe_property . ' ' . rtrim( rtrim( sprintf( '%.3F', $safe_duration ), '0' ), '.' ) . 's ' . $safe_timing;
		}

		/**
		 * Merge duplicate selectors and their styles.
		 *
		 * @param array<int, array{selector:string, styles:array<string, mixed>}> $rules Rules.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function merge_css_rules_by_selector( array $rules ) {
			$merged = array();

			foreach ( $rules as $rule ) {
				if ( empty( $rule['selector'] ) || empty( $rule['styles'] ) || ! is_array( $rule['styles'] ) ) {
					continue;
				}

				$selector = (string) $rule['selector'];
				if ( ! isset( $merged[ $selector ] ) ) {
					$merged[ $selector ] = array();
				}

				$merged[ $selector ] = array_merge( $merged[ $selector ], $rule['styles'] );
			}

			$output = array();
			foreach ( $merged as $selector => $styles ) {
				$output[] = array(
					'selector' => $selector,
					'styles'   => $styles,
				);
			}

			return $output;
		}

		/**
		 * Convert a rule array into CSS text.
		 *
		 * @param array<int, array{selector:string, styles:array<string, mixed>}> $rules Rules.
		 * @return string
		 */
		public static function object_to_css_string( array $rules ) {
			$css = '';

			foreach ( $rules as $rule ) {
				if ( empty( $rule['selector'] ) || empty( $rule['styles'] ) || ! is_array( $rule['styles'] ) ) {
					continue;
				}

				$declarations = array();
				foreach ( $rule['styles'] as $property => $value ) {
					if ( null === $value || '' === $value || false === $value ) {
						continue;
					}
					$value = self::sanitize_declaration_value( $value );
					if ( '' === $value ) {
						continue;
					}
					$declarations[] = trim( (string) $property ) . ': ' . $value . ';';
				}

				if ( empty( $declarations ) ) {
					continue;
				}

				$css .= $rule['selector'] . ' { ' . implode( ' ', $declarations ) . " }\n";
			}

			return trim( $css );
		}

		/**
		 * Sanitize a single CSS declaration value before it is written into the
		 * stylesheet.
		 *
		 * Block attributes are author-editable (and travel through REST), so a
		 * crafted/corrupted value must not be able to break out of its declaration.
		 * We strip the characters that would allow that — rule braces `{}`, the
		 * statement separator `;`, and the angle brackets `<>` that could otherwise
		 * close the surrounding `<style>` element. Legitimate CSS values (colors,
		 * lengths, gradients, font stacks) never contain any of these, so valid
		 * output is unchanged.
		 *
		 * @param mixed $value Raw declaration value.
		 * @return string Sanitized value (may be empty).
		 */
		private static function sanitize_declaration_value( $value ) {
			$value = preg_replace( '/[<>{};]/', '', (string) $value );

			return trim( (string) $value );
		}

		/**
		 * Wrap CSS in a media query.
		 *
		 * @param string $css CSS string.
		 * @param string $query Media query.
		 * @return string
		 */
		public static function wrap_in_media_query( $css, $query ) {
			$css = trim( (string) $css );
			if ( '' === $css ) {
				return '';
			}

			return '@media ' . $query . ' { ' . $css . ' }';
		}
	}
}
