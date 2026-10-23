<?php
/**
 * Recursive merge helper for schema overrides.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Util;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeepMerge' ) ) {
	/**
	 * Merge associative schema trees recursively.
	 */
	class DeepMerge {
		/**
		 * Recursively merge override values into the base array.
		 *
		 * @param array $base Base schema map.
		 * @param array $override Override schema map.
		 * @return array
		 */
		public static function recursive( array $base, array $override ) {
			foreach ( $override as $key => $override_value ) {
				if (
					array_key_exists( $key, $base )
					&& is_array( $base[ $key ] )
					&& is_array( $override_value )
					&& self::is_assoc( $base[ $key ] )
					&& self::is_assoc( $override_value )
				) {
					$base[ $key ] = self::recursive( $base[ $key ], $override_value );
				} else {
					$base[ $key ] = $override_value;
				}
			}

			return $base;
		}

		/**
		 * Check whether the array is associative.
		 *
		 * @param array $candidate Array to inspect.
		 * @return bool
		 */
		private static function is_assoc( array $candidate ) {
			if ( array() === $candidate ) {
				return true;
			}

			return array_keys( $candidate ) !== range( 0, count( $candidate ) - 1 );
		}
	}
}
