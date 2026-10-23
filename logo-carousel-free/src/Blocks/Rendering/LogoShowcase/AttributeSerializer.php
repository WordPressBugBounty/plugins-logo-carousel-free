<?php
/**
 * Serialize logo showcase HTML attributes and data payloads.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoShowcase;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AttributeSerializer' ) ) {
	/**
	 * Serialize root attributes for logo showcase rendering.
	 */
	class AttributeSerializer {
		/**
		 * Serialize style declarations into a CSS string.
		 *
		 * @param array<string, mixed> $styles Style declarations.
		 * @return string
		 */
		public function serialize_style_declarations( array $styles ) {
			$chunks = array();

			foreach ( $styles as $property => $value ) {
				if ( null === $value || '' === $value || false === $value ) {
					continue;
				}

				$chunks[] = trim( (string) $property ) . ': ' . trim( (string) $value ) . ';';
			}

			return implode( ' ', $chunks );
		}

		/**
		 * Serialize HTML attributes.
		 *
		 * @param array<string, string> $attributes Attribute map.
		 * @return string
		 */
		public function serialize_attributes( array $attributes ) {
			$chunks = array();

			foreach ( $attributes as $name => $value ) {
				if ( '' === $value ) {
					continue;
				}

				if ( 'style' === $name ) {
					$chunks[] = ' style="' . $this->escape_attr( $value ) . '"';
					continue;
				}

				$chunks[] = ' ' . $this->escape_attr( $name ) . '="' . $this->escape_attr( $value ) . '"';
			}

			return implode( '', $chunks );
		}

		/**
		 * Encode a data payload safely for HTML attributes.
		 *
		 * @param array<string, mixed> $value Payload.
		 * @return string
		 */
		public function encode_data( array $value ) {
			if ( function_exists( '\\wp_json_encode' ) ) {
				return (string) \wp_json_encode( $value );
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- WordPress is unavailable in plain PHPUnit runs.
			return (string) json_encode( $value );
		}

		/**
		 * Escape a string for use in HTML attributes.
		 *
		 * @param string $value Raw value.
		 * @return string
		 */
		private function escape_attr( $value ) {
			if ( function_exists( '\\esc_attr' ) ) {
				return \esc_attr( $value );
			}

			return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
		}
	}
}
