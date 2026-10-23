<?php
/**
 * Default values for the Grid layout branch.
 *
 * Profile-shaped: a top-level `gridStyle` selects a style
 * (standard / strip / staggered / zigzag); `shared` holds cross-style layout
 * (columns, gaps, justify, alignment), and each style object holds only its own
 * fields. Grid is a static CSS grid — the styles differ only in CSS arrangement
 * (bordered cells for Strip, transform offsets for Staggered / Zigzag), so there
 * is no per-style runtime.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GridLayoutDefaults' ) ) {
	/**
	 * Grid layout default provider.
	 */
	class GridLayoutDefaults {
		/**
		 * Allowed Grid style profiles.
		 *
		 * @return array<int, string>
		 */
		public static function get_grid_style_options() {
			return array( 'standard', 'strip', 'staggered', 'zigzag' );
		}

		/**
		 * Allowed justify (justify-items) values.
		 *
		 * @return array<int, string>
		 */
		public static function get_justify_options() {
			return array( 'start', 'center', 'end' );
		}

		/**
		 * Allowed alignment (align-items) values.
		 *
		 * @return array<int, string>
		 */
		public static function get_alignment_options() {
			return array( 'start', 'center', 'end', 'stretch' );
		}

		/**
		 * Allowed line (border) styles for the Grid Strip cell.
		 *
		 * @return array<int, string>
		 */
		public static function get_line_style_options() {
			return array( 'none', 'solid', 'dashed', 'dotted', 'double' );
		}

		/**
		 * Single responsive value default shape.
		 *
		 * @param mixed  $desktop Desktop value.
		 * @param mixed  $tablet Tablet value.
		 * @param mixed  $mobile Mobile value.
		 * @param string $unit Shared unit.
		 * @return array<string, mixed>
		 */
		private static function single_responsive_default( $desktop, $tablet, $mobile, $unit = 'px' ) {
			return array(
				'device' => array(
					'Desktop' => $desktop,
					'Tablet'  => $tablet,
					'Mobile'  => $mobile,
				),
				'unit'   => array(
					'Desktop' => $unit,
					'Tablet'  => $unit,
					'Mobile'  => $unit,
				),
			);
		}

		/**
		 * Responsive count default shape (unitless — columns, rows).
		 *
		 * @param int      $desktop Desktop value.
		 * @param int|null $tablet Tablet value (defaults to Desktop).
		 * @param int|null $mobile Mobile value (defaults to Tablet).
		 * @return array<string, mixed>
		 */
		private static function responsive_count_default( $desktop, $tablet = null, $mobile = null ) {
			$tablet = null === $tablet ? $desktop : $tablet;
			$mobile = null === $mobile ? $tablet : $mobile;

			return array(
				'device' => array(
					'Desktop' => (int) $desktop,
					'Tablet'  => (int) $tablet,
					'Mobile'  => (int) $mobile,
				),
			);
		}

		/**
		 * Shared layout defaults (apply across all Grid styles).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_shared_layout_defaults() {
			return array(
				// 6 across on Desktop, stepping down to 4 on Tablet and 3 on Mobile.
				'columns'                 => self::responsive_count_default( 6, 4, 3 ),
				'horizontalGap'           => self::single_responsive_default( 15, 15, 15, 'px' ),
				'verticalGap'             => self::single_responsive_default( 15, 15, 15, 'px' ),
				'justify'                 => 'center',
				'alignment'               => 'start',
				'pagination'              => false,
				'ajaxLiveFilter'          => false,
				'ajaxSearch'              => false,
				'filterSearchOrientation' => 'inline',
				'filterSearchGap'         => self::single_responsive_default( 15, 15, 15, 'px' ),
				'justifyFilterSearch'     => 'flex-start',
			);
		}

		/**
		 * Standard style defaults (uses shared only).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_standard_layout_defaults() {
			return array();
		}

		/**
		 * Responsive spacing default (device-keyed 4-side box) — the shape the
		 * shared Border/Spacing controls read for Grid Strip line thickness.
		 *
		 * @param mixed  $value Desktop value applied to all four sides.
		 * @param string $unit Shared unit.
		 * @return array<string, mixed>
		 */
		private static function spacing_default( $value, $unit = 'px' ) {
			$sides = array(
				'top'    => $value,
				'right'  => $value,
				'bottom' => $value,
				'left'   => $value,
			);
			return array(
				'device'    => array(
					'Desktop' => $sides,
					'Tablet'  => $sides,
					'Mobile'  => $sides,
				),
				'unit'      => array(
					'Desktop' => $unit,
					'Tablet'  => $unit,
					'Mobile'  => $unit,
				),
				'allChange' => true,
			);
		}

		/**
		 * Grid Strip per-state (Normal / Hover) defaults — mirrors the shared
		 * Border control shape (`border` object + `borderWidth` spacing).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_strip_state_defaults() {
			return array(
				'border'      => array(
					'style' => 'solid',
					'color' => '#dddddd',
				),
				'borderWidth' => self::spacing_default( 1, 'px' ),
			);
		}

		/**
		 * Grid Strip style defaults (bordered cell).
		 *
		 * Normal / Hover states reuse the shared Border control; Inner Padding is a
		 * single shared field applied to the cell in both states.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_strip_layout_defaults() {
			return array(
				'normal'       => self::get_strip_state_defaults(),
				'hover'        => self::get_strip_state_defaults(),
				'innerPadding' => self::single_responsive_default( 12, 12, 12, 'px' ),
			);
		}

		/**
		 * Staggered style defaults (alternate columns offset vertically).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_staggered_layout_defaults() {
			return array(
				'preserveLayoutGaps' => 20,
			);
		}

		/**
		 * Zigzag style defaults (alternate rows offset horizontally).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_zigzag_layout_defaults() {
			return array(
				'preserveLayoutGaps' => 10,
			);
		}

		/**
		 * Full Grid layout branch defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_layout_defaults() {
			return array(
				'gridStyle' => 'standard',
				'shared'    => self::get_shared_layout_defaults(),
				'standard'  => self::get_standard_layout_defaults(),
				'strip'     => self::get_strip_layout_defaults(),
				'staggered' => self::get_staggered_layout_defaults(),
				'zigzag'    => self::get_zigzag_layout_defaults(),
			);
		}
	}
}
