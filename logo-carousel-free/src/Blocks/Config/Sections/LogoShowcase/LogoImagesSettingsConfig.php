<?php
/**
 * Normalize logo image settings.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\LogoImagesDefaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoImagesSettingsConfig' ) ) {
	/**
	 * Normalize imagesSettings attributes for render/config.
	 */
	class LogoImagesSettingsConfig {
		/**
		 * Normalize images settings.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @return array<string, mixed>
		 */
		public function normalize( array $attributes ) {
			$defaults = LogoImagesDefaults::get_images_settings_defaults();
			$raw      = isset( $attributes['imagesSettings'] ) && is_array( $attributes['imagesSettings'] ) ? $attributes['imagesSettings'] : array();

			return array(
				'aspectRatio'             => $this->normalize_enum( isset( $raw['aspectRatio'] ) ? $raw['aspectRatio'] : null, array( 'original', '1:1', '4:3', '3:4', '3:2', '2:3', '16:9', '9:16', 'custom' ), $defaults['aspectRatio'] ),
				'customAspectRatioWidth'  => $this->normalize_custom_size( isset( $raw['customAspectRatioWidth'] ) ? $raw['customAspectRatioWidth'] : null, $defaults['customAspectRatioWidth'], LogoImagesDefaults::get_custom_width_units() ),
				'customAspectRatioHeight' => $this->normalize_custom_size( isset( $raw['customAspectRatioHeight'] ) ? $raw['customAspectRatioHeight'] : null, $defaults['customAspectRatioHeight'], LogoImagesDefaults::get_custom_height_units() ),
				'resolution'              => $this->normalize_enum( isset( $raw['resolution'] ) ? $raw['resolution'] : null, array( 'full', 'large', 'medium', 'thumbnail' ), $defaults['resolution'] ),
				'lazyLoad'                => $this->normalize_boolean( isset( $raw['lazyLoad'] ) ? $raw['lazyLoad'] : null, $defaults['lazyLoad'] ),
				'randomOrder'             => $this->normalize_boolean( isset( $raw['randomOrder'] ) ? $raw['randomOrder'] : null, $defaults['randomOrder'] ),
				'clickAction'             => $this->normalize_enum( isset( $raw['clickAction'] ) ? $raw['clickAction'] : null, array( 'none', 'link', 'popup' ), $defaults['clickAction'] ),
				'allowSvgUploads'         => $this->normalize_boolean( isset( $raw['allowSvgUploads'] ) ? $raw['allowSvgUploads'] : null, $defaults['allowSvgUploads'] ),
			);
		}

		/**
		 * Resolve a custom size attribute to a CSS length for one device.
		 *
		 * @param mixed                $raw Saved value (responsive object or legacy scalar).
		 * @param array<string, mixed> $fallback Default responsive size.
		 * @param array<int, string>   $allowed_units Allowed units.
		 * @param string               $device Desktop|Tablet|Mobile.
		 * @return string
		 */
		public static function resolve_css_size( $raw, array $fallback, array $allowed_units, $device = 'Desktop' ) {
			$normalized = ( new self() )->normalize_custom_size( $raw, $fallback, $allowed_units );
			$unit       = isset( $normalized['unit'][ $device ] ) ? (string) $normalized['unit'][ $device ] : ( isset( $normalized['unit']['Desktop'] ) ? (string) $normalized['unit']['Desktop'] : 'px' );
			$value      = array_key_exists( $device, $normalized['device'] ) ? $normalized['device'][ $device ] : ( isset( $normalized['device']['Desktop'] ) ? $normalized['device']['Desktop'] : '' );

			if ( 'auto' === $unit || 'auto' === $value ) {
				return 'auto';
			}

			// Empty size: use the default's — and when the default is empty too
			// (Custom Height defaults to a blank px value), there is no fixed
			// size: `auto`. Without that stop the call recursed forever.
			// Mirrors resolveCustomAspectCssSize() in customAspectRatio.js.
			if ( '' === $value || null === $value ) {
				return $raw === $fallback ? 'auto' : self::resolve_css_size( $fallback, $fallback, $allowed_units, $device );
			}

			if ( ! is_numeric( $value ) ) {
				return 'auto';
			}

			return (string) ( 0 + $value ) . $unit;
		}

		/**
		 * Normalize a custom width/height size object.
		 *
		 * Legacy unitless integers (old ratio semantics) reset to the new defaults
		 * so `100`/`80` are not misread as `100px`/`80px`.
		 *
		 * @param mixed                $value Raw value.
		 * @param array<string, mixed> $fallback Default responsive size.
		 * @param array<int, string>   $allowed_units Allowed units.
		 * @return array<string, mixed>
		 */
		private function normalize_custom_size( $value, array $fallback, array $allowed_units ) {
			if ( is_int( $value ) || is_float( $value ) || ( is_string( $value ) && is_numeric( $value ) ) ) {
				return $fallback;
			}

			if ( ! is_array( $value ) ) {
				return $fallback;
			}

			$devices = array( 'Desktop', 'Tablet', 'Mobile' );
			$device  = array();
			$unit    = array();
			$min     = LogoImagesDefaults::CUSTOM_ASPECT_RATIO_MIN;
			$max     = LogoImagesDefaults::CUSTOM_ASPECT_RATIO_MAX;

			foreach ( $devices as $name ) {
				$fallback_value = isset( $fallback['device'][ $name ] ) ? $fallback['device'][ $name ] : ( isset( $fallback['device']['Desktop'] ) ? $fallback['device']['Desktop'] : '' );
				$fallback_unit  = isset( $fallback['unit'][ $name ] ) ? (string) $fallback['unit'][ $name ] : ( isset( $fallback['unit']['Desktop'] ) ? (string) $fallback['unit']['Desktop'] : $allowed_units[0] );

				$raw_value = isset( $value['device'][ $name ] ) ? $value['device'][ $name ] : $fallback_value;
				$raw_unit  = isset( $value['unit'][ $name ] ) ? (string) $value['unit'][ $name ] : $fallback_unit;

				if ( ! in_array( $raw_unit, $allowed_units, true ) ) {
					$raw_unit = $fallback_unit;
				}

				if ( 'auto' === $raw_unit ) {
					$device[ $name ] = '';
					$unit[ $name ]   = 'auto';
					continue;
				}

				if ( '' === $raw_value || null === $raw_value ) {
					$device[ $name ] = $fallback_value;
					$unit[ $name ]   = $raw_unit;
					continue;
				}

				if ( ! is_numeric( $raw_value ) ) {
					$device[ $name ] = $fallback_value;
					$unit[ $name ]   = $raw_unit;
					continue;
				}

				$number = 0 + $raw_value;
				if ( $number < $min || $number > $max ) {
					$device[ $name ] = $fallback_value;
					$unit[ $name ]   = $raw_unit;
					continue;
				}

				$device[ $name ] = $number;
				$unit[ $name ]   = $raw_unit;
			}

			return array(
				'device' => $device,
				'unit'   => $unit,
			);
		}

		/**
		 * Normalize an enum string.
		 *
		 * @param mixed            $value Raw value.
		 * @param array<int, string> $allowed Allowed values.
		 * @param string           $fallback Fallback.
		 * @return string
		 */
		private function normalize_enum( $value, array $allowed, $fallback ) {
			$value = is_scalar( $value ) ? trim( (string) $value ) : '';
			return ( '' !== $value && in_array( $value, $allowed, true ) ) ? $value : $fallback;
		}

		/**
		 * Normalize a boolean.
		 *
		 * @param mixed $value Raw value.
		 * @param bool  $fallback Fallback.
		 * @return bool
		 */
		private function normalize_boolean( $value, $fallback ) {
			if ( is_bool( $value ) ) {
				return $value;
			}
			if ( null === $value || '' === $value ) {
				return (bool) $fallback;
			}
			$normalized = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			return null === $normalized ? (bool) $fallback : (bool) $normalized;
		}
	}
}
