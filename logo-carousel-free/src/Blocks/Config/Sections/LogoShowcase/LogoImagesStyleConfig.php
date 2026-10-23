<?php
/**
 * Normalize logo image style configuration.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\LogoImagesDefaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoImagesStyleConfig' ) ) {
	class LogoImagesStyleConfig {
		public function normalize( array $attributes ) {
			$defaults   = LogoImagesDefaults::get_logo_images_style_defaults();
			$raw        = $this->resolve_style_branch( $attributes );
			$raw_normal = isset( $raw['normal'] ) && is_array( $raw['normal'] ) ? $raw['normal'] : array();
			$raw_hover  = isset( $raw['hover'] ) && is_array( $raw['hover'] ) ? $raw['hover'] : array();

			return array(
				'hoverAnimation' => $this->normalize_enum( isset( $raw['hoverAnimation'] ) ? $raw['hoverAnimation'] : null, array( 'none', 'scale-up', 'fade-scale', 'rotate', 'slide-up', 'flip-x', 'border-ring', 'flip-y', 'skew', 'bounce', 'spin-360', 'jelly', 'shake', 'float', 'pulse', 'swing', 'wobble', 'heartbeat' ), $defaults['hoverAnimation'] ),
				'margin'         => $this->normalize_spacing( $this->resolve_style_margin( $raw, $raw_normal, $raw_hover ), $defaults['margin'] ),
				'normal'         => $this->normalize_style_state( $raw_normal, $defaults['normal'] ),
				'hover'          => $this->normalize_style_state( $raw_hover, $defaults['hover'] ),
			);
		}

		private function resolve_style_branch( array $attributes ) {
			if ( isset( $attributes['logoImagesStyle'] ) && is_array( $attributes['logoImagesStyle'] ) ) {
				return $attributes['logoImagesStyle'];
			}
			if ( isset( $attributes['logoImageStyle'] ) && is_array( $attributes['logoImageStyle'] ) ) {
				return $attributes['logoImageStyle'];
			}
			return array();
		}
		private function normalize_style_state( array $state, array $defaults ) {
			$image_filter = isset( $state['imageFilter'] ) && is_array( $state['imageFilter'] ) ? $state['imageFilter'] : array();
			$overlay      = isset( $state['overlay'] ) && is_array( $state['overlay'] ) ? $state['overlay'] : array();
			return array(
				'customColorEnabled' => $this->normalize_boolean( isset( $state['customColorEnabled'] ) ? $state['customColorEnabled'] : null, ! empty( $defaults['customColorEnabled'] ) ),
				'customColor'        => $this->normalize_text( isset( $state['customColor'] ) ? $state['customColor'] : null, isset( $defaults['customColor'] ) ? $defaults['customColor'] : '' ),
				'imageFilter'        => array(
					'blur'       => $this->normalize_integer_in_range( isset( $image_filter['blur'] ) ? $image_filter['blur'] : null, isset( $defaults['imageFilter']['blur'] ) ? $defaults['imageFilter']['blur'] : 0, 0, 20 ),
					'brightness' => $this->normalize_integer_in_range( isset( $image_filter['brightness'] ) ? $image_filter['brightness'] : null, isset( $defaults['imageFilter']['brightness'] ) ? $defaults['imageFilter']['brightness'] : 0, -100, 100 ),
					'contrast'   => $this->normalize_integer_in_range( isset( $image_filter['contrast'] ) ? $image_filter['contrast'] : null, isset( $defaults['imageFilter']['contrast'] ) ? $defaults['imageFilter']['contrast'] : 0, -100, 100 ),
					'saturation' => $this->normalize_integer_in_range( isset( $image_filter['saturation'] ) ? $image_filter['saturation'] : null, isset( $defaults['imageFilter']['saturation'] ) ? $defaults['imageFilter']['saturation'] : 0, -100, 100 ),
					'hue'        => $this->normalize_integer_in_range( isset( $image_filter['hue'] ) ? $image_filter['hue'] : null, isset( $defaults['imageFilter']['hue'] ) ? $defaults['imageFilter']['hue'] : 0, -180, 180 ),
				),
				'opacity'            => $this->normalize_integer_in_range( isset( $state['opacity'] ) ? $state['opacity'] : null, isset( $defaults['opacity'] ) ? $defaults['opacity'] : 100, 0, 100 ),
				'overlay'            => array(
					'type'     => $this->normalize_enum( isset( $overlay['type'] ) ? $overlay['type'] : null, array( 'solid', 'gradient' ), isset( $defaults['overlay']['type'] ) ? $defaults['overlay']['type'] : 'solid' ),
					'color'    => $this->normalize_text( isset( $overlay['color'] ) ? $overlay['color'] : null, isset( $defaults['overlay']['color'] ) ? $defaults['overlay']['color'] : '#000000' ),
					'gradient' => $this->normalize_text( isset( $overlay['gradient'] ) ? $overlay['gradient'] : null, isset( $defaults['overlay']['gradient'] ) ? $defaults['overlay']['gradient'] : '' ),
					'opacity'  => $this->normalize_number_in_range( isset( $overlay['opacity'] ) ? $overlay['opacity'] : null, isset( $defaults['overlay']['opacity'] ) ? $defaults['overlay']['opacity'] : 0, 0, 1 ),
				),
			);
		}
		private function resolve_style_margin( array $raw, array $normal_state, array $hover_state ) {
			if ( isset( $raw['margin'] ) && is_array( $raw['margin'] ) ) {
				return $raw['margin'];
			}
			if ( isset( $normal_state['margin'] ) && is_array( $normal_state['margin'] ) ) {
				return $normal_state['margin'];
			}
			if ( isset( $hover_state['margin'] ) && is_array( $hover_state['margin'] ) ) {
				return $hover_state['margin'];
			}
			return array();
		}
		private function normalize_spacing( $value, array $defaults ) {
			$value = is_array( $value ) ? $value : array();
			$device = isset( $value['device'] ) && is_array( $value['device'] ) ? $value['device'] : array();
			$unit   = isset( $value['unit'] ) && is_array( $value['unit'] ) ? $value['unit'] : array();
			$states = array( 'Desktop', 'Tablet', 'Mobile' );
			$normalized = array( 'device' => array(), 'unit' => array(), 'allChange' => $this->normalize_boolean( isset( $value['allChange'] ) ? $value['allChange'] : null, ! empty( $defaults['allChange'] ) ) );
			foreach ( $states as $state ) {
				$default_device = isset( $defaults['device'][ $state ] ) && is_array( $defaults['device'][ $state ] ) ? $defaults['device'][ $state ] : array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 );
				$raw_device     = isset( $device[ $state ] ) && is_array( $device[ $state ] ) ? $device[ $state ] : array();
				$normalized['device'][ $state ] = array(
					'top'    => isset( $raw_device['top'] ) && is_numeric( $raw_device['top'] ) ? (int) $raw_device['top'] : (int) $default_device['top'],
					'right'  => isset( $raw_device['right'] ) && is_numeric( $raw_device['right'] ) ? (int) $raw_device['right'] : (int) $default_device['right'],
					'bottom' => isset( $raw_device['bottom'] ) && is_numeric( $raw_device['bottom'] ) ? (int) $raw_device['bottom'] : (int) $default_device['bottom'],
					'left'   => isset( $raw_device['left'] ) && is_numeric( $raw_device['left'] ) ? (int) $raw_device['left'] : (int) $default_device['left'],
				);
				$normalized['unit'][ $state ] = isset( $unit[ $state ] ) && is_scalar( $unit[ $state ] ) && '' !== trim( (string) $unit[ $state ] ) ? trim( (string) $unit[ $state ] ) : ( isset( $defaults['unit'][ $state ] ) ? (string) $defaults['unit'][ $state ] : 'px' );
			}
			return $normalized;
		}
		private function normalize_enum( $value, array $allowed, $fallback ) { $value = is_scalar( $value ) ? trim( (string) $value ) : ''; return ( '' !== $value && in_array( $value, $allowed, true ) ) ? $value : $fallback; }
		private function normalize_boolean( $value, $fallback ) { if ( is_bool( $value ) ) { return $value; } if ( null === $value || '' === $value ) { return (bool) $fallback; } $normalized = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ); return null === $normalized ? (bool) $fallback : (bool) $normalized; }
		private function normalize_text( $value, $fallback ) { if ( is_scalar( $value ) ) { $value = trim( (string) $value ); return '' !== $value ? $value : $fallback; } return $fallback; }
		private function normalize_integer_in_range( $value, $fallback, $min, $max ) { if ( is_numeric( $value ) ) { $value = (int) $value; if ( $value >= $min && $value <= $max ) { return $value; } } return (int) $fallback; }
		private function normalize_number_in_range( $value, $fallback, $min, $max ) { if ( is_numeric( $value ) ) { $value = (float) $value; if ( $value >= $min && $value <= $max ) { return $value; } } return (float) $fallback; }
	}
}
