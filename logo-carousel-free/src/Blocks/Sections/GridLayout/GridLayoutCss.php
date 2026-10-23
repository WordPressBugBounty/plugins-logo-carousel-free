<?php
/**
 * Server-side Grid layout CSS.
 *
 * Mirrors the editor emitter `gridLayoutDynamicCss.js` assertion-for-assertion
 * (same selectors, values, device buckets). The free Grid has one style,
 * Standard Grid: a plain flex-wrap grid. Grid Strip, Staggered and Zigzag are
 * Pro (`GridFreeGate` renders them as Standard), so they have no CSS here.
 * Targets the shared logo-source item selectors so editor preview and server
 * render style identically.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\GridLayout;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\GridLayoutDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Support\ResponsiveCount;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GridLayoutCss' ) ) {
	/**
	 * Grid layout rules mirrored from the editor emitter.
	 */
	class GridLayoutCss {
		/**
		 * Base (Desktop) rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function base_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Desktop' );
		}

		/**
		 * Tablet rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function tablet_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Tablet' );
		}

		/**
		 * Mobile rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Mobile' );
		}

		/**
		 * Build rules for a device bucket.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @param string                $device Device key (Desktop|Tablet|Mobile).
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		private static function get_rules( array $selectors, array $attributes, $device ) {
			$layout  = isset( $attributes['layout'] ) && is_array( $attributes['layout'] ) ? $attributes['layout'] : array();
			$shared  = isset( $layout['shared'] ) && is_array( $layout['shared'] ) ? $layout['shared'] : array();
			$is_base = 'Desktop' === $device;

			// Columns are responsive: a device with no value of its own inherits
			// Desktop, so existing content renders unchanged. A block that saved
			// no columns at all gets the schema default for this device (6 / 4 /
			// 3) — WordPress fills defaults only for a wholly unsaved `layout`.
			$columns   = ResponsiveCount::resolve( self::shared_first( $shared, $layout, 'columns', null ), $device, self::default_columns( $device ), 1, 50 );
			$justify   = (string) self::shared_first( $shared, $layout, 'justify', 'center' );
			$alignment = (string) self::shared_first( $shared, $layout, 'alignment', 'start' );
			$h_gap     = self::single( self::shared_first_array( $shared, $layout, 'horizontalGap' ), $device );
			$v_gap     = self::single( self::shared_first_array( $shared, $layout, 'verticalGap' ), $device );

			// LogoImagesCss's aspect-ratio rules scope the figure as
			// `.sp-lcpro-logo-carousel[data-sp-lcpro-aspect-ratio="..."] .sp-lcpro-logo-source__figure`
			// — one extra type+attribute selector beyond our plain `logoSourceFigure`,
			// so its `padding`/`width`/`box-sizing` always won regardless of source
			// order (higher specificity beats later rules). `[data-sp-lcpro-aspect-ratio]`
			// (no value) matches the attribute in EITHER mode (original or fixed) at the
			// SAME specificity as those rules, so ours — emitted after, in both
			// composers — wins the tie via source order.
			$figure_selector = $selectors['wrapper'] . ' .sp-lcpro-logo-carousel[data-sp-lcpro-aspect-ratio] .sp-lcpro-logo-source__figure';

			$rules = array();

			// "Original" aspect mode normally forces every logo image to an identical
			// fixed height (`height: var(--sp-lcpro-slot-height, 52px)`), so every row
			// ends up the same height and Alignment (vertical, per-row) has nothing to
			// align. For Grid — where columns are locked to equal width — this is the
			// ONLY mode where natural per-logo height variance is even possible (fixed
			// aspect-ratio modes lock height to the shared column width, so they stay
			// intentionally uniform). Overriding to an intrinsic, capped height lets
			// each logo's own proportions show through, making Alignment meaningful.
			// Qualified with `="original"` (exact match) — fixed-ratio modes are left
			// untouched, matching LogoImagesCss's own selector so specificity ties.
			if ( $is_base ) {
				$rules[] = array(
					'selector' => $selectors['wrapper'] . ' .sp-lcpro-logo-carousel[data-sp-lcpro-aspect-ratio="original"] .sp-lcpro-logo-source__image',
					'styles'   => array(
						'height'     => 'auto',
						'max-height' => 'var(--sp-lcpro-slot-height, 52px)',
						'width'      => 'auto',
						'max-width'  => '100%',
						'object-fit' => 'contain',
					),
				);
			}

			// Grid container — flex-wrap model (NOT `repeat(cols, 1fr)`). Even
			// `flex-basis` columns make full rows fill exactly, so `justify-content`
			// only redistributes the LAST (incomplete) row — matching the Figma
			// Justify Style (Left/Center/Right). `align-items` aligns each wrapped
			// line's items vertically. CSS Grid can't align an incomplete row, which
			// is why Justify/Alignment appeared to "do nothing" before.
			$container = array();
			if ( '' !== $h_gap ) {
				$container['column-gap'] = $h_gap;
			}
			if ( '' !== $v_gap ) {
				$container['row-gap'] = $v_gap;
			}
			if ( $is_base ) {
				$container['display']         = 'flex';
				$container['flex-wrap']       = 'wrap';
				$container['justify-content'] = self::flex_justify( $justify );
				$container['align-items']     = self::flex_align( $alignment );
			}

			if ( ! empty( $container ) ) {
				$rules[] = array(
					'selector' => $selectors['logoSourceItems'],
					'styles'   => $container,
				);
			}

			// Even columns: each item takes 1/cols of the row minus the shared gaps,
			// so `cols` items + `cols-1` gaps fill 100% exactly (full rows never leave
			// slack for justify-content to move). Emitted per device — the gap is
			// responsive, columns are not.
			$basis_gap = '' !== $h_gap ? $h_gap : '0px';
			$basis     = 'calc((100% - ' . ( $columns - 1 ) . ' * ' . $basis_gap . ') / ' . $columns . ')';
			$rules[]   = array(
				'selector' => $selectors['logoSourceItem'],
				'styles'   => array(
					'flex'       => '0 0 ' . $basis,
					'max-width'  => $basis,
					'min-width'  => '0',
					'box-sizing' => 'border-box',
				),
			);

			// Individual logo cells have no border by default. Scoped with
			// `:not(--has-display-elements)` — the logo card owns its border
			// (`logoCard` selector) when it has a surface; an UNSCOPED
			// `border: none` here would out-rank that selector on specificity
			// (this figure selector carries an extra `[data-sp-lcpro-aspect-ratio]`
			// ancestor qualifier, pushing it past `logoCard`'s 2-class
			// specificity) and silently kill the card border.
			if ( $is_base ) {
				$rules[] = array(
					'selector' => $figure_selector . ':not(.sp-lcpro-logo-source__figure--has-display-elements)',
					'styles'   => array(
						'border' => 'none',
					),
				);
			}

			return $rules;
		}

		/**
		 * Map the Justify Style value to a flexbox `justify-content` keyword.
		 *
		 * @param string $value Stored value (start|center|end).
		 * @return string
		 */
		private static function flex_justify( $value ) {
			$map = array(
				'start'  => 'flex-start',
				'center' => 'center',
				'end'    => 'flex-end',
			);
			return isset( $map[ $value ] ) ? $map[ $value ] : 'center';
		}

		/**
		 * Map the Alignment value to a flexbox `align-items` keyword.
		 *
		 * @param string $value Stored value (start|center|end|stretch).
		 * @return string
		 */
		private static function flex_align( $value ) {
			$map = array(
				'start'   => 'flex-start',
				'center'  => 'center',
				'end'     => 'flex-end',
				'stretch' => 'stretch',
			);
			return isset( $map[ $value ] ) ? $map[ $value ] : 'flex-start';
		}

		/**
		 * Default column count for a device when the block saved none.
		 *
		 * Read from the schema defaults so the two can never disagree.
		 *
		 * @param string $device Device key (Desktop|Tablet|Mobile).
		 * @return int
		 */
		private static function default_columns( $device ) {
			$defaults = GridLayoutDefaults::get_shared_layout_defaults();

			return isset( $defaults['columns']['device'][ $device ] ) ? (int) $defaults['columns']['device'][ $device ] : 6;
		}

		/**
		 * Read a scalar field shared-first, then top-level, then fallback.
		 *
		 * @param array<string, mixed> $shared Shared layout branch.
		 * @param array<string, mixed> $layout Top-level layout branch.
		 * @param string               $key Field key.
		 * @param mixed                $fallback Fallback value.
		 * @return mixed
		 */
		private static function shared_first( array $shared, array $layout, $key, $fallback ) {
			if ( array_key_exists( $key, $shared ) && '' !== $shared[ $key ] ) {
				return $shared[ $key ];
			}
			if ( array_key_exists( $key, $layout ) && '' !== $layout[ $key ] ) {
				return $layout[ $key ];
			}
			return $fallback;
		}

		/**
		 * Read a responsive-object field shared-first, then top-level.
		 *
		 * @param array<string, mixed> $shared Shared layout branch.
		 * @param array<string, mixed> $layout Top-level layout branch.
		 * @param string               $key Field key.
		 * @return array<string, mixed>
		 */
		private static function shared_first_array( array $shared, array $layout, $key ) {
			if ( isset( $shared[ $key ] ) && is_array( $shared[ $key ] ) ) {
				return $shared[ $key ];
			}
			if ( isset( $layout[ $key ] ) && is_array( $layout[ $key ] ) ) {
				return $layout[ $key ];
			}
			return array();
		}

		/**
		 * Single responsive value (number + unit) for a device.
		 *
		 * @param array<string, mixed> $responsive Responsive value.
		 * @param string               $device Device key.
		 * @return string
		 */
		private static function single( array $responsive, $device ) {
			$value = isset( $responsive['device'][ $device ] ) ? $responsive['device'][ $device ] : '';

			if ( '' === $value || null === $value ) {
				return '';
			}

			$unit = isset( $responsive['unit'][ $device ] ) ? (string) $responsive['unit'][ $device ] : 'px';

			return $value . $unit;
		}
	}
}
