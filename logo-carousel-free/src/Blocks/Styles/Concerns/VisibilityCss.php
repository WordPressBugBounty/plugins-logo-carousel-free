<?php
/**
 * Visibility CSS concern for shared advanced settings — FRONTEND side.
 *
 * Hides the block outright at the chosen device widths, which is the whole
 * point of the setting on a published page.
 *
 * The editor mirror (`concerns/visibilityDynamicCss.js`) deliberately does NOT
 * match this: `display: none` on the canvas would make the block vanish the
 * moment the box was ticked, leaving nothing to select, configure or untick, so
 * the editor paints it as disabled (faded, hatched) instead. The divergence is
 * the feature — don't "restore parity" here.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VisibilityCss' ) ) {
	/**
	 * Emit display rules for visibility toggles.
	 */
	class VisibilityCss {
		/**
		 * Desktop-only visibility rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function desktop_rules( array $selectors, array $attributes ) {
			return self::rule_for_flag( $selectors, $attributes, 'hideOnDesktop' );
		}

		/**
		 * Tablet-only visibility rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function tablet_rules( array $selectors, array $attributes ) {
			return self::rule_for_flag( $selectors, $attributes, 'hideOnTablet' );
		}

		/**
		 * Mobile portrait-only visibility rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_portrait_rules( array $selectors, array $attributes ) {
			return self::rule_for_flag( $selectors, $attributes, 'hideOnMobilePortrait' );
		}

		/**
		 * Mobile landscape-only visibility rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_landscape_rules( array $selectors, array $attributes ) {
			return self::rule_for_flag( $selectors, $attributes, 'hideOnMobileLandscape' );
		}

		/**
		 * Build a display-none rule for a visibility flag.
		 *
		 * Frontend only — see the file docblock for why the editor differs.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @param string                $flag Flag key.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		private static function rule_for_flag( array $selectors, array $attributes, $flag ) {
			$visibility = isset( $attributes['visibility'] ) && is_array( $attributes['visibility'] ) ? $attributes['visibility'] : array();
			$is_hidden  = ! empty( $visibility[ $flag ] );

			if ( ! $is_hidden ) {
				return array();
			}

			return array(
				array(
					'selector' => $selectors['wrapper'],
					'styles'   => array(
						'display' => 'none',
					),
				),
			);
		}
	}
}
