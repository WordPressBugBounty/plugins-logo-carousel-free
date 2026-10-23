<?php

/**
 * Shared reader for responsive whole-number fields (columns, rows).
 *
 * These fields started life as bare integers and became per-device objects, so
 * three shapes reach this reader from saved content:
 *
 *   5                                        legacy scalar
 *   { "value": 5 }                           legacy SPRangeControl Reset shape
 *   { "device": { "Desktop": 5, … } }        current
 *
 * `device()` answers "did the author set THIS breakpoint?" and returns null
 * when they did not, so a caller can keep its own fallback — Carousel needs the
 * caps it has always derived for small screens, while Grid simply
 * inherit Desktop. `resolve()` is that Desktop-inheriting answer.
 *
 * A JS mirror lives at `blocks/blocks/shared/utils/responsiveCount.js`.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Support;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ResponsiveCount' ) ) {
	/**
	 * Read a responsive count for one device bucket.
	 */
	class ResponsiveCount {
		/**
		 * The value the author set for this device, or null when they set none.
		 *
		 * A legacy scalar counts as Desktop only: content saved before the field
		 * was responsive never expressed a Tablet or Mobile intent, and inventing
		 * one for it would change how an existing block renders.
		 *
		 * @param mixed  $value Raw attribute value.
		 * @param string $device Device key (Desktop|Tablet|Mobile).
		 * @return int|null
		 */
		public static function device( $value, $device ) {
			if ( is_array( $value ) ) {
				if ( isset( $value['device'][ $device ] ) && is_numeric( $value['device'][ $device ] ) ) {
					return (int) $value['device'][ $device ];
				}

				// Legacy Reset shape — a single value, so Desktop only.
				if ( 'Desktop' === $device && isset( $value['value'] ) && is_numeric( $value['value'] ) ) {
					return (int) $value['value'];
				}

				return null;
			}

			if ( 'Desktop' === $device && is_numeric( $value ) ) {
				return (int) $value;
			}

			return null;
		}

		/**
		 * The value for this device, inheriting Desktop and then the default.
		 *
		 * @param mixed  $value Raw attribute value.
		 * @param string $device Device key (Desktop|Tablet|Mobile).
		 * @param int    $default Value when neither the device nor Desktop is set.
		 * @param int    $min Lower clamp.
		 * @param int    $max Upper clamp.
		 * @return int
		 */
		public static function resolve( $value, $device, $default, $min = 1, $max = 50 ) {
			$resolved = self::device( $value, $device );

			if ( null === $resolved ) {
				$resolved = self::device( $value, 'Desktop' );
			}

			if ( null === $resolved ) {
				$resolved = (int) $default;
			}

			return max( (int) $min, min( (int) $max, (int) $resolved ) );
		}
	}
}
