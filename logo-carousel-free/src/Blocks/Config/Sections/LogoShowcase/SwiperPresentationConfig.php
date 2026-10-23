<?php
/**
 * Build Swiper presentation config.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssUtils;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SwiperPresentationConfig' ) ) {
	class SwiperPresentationConfig {
		public function build( array $behavior ) {
			$orientation = ! empty( $behavior['isVertical'] ) ? 'vertical' : 'horizontal';
			$alignment   = $this->resolve_alignment_value( $behavior );
			$edge_fade   = $this->build_edge_fade_presentation( $behavior );
			$styles      = array();
			$classes     = array( 'sp-lcpro-logo-carousel--orientation-' . $orientation, 'sp-lcpro-logo-carousel--align-' . CssUtils::sanitize_wrapper_token( $alignment ) );
			if ( ! empty( $behavior['carouselStartsOnScreen'] ) ) { $classes[] = 'sp-lcpro-logo-carousel--start-on-screen'; }
			if ( ! empty( $edge_fade['enabled'] ) ) { $classes[] = 'sp-lcpro-logo-carousel--edge-fade'; $classes[] = 'sp-lcpro-logo-carousel--edge-fade-' . CssUtils::sanitize_wrapper_token( (string) $edge_fade['direction'] ); $styles = array_merge( $styles, $edge_fade['styles'] ); }
			if ( ! empty( $behavior['isCenter'] ) ) { $classes[] = 'sp-lcpro-logo-carousel--centered'; $styles['--sp-lcpro-center-emphasis'] = isset( $behavior['centeredLogoScale'] ) ? (string) $behavior['centeredLogoScale'] : '1.2'; $styles['--sp-lcpro-center-side-opacity'] = isset( $behavior['slideLogosOpacity'] ) ? (string) $behavior['slideLogosOpacity'] : '0.5'; }
			if ( 'slide' === $behavior['slidingEffects'] ) { $classes[] = 'sp-lcpro-logo-carousel--effect-' . CssUtils::sanitize_wrapper_token( (string) $behavior['slidingEffects'] ); $styles['--sp-lcpro-slide-easing'] = 'ease'; }
			elseif ( 'ease-in-out-slide' === $behavior['slidingEffects'] ) { $classes[] = 'sp-lcpro-logo-carousel--effect-' . CssUtils::sanitize_wrapper_token( (string) $behavior['slidingEffects'] ); $styles['--sp-lcpro-slide-easing'] = 'cubic-bezier(0.42, 0, 0.58, 1)'; }
			elseif ( 'snappy' === $behavior['slidingEffects'] ) { $classes[] = 'sp-lcpro-logo-carousel--effect-' . CssUtils::sanitize_wrapper_token( (string) $behavior['slidingEffects'] ); $styles['--sp-lcpro-slide-easing'] = 'cubic-bezier(0.25, 0, 0, 1)'; }
			elseif ( 'spring-bounce' === $behavior['slidingEffects'] ) { $classes[] = 'sp-lcpro-logo-carousel--effect-' . CssUtils::sanitize_wrapper_token( (string) $behavior['slidingEffects'] ); $styles['--sp-lcpro-slide-easing'] = 'cubic-bezier(0.34, 1.56, 0.64, 1)'; }
			elseif ( in_array( $behavior['slidingEffects'], array( 'fade', 'slide-fade' ), true ) ) { $classes[] = 'sp-lcpro-logo-carousel--effect-' . CssUtils::sanitize_wrapper_token( (string) $behavior['slidingEffects'] ); $styles['--sp-lcpro-fade-duration'] = $this->resolve_speed( $behavior['sliderSpeed'], $behavior['slidingEffects'], ! empty( $behavior['hasCustomSliderSpeed'] ) ) . 'ms'; }
			return array(
				'classes'                => array_values( array_filter( $classes ) ),
				'styles'                 => $styles,
				'orientation'            => array( 'value' => $orientation, 'class' => 'sp-lcpro-logo-carousel--orientation-' . $orientation ),
				'alignment'              => array( 'value' => $alignment, 'class' => 'sp-lcpro-logo-carousel--align-' . CssUtils::sanitize_wrapper_token( $alignment ) ),
				'edgeFade'               => $edge_fade,
				'carouselStartsOnScreen' => ! empty( $behavior['carouselStartsOnScreen'] ),
			);
		}
		private function resolve_speed( $speed, $effect, $has_custom_speed = false ) { $speed_value = max( 0, (int) $speed ); if ( 'slide' === $effect && ! $has_custom_speed ) { return 450; } if ( $speed_value > 0 ) { return $speed_value; } switch ( $effect ) { case 'slide': return 450; case 'snappy': return 220; case 'spring-bounce': return 650; case 'ease-in-out-slide': return 420; case 'fade': return 0; default: return 300; } }
		private function resolve_alignment_value( array $behavior ) { $alignment = isset( $behavior['alignment'] ) && is_array( $behavior['alignment'] ) ? $behavior['alignment'] : array(); if ( ! empty( $behavior['isVertical'] ) ) { $value = isset( $alignment['vertical'] ) ? (string) $alignment['vertical'] : 'center'; return in_array( $value, array( 'left', 'center', 'right' ), true ) ? $value : 'center'; } $value = isset( $alignment['horizontal'] ) ? (string) $alignment['horizontal'] : 'center'; return in_array( $value, array( 'top', 'center', 'bottom' ), true ) ? $value : 'center'; }
		private function build_edge_fade_presentation( array $behavior ) {
			$edge_fade  = isset( $behavior['edgeFade'] ) && is_array( $behavior['edgeFade'] ) ? $behavior['edgeFade'] : array();
			$enabled    = ! empty( $edge_fade['enabled'] );
			$width      = $this->normalize_integer( isset( $edge_fade['width'] ) ? $edge_fade['width'] : null, 0, 0 );
			$strength   = $this->normalize_integer( isset( $edge_fade['strength'] ) ? $edge_fade['strength'] : null, 0, 0 );
			$color_mode = isset( $edge_fade['colorMode'] ) ? (string) $edge_fade['colorMode'] : 'auto';
			$direction  = ! empty( $behavior['isVertical'] ) ? 'vertical' : 'horizontal';
			$color      = 'custom' === $color_mode ? CssUtils::sanitize_css_color( isset( $edge_fade['color'] ) ? $edge_fade['color'] : '', '#ffffff' ) : '#ffffff';
			$opacity    = max( 0, min( 1, $strength / 100 ) );

			if ( ! $enabled || $width <= 0 ) {
				return array(
					'enabled'   => false,
					'direction' => $direction,
					'width'     => $width,
					'opacity'   => $opacity,
					'color'     => $color,
					'styles'    => array(),
				);
			}

			return array(
				'enabled'   => true,
				'direction' => $direction,
				'width'     => $width,
				'opacity'   => $opacity,
				'color'     => $color,
				'styles'    => array(
					'--sp-lcpro-edge-fade-color'   => $color,
					'--sp-lcpro-edge-fade-opacity' => rtrim( rtrim( sprintf( '%.2F', $opacity ), '0' ), '.' ),
					'--sp-lcpro-edge-fade-size'    => $width . 'px',
				),
			);
		}

		/**
		 * Normalize an integer, including legacy `{ "value": N }` range shapes.
		 *
		 * @param mixed $value Raw value.
		 * @param int   $fallback Fallback value.
		 * @param int   $minimum Minimum allowed value.
		 * @return int
		 */
		private function normalize_integer( $value, $fallback, $minimum ) {
			if ( is_array( $value ) && array_key_exists( 'value', $value ) ) {
				$value = $value['value'];
			}

			if ( is_numeric( $value ) ) {
				$value = (int) $value;

				if ( $value >= $minimum ) {
					return $value;
				}
			}

			return (int) $fallback;
		}
	}
}
