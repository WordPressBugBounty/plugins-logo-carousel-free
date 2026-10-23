<?php
/**
 * Section-level wrapper CSS concern.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns;

use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssValueHelper;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SectionCss' ) ) {
	/**
	 * Shared wrapper background, padding, and margin rules.
	 */
	class SectionCss {
		// The margin schema leaf (`CommonAttributeGroups::section()`) has no
		// server-side numeric bound — negative margin is a legitimate,
		// intentional CSS technique (e.g. pulling the section up to overlap a
		// hero above it), so this can't just floor at 0 like padding. But an
		// unbounded negative value reaching this far (REST override, raw
		// attribute write) can overlap the section's OWN children or bleed into
		// sibling markup outside the block. -1000px is generous for any real
		// overlap use case while still bounding a runaway/malformed value.
		const MARGIN_MIN_PX = -1000;
		/**
		 * Base section rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function base_rules( array $selectors, array $attributes ) {
			$section = isset( $attributes['section'] ) && is_array( $attributes['section'] ) ? $attributes['section'] : array();
			$wrapper = isset( $selectors['wrapper'] ) ? (string) $selectors['wrapper'] : '';

			return array(
				array(
					'selector' => $selectors['wrapper'],
					'styles'   => array(
						'background' => CssValueHelper::background_value( isset( $section['background'] ) && is_array( $section['background'] ) ? $section['background'] : array() ),
						// `box-sizing` travels with `padding` on this wrapper. Block
						// themes size the block root from the theme.json layout (a
						// `max-width` content column, or `width: 100%` when aligned
						// full) and do not all ship a universal border-box reset, so
						// under the default content-box the padding is added OUTSIDE
						// that width and the block spills past its column. Emitted
						// here, next to the padding that causes it, and scoped by
						// #uniqueId so no theme rule can undo it.
						'box-sizing' => 'border-box',
						'padding'    => CssValueHelper::responsive_spacing( isset( $section['padding'] ) && is_array( $section['padding'] ) ? $section['padding'] : array(), 'Desktop' ),
						'margin'     => CssValueHelper::responsive_spacing( isset( $section['margin'] ) && is_array( $section['margin'] ) ? $section['margin'] : array(), 'Desktop', self::MARGIN_MIN_PX ),
					),
				),
				// Family-wide frontend: no focus / focus-visible chrome on any
				// interactive control (links, popup buttons, nav, filter, search,
				// pagination, Display Elements title/read-more). Scoped by
				// #uniqueId so it outranks theme `a:focus` / `button:focus` rings.
				array(
					'selector' => $wrapper . ' a:focus, ' . $wrapper . ' a:focus-visible, ' . $wrapper . ' button:focus, ' . $wrapper . ' button:focus-visible, ' . $wrapper . ' [tabindex]:focus, ' . $wrapper . ' [tabindex]:focus-visible, ' . $wrapper . ' input:focus, ' . $wrapper . ' input:focus-visible, ' . $wrapper . ' select:focus, ' . $wrapper . ' select:focus-visible',
					'styles'   => array(
						'outline'    => 'none',
						'box-shadow' => 'none',
					),
				),
			);
		}

		/**
		 * Tablet section rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function tablet_rules( array $selectors, array $attributes ) {
			$section = isset( $attributes['section'] ) && is_array( $attributes['section'] ) ? $attributes['section'] : array();

			return array(
				array(
					'selector' => $selectors['wrapper'],
					'styles'   => array(
						'padding' => CssValueHelper::responsive_spacing( isset( $section['padding'] ) && is_array( $section['padding'] ) ? $section['padding'] : array(), 'Tablet' ),
						'margin'  => CssValueHelper::responsive_spacing( isset( $section['margin'] ) && is_array( $section['margin'] ) ? $section['margin'] : array(), 'Tablet', self::MARGIN_MIN_PX ),
					),
				),
			);
		}

		/**
		 * Mobile section rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_rules( array $selectors, array $attributes ) {
			$section = isset( $attributes['section'] ) && is_array( $attributes['section'] ) ? $attributes['section'] : array();

			return array(
				array(
					'selector' => $selectors['wrapper'],
					'styles'   => array(
						'padding' => CssValueHelper::responsive_spacing( isset( $section['padding'] ) && is_array( $section['padding'] ) ? $section['padding'] : array(), 'Mobile' ),
						'margin'  => CssValueHelper::responsive_spacing( isset( $section['margin'] ) && is_array( $section['margin'] ) ? $section['margin'] : array(), 'Mobile', self::MARGIN_MIN_PX ),
					),
				),
			);
		}
	}
}
