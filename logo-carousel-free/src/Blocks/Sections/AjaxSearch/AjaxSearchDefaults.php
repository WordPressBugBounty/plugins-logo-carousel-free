<?php
/**
 * Ajax Search section defaults.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\AjaxSearch;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AjaxSearchDefaults' ) ) {
	/**
	 * Defaults + allow-lists for the Ajax Search section.
	 */
	class AjaxSearchDefaults {
		/**
		 * Build the full default payload (matches the schema branch shape).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_ajax_search_defaults() {
			return array(
				'searchBarStyle'        => 'style1',
				'alignment'             => 'left',
				'noResultsText'         => 'No logos found.',
				// Typography / Text Colour / Search Icon Colour describe the text
				// and icon inside the field, not the shape of one bar style, so
				// they live here once instead of three times. They default to
				// EMPTY on purpose: WordPress merges schema defaults into every
				// saved block, so an empty value is the only reliable way to tell
				// "the author has not set this" from "the author set the default".
				// While empty, the emitters fall back to the per-style value —
				// which is what keeps content saved before this change looking
				// exactly the same, and keeps Style 3's white-on-button icon.
				'placeholderTypography' => array(),
				'placeholderColor'      => self::color_pair_default( '', '' ),
				'searchIconColor'       => self::color_pair_default( '', '' ),
				'style1'                => self::style1_defaults(),
				'style2'                => self::style2_defaults(),
				'style3'                => self::style3_defaults(),
			);
		}

		/**
		 * Style 1 (icon + underline) profile defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function style1_defaults() {
			return array(
				'placeholder'           => true,
				'placeholderText'       => 'Search',
				// Tablet and Mobile default to the full row — that is what small
				// screens have always rendered (a static `width: 100% !important`
				// used to force it), and keeping it as a DEFAULT rather than a
				// locked rule is what lets the per-device control work at all.
				'width'                 => self::responsive_width_default( 35, 100, 100 ),
				'height'                => self::single_responsive_default( '', 'px' ),
				'placeholderTypography' => self::typography_default(),
				'placeholderColor'      => self::color_pair_default( '#949494', '#949494' ),
				'searchIconColor'       => self::color_pair_default( '#757575', '#2ab674' ),
				'underlineColor'        => self::color_pair_default( '#dddddd', '#2ab674' ),
				'underlineThickness'    => self::single_responsive_default( 1, 'px' ),
				'innerPadding'          => self::responsive_box_sides_defaults( 6, 12, 6, 12 ),
			);
		}

		/**
		 * Style 2 (boxed) profile defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function style2_defaults() {
			return array(
				'placeholder'           => true,
				'placeholderText'       => 'Search',
				// Tablet and Mobile default to the full row — that is what small
				// screens have always rendered (a static `width: 100% !important`
				// used to force it), and keeping it as a DEFAULT rather than a
				// locked rule is what lets the per-device control work at all.
				'width'                 => self::responsive_width_default( 35, 100, 100 ),
				'height'                => self::single_responsive_default( '', 'px' ),
				'placeholderTypography' => self::typography_default(),
				'placeholderColor'      => self::color_pair_default( '#949494', '#949494' ),
				'searchIconColor'       => self::color_pair_default( '#757575', '#2ab674' ),
				'background'            => self::per_state_background_defaults( '#ffffff', '#ffffff' ),
				'border'                => self::solid_border_state_defaults(),
				'borderWidth'           => self::responsive_box_defaults( 1 ),
				'borderRadius'          => self::responsive_box_defaults( 4 ),
				'innerPadding'          => self::responsive_box_sides_defaults( 6, 12, 6, 12 ),
			);
		}

		/**
		 * Style 3 (boxed + button) profile defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function style3_defaults() {
			return array(
				'placeholder'           => true,
				'placeholderText'       => 'Search',
				'searchButtonTitle'     => true,
				'buttonText'            => 'Search',
				// Tablet and Mobile default to the full row — that is what small
				// screens have always rendered (a static `width: 100% !important`
				// used to force it), and keeping it as a DEFAULT rather than a
				// locked rule is what lets the per-device control work at all.
				'width'                 => self::responsive_width_default( 35, 100, 100 ),
				'height'                => self::single_responsive_default( '', 'px' ),
				'buttonTitleTypography' => self::typography_default(),
				'placeholderTypography' => self::typography_default(),
				'placeholderColor'      => self::color_pair_default( '#949494', '#949494' ),
				'buttonTitleColor'      => self::color_pair_default( '#ffffff', '#ffffff' ),
				'searchIconColor'       => self::color_pair_default( '#ffffff', '#ffffff' ),
				'buttonBackgroundColor' => self::color_pair_default( '#1e1e1e', '#1e1e1e' ),
				'background'            => self::per_state_background_defaults( '#ffffff', '#ffffff' ),
				'border'                => self::solid_border_state_defaults(),
				'borderWidth'           => self::responsive_box_defaults( 1 ),
				'borderRadius'          => self::responsive_box_defaults( 4 ),
				'innerPadding'          => self::responsive_box_sides_defaults( 6, 0, 6, 12 ),
			);
		}

		/**
		 * Allowed search-bar-style keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_search_bar_style_options() {
			return array( 'style1', 'style2', 'style3' );
		}

		/**
		 * Allowed search bar alignment keys (Left / Center / Right).
		 *
		 * Applied when Filter & Search Orientation is not `inline` (stacked, or
		 * search alone). Inline pairing uses `justifyFilterSearch` instead.
		 *
		 * @return array<int, string>
		 */
		public static function get_alignment_options() {
			return array( 'left', 'center', 'right' );
		}

		/**
		 * Allowed background-type keys (field background style).
		 *
		 * @return array<int, string>
		 */
		public static function get_background_type_options() {
			return array( 'solid', 'gradient' );
		}

		/**
		 * Single (non-state) background object: type + solid + gradient.
		 *
		 * @param string $solid Fallback solid color.
		 * @return array<string, string>
		 */
		public static function single_background_defaults( $solid = '' ) {
			return array(
				'style'    => 'solid',
				'solid'    => $solid,
				'gradient' => '',
			);
		}

		/**
		 * Per-state (Normal/Hover&Active) background — used by the boxed field.
		 *
		 * @param string $normal_solid Normal solid color.
		 * @param string $hover_solid Hover&Active solid color.
		 * @return array<string, mixed>
		 */
		public static function per_state_background_defaults( $normal_solid = '', $hover_solid = '' ) {
			return array(
				'normal' => self::single_background_defaults( $normal_solid ),
				'hover'  => self::single_background_defaults( $hover_solid ),
			);
		}

		/**
		 * Border (style + per-state color), mirrors the Ajax Filter border shape.
		 * `hoverColor` covers Hover&Active.
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
		 * Solid gray border default used by the boxed search field styles.
		 *
		 * @return array<string, mixed>
		 */
		public static function solid_border_state_defaults() {
			return array(
				'style'      => 'solid',
				'color'      => '#dddddd',
				'hoverColor' => '#dddddd',
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
		 * Typography object in the shape the Typography control expects. Values
		 * are empty so nothing is forced until the user edits.
		 *
		 * @return array<string, mixed>
		 */
		public static function typography_default() {
			return array(
				'family'        => array(
					'family'         => '',
					'fontWeight'     => '',
					'style'          => '',
					'textTransform'  => '',
					'textDecoration' => '',
				),
				'fontSize'      => self::single_responsive_default( '' ),
				'lineHeight'    => array( 'value' => '' ),
				'letterSpacing' => self::spacing_value_default( '' ),
				'wordSpacing'   => self::spacing_value_default( '' ),
			);
		}

		/**
		 * Single responsive value default (Desktop/Tablet/Mobile share one value).
		 *
		 * @param int|float|string $value Value applied to every device.
		 * @param string           $unit Shared unit.
		 * @return array<string, mixed>
		 */
		/**
		 * Per-device width default (percent), so each breakpoint can differ.
		 *
		 * @param int|float $desktop Desktop value.
		 * @param int|float $tablet Tablet value.
		 * @param int|float $mobile Mobile value.
		 * @return array<string, mixed>
		 */
		public static function responsive_width_default( $desktop, $tablet, $mobile ) {
			return array(
				'device' => array(
					'Desktop' => $desktop,
					'Tablet'  => $tablet,
					'Mobile'  => $mobile,
				),
				'unit'   => array(
					'Desktop' => '%',
					'Tablet'  => '%',
					'Mobile'  => '%',
				),
			);
		}

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

		/**
		 * Responsive four-side box default with explicit desktop sides.
		 *
		 * @param int|string $top Desktop top value.
		 * @param int|string $right Desktop right value.
		 * @param int|string $bottom Desktop bottom value.
		 * @param int|string $left Desktop left value.
		 * @return array<string, mixed>
		 */
		public static function responsive_box_sides_defaults( $top, $right, $bottom, $left ) {
			return array(
				'device'    => array(
					'Desktop' => array(
						'top'    => $top,
						'right'  => $right,
						'bottom' => $bottom,
						'left'   => $left,
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
				'allChange' => false,
			);
		}
	}
}
