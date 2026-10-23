<?php
/**
 * Resolve Swiper layout profile branches.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\LayoutDefaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SwiperLayoutProfileResolver' ) ) {
	class SwiperLayoutProfileResolver {
		public function resolve( array $layout, $style ) {
			$defaults    = LayoutDefaults::get_layout_defaults();
			$profile_key = $this->get_profile_key( $style );
			$shared      = isset( $layout['shared'] ) && is_array( $layout['shared'] ) ? $layout['shared'] : array();
			$profile     = isset( $layout[ $profile_key ] ) && is_array( $layout[ $profile_key ] ) ? $layout[ $profile_key ] : array();
			$resolved    = $defaults;

			$resolved['carouselStyle'] = $style;
			foreach ( $this->get_shared_fields() as $field ) {
				$legacy_profile_fallback = in_array( $field, array( 'partialSlideView', 'variableWidth' ), true )
					? $this->resolve_field_value( $layout, $profile, $field, $defaults['shared'][ $field ] ?? $defaults[ $field ] ?? null )
					: ( $defaults['shared'][ $field ] ?? $defaults[ $field ] ?? null );
				$resolved[ $field ]      = $this->resolve_field_value( $layout, $shared, $field, $legacy_profile_fallback );
			}

			foreach ( $this->get_profile_fields( $profile_key ) as $field ) {
				$resolved[ $field ] = $this->resolve_field_value( $layout, $profile, $field, $defaults[ $profile_key ][ $field ] ?? $defaults[ $field ] ?? null );
			}

			$resolved['shared']       = array_replace_recursive( $defaults['shared'], $shared );
			$resolved[ $profile_key ] = array_replace_recursive( isset( $defaults[ $profile_key ] ) && is_array( $defaults[ $profile_key ] ) ? $defaults[ $profile_key ] : array(), $profile );
			$resolved['standard']     = isset( $layout['standard'] ) && is_array( $layout['standard'] ) ? $layout['standard'] : ( isset( $defaults['standard'] ) && is_array( $defaults['standard'] ) ? $defaults['standard'] : array() );
			$resolved['staggered']    = isset( $layout['staggered'] ) && is_array( $layout['staggered'] ) ? $layout['staggered'] : ( isset( $defaults['staggered'] ) && is_array( $defaults['staggered'] ) ? $defaults['staggered'] : array() );
			$resolved['center']       = isset( $layout['center'] ) && is_array( $layout['center'] ) ? $layout['center'] : ( isset( $defaults['center'] ) && is_array( $defaults['center'] ) ? $defaults['center'] : array() );
			$resolved['multirow']     = isset( $layout['multirow'] ) && is_array( $layout['multirow'] ) ? array_replace_recursive( $defaults['multirow'], $layout['multirow'] ) : ( isset( $defaults['multirow'] ) && is_array( $defaults['multirow'] ) ? $defaults['multirow'] : array() );

			if ( 'multirow' === $profile_key ) {
				$orientation_key                           = 'vertical' === $resolved['displayOrientation'] ? 'vertical' : 'horizontal';
				$orientation_defaults                      = isset( $defaults['multirow'][ $orientation_key ] ) && is_array( $defaults['multirow'][ $orientation_key ] ) ? $defaults['multirow'][ $orientation_key ] : array();
				$orientation_branch                        = isset( $layout['multirow'][ $orientation_key ] ) && is_array( $layout['multirow'][ $orientation_key ] ) ? $layout['multirow'][ $orientation_key ] : array();
				$resolved['rows']                          = $this->resolve_field_value( $resolved, $orientation_branch, 'rows', isset( $orientation_defaults['rows'] ) ? $orientation_defaults['rows'] : $defaults['rows'] );
				$resolved['columns']                       = $this->resolve_field_value( $resolved, $orientation_branch, 'columns', isset( $orientation_defaults['columns'] ) ? $orientation_defaults['columns'] : $defaults['columns'] );
				$resolved['multirow'][ $orientation_key ] = array_replace_recursive( $orientation_defaults, $orientation_branch );
			}

			return $resolved;
		}

		public function get_profile_key( $style ) {
			switch ( $style ) {
				case 'staggered-carousel':
					return 'staggered';
				case 'center':
					return 'center';
				case 'multirow':
					return 'multirow';
				default:
					return 'standard';
			}
		}

		private function resolve_field_value( array $layout, array $branch, $field, $fallback ) {
			if ( array_key_exists( $field, $branch ) && null !== $branch[ $field ] ) {
				return $branch[ $field ];
			}
			if ( array_key_exists( $field, $layout ) && null !== $layout[ $field ] ) {
				return $layout[ $field ];
			}
			return $fallback;
		}
		private function get_shared_fields() { return array( 'displayOrientation', 'partialSlideView', 'variableWidth', 'gapBetweenLogos', 'alignment', 'edgeFade', 'autoplay', 'autoplayDelay', 'sliderSpeed', 'pauseOnHover', 'adaptiveHeight', 'infiniteLoop', 'navigationArrows', 'paginationDots', 'ajaxLiveFilter', 'ajaxSearch', 'filterSearchOrientation', 'filterSearchGap', 'justifyFilterSearch' ); }
		private function get_profile_fields( $profile_key ) {
			switch ( $profile_key ) {
				case 'staggered':
					return array( 'columns', 'rows', 'staggeredAnimation', 'randomLogoAnimation', 'easing' );
				case 'center':
				case 'multirow':
					return array( 'columns', 'rows', 'carouselDirection', 'slideToScroll', 'slidingEffects', 'carouselStartsOnScreen', 'tabsAndKeyNavigation', 'mouseWheelControl', 'freeScrollMode', 'centeredLogoScale', 'slideLogosOpacity' );
				default:
					return array( 'columns', 'rows', 'carouselDirection', 'slideToScroll', 'slidingEffects', 'carouselStartsOnScreen', 'tabsAndKeyNavigation', 'mouseWheelControl', 'freeScrollMode' );
			}
		}
	}
}
