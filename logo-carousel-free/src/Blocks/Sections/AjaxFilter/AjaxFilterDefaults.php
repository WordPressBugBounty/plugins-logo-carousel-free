<?php
/**
 * Ajax Live Filter section defaults.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\AjaxFilter;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AjaxFilterDefaults' ) ) {
	/**
	 * Defaults + allow-lists for the Ajax Live Filter section.
	 */
	class AjaxFilterDefaults {
		/**
		 * Build the full default payload (matches the schema branch shape).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_ajax_filter_defaults() {
			return array(
				'filterStyle' => 'minimal',
				'minimal'     => self::minimal_defaults(),
				'button'      => self::button_defaults(),
				'dropdown'    => self::dropdown_defaults(),
			);
		}

		/**
		 * Minimal (text tabs) profile defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function minimal_defaults() {
			return array(
				'displayCount'      => false,
				'showAll'           => true,
				'allLabel'          => 'All',
				'gapBetweenFilters' => self::single_responsive_default( 8 ),
				'justifyContent'    => 'flex-start',
				'overflowBehavior'  => 'wrap',
				'typography'        => self::typography_default( 16 ),
				'textColor'         => self::color_pair_default( '#1e1e1e', '#2ab674' ),
				'area'              => self::area_defaults( true ),
				'margin'            => self::filter_margin_defaults(),
			);
		}

		/**
		 * Button (chip) profile defaults. The chip is its own paint layer
		 * (per-state background + border + radius) on top of the filter area.
		 *
		 * @return array<string, mixed>
		 */
		public static function button_defaults() {
			return array(
				'displayCount'      => false,
				'showAll'           => true,
				'allLabel'          => 'All',
				'gapBetweenFilters' => self::single_responsive_default( 8 ),
				'justifyContent'    => 'flex-start',
				'overflowBehavior'  => 'wrap',
				'typography'        => self::typography_default( 16 ),
				'chip'              => array(
					'textColor'    => self::color_pair_default( '#1e1e1e', '#ffffff' ),
					'background'   => self::button_background_defaults(),
					'border'       => self::border_state_defaults(),
					'borderWidth'  => self::responsive_box_defaults( 0 ),
					'borderRadius' => self::responsive_box_defaults( 4 ),
				),
				'area'              => self::area_defaults( true ),
				'margin'            => self::filter_margin_defaults(),
			);
		}

		/**
		 * Dropdown (native select) profile defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function dropdown_defaults() {
			return array(
				'filterTitle'      => true,
				'titleLabel'       => 'Category',
				'displayCount'     => false,
				'showAll'          => true,
				'allLabel'         => 'All',
				'fieldWidth'       => self::single_responsive_default( 240 ),
				'titleDropdownGap' => self::single_responsive_default( 12 ),
				'alignment'        => 'left',
				'titleTypography'  => self::typography_default( 18 ),
				'filterTypography' => self::typography_default( 16 ),
				'titleColor'       => self::color_pair_default( '#1e1e1e', '#2ab674' ),
				'filterTextColor'  => self::color_pair_default( '#1e1e1e', '#2ab674' ),
				'field'            => array(
					'background'   => array(
						'style'    => 'solid',
						'solid'    => '#ffffff',
						'gradient' => '',
					),
					'border'       => array(
						'style'      => 'solid',
						'color'      => '#dddddd',
						'hoverColor' => '#dddddd',
					),
					'borderWidth'  => self::responsive_box_defaults( 1 ),
					'borderRadius' => self::responsive_box_defaults( 5 ),
				),
				'area'             => self::area_defaults( false ),
				'margin'           => self::filter_margin_defaults(),
			);
		}

		/**
		 * Filter Area container defaults (the "Filter Area Background" popover).
		 * `width` (Full/Hug) is only present for the minimal/button styles.
		 *
		 * @param bool $with_width Whether to include the Full/Hug width control.
		 * @return array<string, mixed>
		 */
		public static function area_defaults( $with_width = true ) {
			$area = array(
				'background'   => self::single_background_defaults(),
				'border'       => self::border_state_defaults(),
				'borderWidth'  => self::responsive_box_defaults( 0 ),
				'borderRadius' => self::responsive_box_defaults( 0 ),
				'innerPadding' => self::responsive_box_defaults( 0 ),
			);

			if ( $with_width ) {
				$area['width'] = 'full';
			}

			return $area;
		}

		/**
		 * Default vertical gap between the filter bar and carousel body.
		 *
		 * @return array<string, mixed>
		 */
		public static function filter_margin_defaults() {
			$margin                                = self::responsive_box_defaults( 0 );
			$margin['device']['Desktop']['bottom'] = 32;

			return $margin;
		}

		/**
		 * Allowed filter-style keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_filter_style_options() {
			return array( 'minimal', 'button', 'dropdown' );
		}

		/**
		 * Allowed Justify Content keys (minimal / button).
		 *
		 * @return array<int, string>
		 */
		public static function get_justify_content_options() {
			return array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' );
		}

		/**
		 * Allowed Overflow Behavior keys (minimal / button pill rows).
		 *
		 * @return array<int, string>
		 */
		public static function get_overflow_behavior_options() {
			return array( 'wrap', 'scroll' );
		}

		/**
		 * Allowed dropdown Alignment keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_alignment_options() {
			return array( 'left', 'center', 'right' );
		}

		/**
		 * Allowed background-type keys (area / field / chip background style).
		 *
		 * @return array<int, string>
		 */
		public static function get_background_type_options() {
			return array( 'solid', 'gradient' );
		}

		/**
		 * Allowed Filter Area width keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_area_width_options() {
			return array( 'full', 'hug' );
		}

		/**
		 * Single (non-state) background object: type + solid + gradient.
		 *
		 * @return array<string, string>
		 */
		public static function single_background_defaults() {
			return array(
				'style'    => 'solid',
				'solid'    => '',
				'gradient' => '',
			);
		}

		/**
		 * Per-state (Normal/Hover&Active) background — used by the button chip.
		 *
		 * @return array<string, mixed>
		 */
		public static function per_state_background_defaults() {
			return array(
				'normal' => self::single_background_defaults(),
				'hover'  => self::single_background_defaults(),
			);
		}

		/**
		 * Button chip background defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function button_background_defaults() {
			return array(
				'normal' => array(
					'style'    => 'solid',
					'solid'    => '#f3f4f5',
					'gradient' => '',
				),
				'hover'  => array(
					'style'    => 'solid',
					'solid'    => '#315cf6',
					'gradient' => '',
				),
			);
		}

		/**
		 * Border (style + per-state color), mirrors the Navigation/DisplayElements
		 * border shape. `hoverColor` covers Hover&Active.
		 *
		 * @return array<string, mixed>
		 */
		public static function border_state_defaults() {
			return array(
				'style'      => 'none',
				'color'      => '#cccccc',
				'hoverColor' => '#cccccc',
			);
		}

		/**
		 * Normal/Hover&Active color pair default.
		 *
		 * @param string $normal Normal color.
		 * @param string $hover Hover&Active color.
		 * @return array<string, string>
		 */
		public static function color_pair_default( $normal = '', $hover = '' ) {
			return array(
				'normal' => $normal,
				'hover'  => $hover,
			);
		}

		/**
		 * Typography object in the shape the Typography control expects.
		 * Category text defaults to 16px; dropdown title defaults to 18px.
		 *
		 * @param int|float|string $font_size Default font size (px) or '' when unset.
		 * @return array<string, mixed>
		 */
		public static function typography_default( $font_size = '' ) {
			return array(
				'family'        => array(
					'family'         => '',
					'fontWeight'     => '',
					'style'          => '',
					'textTransform'  => '',
					'textDecoration' => '',
				),
				'fontSize'      => self::single_responsive_default( $font_size ),
				'lineHeight'    => array( 'value' => '' ),
				'letterSpacing' => self::spacing_value_default( '' ),
				'wordSpacing'   => self::spacing_value_default( '' ),
			);
		}

		/**
		 * Single responsive value default (Desktop/Tablet/Mobile share one number).
		 *
		 * @param int|float|string $value Value applied to every device.
		 * @param string           $unit Shared unit.
		 * @return array<string, mixed>
		 */
		public static function single_responsive_default( $value, $unit = 'px' ) {
			return array(
				'device' => array(
					'Desktop' => $value,
					'Tablet'  => $value,
					'Mobile'  => $value,
				),
				'unit'   => array(
					'Desktop' => $unit,
					'Tablet'  => $unit,
					'Mobile'  => $unit,
				),
			);
		}

		/**
		 * Non-responsive spacing default (single value shared across all devices).
		 *
		 * @param int|float|string $value Value.
		 * @param string           $unit Unit.
		 * @return array<string, mixed>
		 */
		public static function spacing_value_default( $value = '', $unit = 'px' ) {
			return array(
				'value' => $value,
				'unit'  => $unit,
			);
		}

		/**
		 * Responsive four-side box default (mirrors AttributeFactory::responsive_spacing).
		 *
		 * @param int|string $desktop Desktop value applied to all four sides.
		 * @return array<string, mixed>
		 */
		public static function responsive_box_defaults( $desktop ) {
			return array(
				'device'    => array(
					'Desktop' => array(
						'top'    => $desktop,
						'right'  => $desktop,
						'bottom' => $desktop,
						'left'   => $desktop,
					),
					'Tablet'  => array(
						'top'    => '',
						'right'  => '',
						'bottom' => '',
						'left'   => '',
					),
					'Mobile'  => array(
						'top'    => '',
						'right'  => '',
						'bottom' => '',
						'left'   => '',
					),
				),
				'unit'      => array(
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
				),
				'allChange' => true,
			);
		}
	}
}
