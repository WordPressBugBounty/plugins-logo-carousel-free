<?php
/**
 * Shared CSS value conversion helpers for block-family styles.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CssValueHelper' ) ) {
	/**
	 * Convert grouped attribute branches into CSS-ready values.
	 */
	class CssValueHelper {
		/**
		 * Resolve a grouped background object into a CSS background value.
		 *
		 * @param array<string, mixed> $background Background branch.
		 * @return string
		 */
		public static function background_value( array $background ) {
			$style = isset( $background['style'] ) ? (string) $background['style'] : 'transparent';

			if ( 'solid' === $style ) {
				return isset( $background['solid'] ) ? (string) $background['solid'] : '';
			}

			if ( 'gradient' === $style ) {
				return isset( $background['gradient'] ) ? (string) $background['gradient'] : '';
			}

			return '';
		}

		/**
		 * Resolve a grouped background slice into a map of CSS declarations.
		 *
		 * Solid and gradient emit a single `background`. Glassmorphism is a Pro-only
		 * background type: a Pro-saved glass slice falls back to a solid fill
		 * (its `solid` colour, else its `glassBase`), exactly as ModuleGate
		 * downgrades it when the module is off. Border and box-shadow are
		 * intentionally NOT emitted here; they stay owned by the Border control.
		 *
		 * @param array<string, mixed> $background Background slice.
		 * @return array<string, string> Declaration map (property => value).
		 */
		public static function background_declarations( array $background ) {
			$style = isset( $background['style'] ) ? (string) $background['style'] : 'transparent';

			if ( 'glassmorphism' === $style ) {
				$style = 'solid';
				if ( ( ! isset( $background['solid'] ) || '' === $background['solid'] ) && ! empty( $background['glassBase'] ) ) {
					$background['solid'] = $background['glassBase'];
				}
			}

			if ( 'solid' === $style ) {
				$solid = isset( $background['solid'] ) ? (string) $background['solid'] : '';
				return '' !== $solid ? array( 'background' => $solid ) : array();
			}

			if ( 'gradient' === $style ) {
				$gradient = isset( $background['gradient'] ) ? (string) $background['gradient'] : '';
				return '' !== $gradient ? array( 'background' => $gradient ) : array();
			}

			return array();
		}

		/**
		 * Convert a responsive spacing object into CSS shorthand for a device.
		 *
		 * @param array<string, mixed> $spacing Spacing branch.
		 * @param string               $device Device key.
		 * @param float|int|null       $min Optional floor applied to each numeric
		 *   side before formatting (e.g. a sane negative-margin floor). Opt-in per
		 *   call site, not a blanket default — padding/border-width consumers of
		 *   this helper are already correct with no clamp.
		 * @return string
		 */
		public static function responsive_spacing( array $spacing, $device, $min = null ) {
			$device_values = isset( $spacing['device'][ $device ] ) && is_array( $spacing['device'][ $device ] ) ? $spacing['device'][ $device ] : array();
			$unit          = isset( $spacing['unit'][ $device ] ) ? (string) $spacing['unit'][ $device ] : 'px';

			if ( empty( $device_values ) ) {
				return '';
			}

			$values = array(
				isset( $device_values['top'] ) ? $device_values['top'] : '',
				isset( $device_values['right'] ) ? $device_values['right'] : '',
				isset( $device_values['bottom'] ) ? $device_values['bottom'] : '',
				isset( $device_values['left'] ) ? $device_values['left'] : '',
			);

			if ( null !== $min ) {
				$values = array_map(
					static function ( $value ) use ( $min ) {
						if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
							return $value;
						}

						return max( (float) $value, $min );
					},
					$values
				);
			}

			$filtered = array_filter(
				$values,
				static function ( $value ) {
					return '' !== $value && null !== $value;
				}
			);

			if ( empty( $filtered ) ) {
				return '';
			}

			return implode(
				' ',
				array_map(
					static function ( $value ) use ( $unit ) {
						if ( '' === $value || null === $value ) {
							return '0';
						}

						return $value . $unit;
					},
					$values
				)
			);
		}
	}
}
