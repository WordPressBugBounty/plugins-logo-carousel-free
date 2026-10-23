<?php
/**
 * Navigation Arrows Style-tab CSS concern (server side).
 *
 * Ownership: the arrow BUTTON box paint — background, icon color (`color`, the
 * SVG inherits via currentColor), border, border-radius, inner padding, margin
 * — for Normal and Hover. Placement (position/gap/size vars) stays in the
 * static `_navigation.scss` partial.
 *
 * PARITY: byte-identical with the editor emitter
 * `blocks/blocks/shared/styles/concerns/navigationDynamicCss.js` — same
 * selectors, value resolution, property order, and device buckets. Enforced by
 * CarouselFamilyDynamicCssTest.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns;

use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssUtils;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssValueHelper;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'NavigationCss' ) ) {
	/**
	 * Navigation arrow style rules mirrored from the editor CSS concern.
	 */
	class NavigationCss {
		/**
		 * Base (Desktop) rules: full paint + Desktop box values.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function base_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Desktop' );
		}

		/**
		 * Tablet rules: responsive box values only.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function tablet_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Tablet' );
		}

		/**
		 * Mobile rules: responsive box values only.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Mobile' );
		}

		/**
		 * Build the navigation arrow rules for a device bucket.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @param string                $device Device key (Desktop|Tablet|Mobile).
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		private static function get_rules( array $selectors, array $attributes, $device ) {
			$navigation = isset( $attributes['navigation'] ) && is_array( $attributes['navigation'] ) ? $attributes['navigation'] : array();
			$background = isset( $navigation['background'] ) && is_array( $navigation['background'] ) ? $navigation['background'] : array();
			$icon_color = isset( $navigation['iconColor'] ) && is_array( $navigation['iconColor'] ) ? $navigation['iconColor'] : array();
			$border     = isset( $navigation['border'] ) && is_array( $navigation['border'] ) ? $navigation['border'] : array();
			$is_desktop = 'Desktop' === $device;

			$border_style = isset( $border['style'] ) && 'none' !== $border['style'] ? (string) $border['style'] : '';
			$nav_style    = isset( $navigation['navStyle'] ) ? (string) $navigation['navStyle'] : 'style-1';
			$is_grouped   = 'style-2' === $nav_style;

			$rules = array(
				array(
					'selector' => $selectors['nav'],
					'styles'   => array(
						'color'         => $is_desktop ? CssUtils::sanitize_css_color( isset( $icon_color['normal'] ) ? $icon_color['normal'] : '', '' ) : '',
						'background'    => $is_desktop ? self::resolve_background( isset( $background['normal'] ) && is_array( $background['normal'] ) ? $background['normal'] : array() ) : '',
						'border-style'  => $is_desktop ? $border_style : '',
						'border-color'  => $is_desktop ? CssUtils::sanitize_css_color( isset( $border['color'] ) ? $border['color'] : '', '' ) : '',
						'border-width'  => self::spacing( isset( $navigation['borderWidth'] ) && is_array( $navigation['borderWidth'] ) ? $navigation['borderWidth'] : array(), $device ),
						'border-radius' => self::spacing( isset( $navigation['borderRadius'] ) && is_array( $navigation['borderRadius'] ) ? $navigation['borderRadius'] : array(), $device ),
						'padding'       => self::spacing( isset( $navigation['innerPadding'] ) && is_array( $navigation['innerPadding'] ) ? $navigation['innerPadding'] : array(), $device ),
						'margin'        => $is_grouped ? self::spacing( isset( $navigation['margin'] ) && is_array( $navigation['margin'] ) ? $navigation['margin'] : array(), $device ) : '',
					),
				),
			);

			// Arrow size is the one navigation box value that is genuinely
			// responsive, so it is emitted here instead of inline on the frame
			// with the gap/offset vars: an inline style cannot carry a
			// per-breakpoint value, and it would outrank any media query that
			// tried to correct it — which is exactly why the Tablet/Mobile
			// sizes did nothing. Desktop falls back to the schema default so
			// the arrows never drop to the stylesheet's own fallback; a device
			// with no value of its own simply inherits Desktop.
			$arrow_size = self::icon_size( $navigation, $device );
			if ( '' !== $arrow_size && isset( $selectors['navFrame'] ) ) {
				$rules[] = array(
					'selector' => $selectors['navFrame'],
					'styles'   => array(
						'--sp-lcpro-nav-arrow-size' => $arrow_size,
					),
				);
			}

			if ( $is_desktop ) {
				$rules[] = array(
					'selector' => $selectors['navHover'],
					'styles'   => array(
						'color'        => CssUtils::sanitize_css_color( isset( $icon_color['hover'] ) ? $icon_color['hover'] : '', '' ),
						'background'   => self::resolve_background( isset( $background['hover'] ) && is_array( $background['hover'] ) ? $background['hover'] : array() ),
						'border-color' => CssUtils::sanitize_css_color( isset( $border['hoverColor'] ) ? $border['hoverColor'] : '', '' ),
					),
				);
			}

			return $rules;
		}

		/**
		 * Resolve the arrow size for a device bucket.
		 *
		 * Mirrors `NavigationConfig`'s clamp (8-200px) so the value a user sees
		 * on Desktop is unchanged by the move out of the inline style.
		 *
		 * @param array<string, mixed> $navigation Navigation branch.
		 * @param string               $device Device key (Desktop|Tablet|Mobile).
		 * @return string CSS length, or '' when this device adds nothing.
		 */
		private static function icon_size( array $navigation, $device ) {
			$icon_size = isset( $navigation['iconSize'] ) && is_array( $navigation['iconSize'] ) ? $navigation['iconSize'] : array();
			$value     = isset( $icon_size['device'][ $device ] ) ? $icon_size['device'][ $device ] : null;

			if ( ! is_numeric( $value ) ) {
				return 'Desktop' === $device ? '24px' : '';
			}

			return max( 8, min( 200, (int) $value ) ) . 'px';
		}

		/**
		 * Resolve a background branch into a CSS value (transparent fallback).
		 *
		 * @param array<string, mixed> $background Background branch.
		 * @return string
		 */
		private static function resolve_background( array $background ) {
			$value = CssValueHelper::background_value( $background );

			return '' !== $value ? $value : 'transparent';
		}

		/**
		 * Convert a responsive spacing branch into a CSS shorthand for a device.
		 *
		 * @param array<string, mixed> $spacing Spacing branch.
		 * @param string               $device Device key.
		 * @return string
		 */
		private static function spacing( array $spacing, $device ) {
			$value = CssValueHelper::responsive_spacing( $spacing, $device );

			return is_string( $value ) ? $value : '';
		}
	}
}
