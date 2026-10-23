<?php
/**
 * Navigation arrows config normalization.
 *
 * Normalizes the top-level `navigation` attribute branch into render-ready
 * frame classes, CSS variables, and resolved icon markup. Settings-tab fields
 * only (Style tab deferred).
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\SectionConfigInterface;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\Navigation\ArrowIconSvg;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'NavigationConfig' ) ) {
	/**
	 * Normalize the navigation arrows section.
	 */
	class NavigationConfig implements SectionConfigInterface {
		const STYLE_1_POSITIONS = array( 'center', 'center-outer', 'center-inner' );
		const STYLE_2_POSITIONS = array( 'top-center', 'top-right', 'top-left', 'bottom-left', 'bottom-center', 'bottom-right' );
		const ALLOWED_STYLES    = array( 'style-1', 'style-2' );

		/**
		 * Default position per style (the two position sets are disjoint).
		 *
		 * @var array<string, string>
		 */
		const POSITION_DEFAULTS = array(
			'style-1' => 'center-outer',
			'style-2' => 'top-right',
		);

		/**
		 * Build the normalized navigation payload.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @param array<string, mixed> $behavior Normalized swiper behavior (for orientation).
		 * @return array<string, mixed>
		 */
		public function normalize( array $attributes, array $behavior = array() ) {
			$nav = isset( $attributes['navigation'] ) && is_array( $attributes['navigation'] )
				? $attributes['navigation']
				: array();

			$nav_style   = $this->normalize_enum( isset( $nav['navStyle'] ) ? $nav['navStyle'] : '', self::ALLOWED_STYLES, 'style-1' );
			$allowed_pos = 'style-2' === $nav_style ? self::STYLE_2_POSITIONS : self::STYLE_1_POSITIONS;
			$position    = $this->normalize_enum( isset( $nav['position'] ) ? $nav['position'] : '', $allowed_pos, self::POSITION_DEFAULTS[ $nav_style ] );
			$preset_icon = $this->normalize_enum( isset( $nav['presetIcon'] ) ? $nav['presetIcon'] : '', ArrowIconSvg::allowed_keys(), ArrowIconSvg::DEFAULT_KEY );

			$gap    = $this->responsive_desktop( isset( $nav['gapBetweenArrows'] ) ? $nav['gapBetweenArrows'] : null, 10 );
			$offset = $this->responsive_desktop( isset( $nav['horizontalDistance'] ) ? $nav['horizontalDistance'] : null, 6 );

			$gap    = max( 0, min( 200, (int) $gap ) );
			$offset = max( 0, min( 400, (int) $offset ) );

			$show_on_hover  = $this->bool_with_default( isset( $nav['showOnHover'] ) ? $nav['showOnHover'] : null, false );
			$hide_on_mobile = $this->bool_with_default( isset( $nav['hideOnMobile'] ) ? $nav['hideOnMobile'] : null, true );
			$is_vertical    = ! empty( $behavior['isVertical'] );

			$classes = array(
				'sp-lcpro-nav-frame--' . $position,
				'sp-lcpro-nav-frame--' . $nav_style,
			);
			if ( $is_vertical ) {
				$classes[] = 'sp-lcpro-nav-frame--vertical';
			}
			if ( $show_on_hover ) {
				$classes[] = 'sp-lcpro-nav-frame--show-on-hover';
			}
			if ( $hide_on_mobile ) {
				$classes[] = 'sp-lcpro-nav-frame--hide-mobile';
			}

			return array(
				'position'     => $position,
				'navStyle'     => $nav_style,
				'presetIcon'   => $preset_icon,
				'isVertical'   => $is_vertical,
				'showOnHover'  => $show_on_hover,
				'hideOnMobile' => $hide_on_mobile,
				'classes'      => $classes,
				// Arrow size is NOT here: it is responsive, and an inline style
				// can only carry one value — the Desktop one — while also
				// outranking the media queries that would correct it on Tablet
				// and Mobile. `NavigationCss` owns that custom property for all
				// three devices. Gap and offset stay inline: both are
				// Desktop-only today.
				'styles'       => array(
					'--sp-lcpro-nav-gap'    => $gap . 'px',
					'--sp-lcpro-nav-offset' => $offset . 'px',
				),
				'icon'         => ArrowIconSvg::get( $preset_icon ),
			);
		}

		/**
		 * Constrain a value to an allow-list with a fallback.
		 *
		 * @param mixed              $value Raw value.
		 * @param array<int, string> $allowed Allowed values.
		 * @param string             $fallback Fallback value.
		 * @return string
		 */
		private function normalize_enum( $value, array $allowed, $fallback ) {
			$value = is_string( $value ) ? $value : '';

			return in_array( $value, $allowed, true ) ? $value : $fallback;
		}

		/**
		 * Normalize a boolean, falling back to the schema default when unset or
		 * unparseable.
		 *
		 * @param mixed $value    Raw value (null when the key is absent).
		 * @param bool  $fallback Schema default for the field.
		 * @return bool
		 */
		private function bool_with_default( $value, $fallback ) {
			if ( null === $value ) {
				return $fallback;
			}

			$normalized = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

			return null === $normalized ? $fallback : $normalized;
		}

		/**
		 * Read the Desktop value from a single-responsive object.
		 *
		 * @param mixed     $responsive Responsive value object.
		 * @param int|float $fallback Fallback when missing.
		 * @return int|float
		 */
		private function responsive_desktop( $responsive, $fallback ) {
			if ( is_array( $responsive ) && isset( $responsive['device']['Desktop'] ) && is_numeric( $responsive['device']['Desktop'] ) ) {
				return $responsive['device']['Desktop'];
			}

			return $fallback;
		}
	}
}
