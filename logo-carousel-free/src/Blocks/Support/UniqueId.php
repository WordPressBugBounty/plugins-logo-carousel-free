<?php
/**
 * Shared block unique ID helpers.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Support;

use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssUtils;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UniqueId' ) ) {
	/**
	 * Normalize block uniqueId values for server-side usage.
	 */
	class UniqueId {
		/**
		 * Resolve a safe unique ID from block attributes.
		 *
		 * @param array $attributes Block attributes.
		 * @return string
		 */
		public static function from_attributes( array $attributes ) {
			if ( ! array_key_exists( 'uniqueId', $attributes ) ) {
				return '';
			}

			return self::sanitize_token( $attributes['uniqueId'] );
		}

		/**
		 * Sanitize one ID/class token.
		 *
		 * @param mixed $value Raw token.
		 * @return string
		 */
		public static function sanitize_token( $value ) {
			return CssUtils::sanitize_wrapper_token( $value );
		}

		/**
		 * Build the standard block unique ID class.
		 *
		 * @param string $unique_id Sanitized unique ID.
		 * @return string
		 */
		public static function build_class_name( $unique_id ) {
			$unique_id = self::sanitize_token( $unique_id );

			if ( '' === $unique_id ) {
				return '';
			}

			return 'sp-lcpro-unique-id-' . $unique_id;
		}
	}
}
