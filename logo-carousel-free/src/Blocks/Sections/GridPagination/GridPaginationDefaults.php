<?php
/**
 * Grid Pagination section defaults.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\GridPagination;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GridPaginationDefaults' ) ) {
	/**
	 * Defaults + allow-lists for the Grid Pagination section.
	 *
	 * Two profiles — `loadMore` (button + optional Infinity Scroll) and `number`
	 * (page numbers + optional arrows) — plus shared fields (`logoShowPerClick`,
	 * `alignment`) that both profiles read. `loadMore` uses Normal/Hover state
	 * pairs (matches Ajax Filter's `border_state_defaults`/`color_pair_default`
	 * shape); `number` uses Normal/Active state pairs (matches the existing Swiper
	 * Pagination style-6 "number" shape in `PaginationDefaults::state_color_default()`
	 * / `border_default()`), since page numbers have no meaningful hover-only state
	 * distinct from the active page on touch devices.
	 */
	class GridPaginationDefaults {
		/**
		 * Build the full default payload (matches the schema branch shape).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_grid_pagination_defaults() {
			return array(
				'paginationType'   => 'loadMore',
				'logoShowPerClick' => 10,
				'alignment'        => 'center',
				'loadMore'         => self::load_more_defaults(),
				'number'           => self::number_defaults(),
			);
		}

		/**
		 * Load More profile defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function load_more_defaults() {
			return array(
				'infinityScroll' => false,
				'buttonLabel'    => 'Load More',
				'endingMessage'  => 'No more logos available',
				'typography'     => self::typography_default(),
				// Figma default: white label on the dark `#3A3A3A` fill.
				'textColor'      => self::color_pair_default( '#ffffff', '#ffffff' ),
				'background'     => self::hover_background_defaults( '#3a3a3a', '#2ab674' ),
				'border'         => self::border_state_defaults(),
				'borderWidth'    => self::responsive_box_defaults( 0 ),
				'borderRadius'   => self::responsive_box_defaults( 4 ),
				// Figma: 12px vertical / 20px horizontal (not uniform).
				'innerPadding'   => self::responsive_box_defaults_vh( 12, 20 ),
				// Top 16px separates the button from the logos by default.
				'margin'         => self::pagination_margin_defaults(),
			);
		}

		/**
		 * Number profile defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function number_defaults() {
			return array(
				'numberType'        => 'number',
				'gapBetweenNumbers' => self::single_responsive_default( 8 ),
				'typography'        => self::typography_default(),
				// White numerals read on both the dark Normal fill and the Blueberry
				// Active fill.
				'textColor'         => self::state_color_default( '#ffffff', '#ffffff' ),
				'backgroundColor'   => self::state_color_default( '#3a3a3a', '#3858e9' ),
				'border'            => self::number_border_defaults(),
				'borderWidth'       => self::responsive_box_defaults( 0 ),
				'borderRadius'      => self::responsive_box_defaults( 4 ),
				// Defaults to 12 (not 0): a 0 default left nothing asserting the
				// property at all in some environments, letting a theme's own
				// button padding reset apply instead.
				'innerPadding'      => self::responsive_box_defaults( 12 ),
				// Top 16px separates the pagination bar from the logos by default.
				'margin'            => self::pagination_margin_defaults(),
			);
		}

		/**
		 * Default gap between the logo grid and pagination (Load More + Number).
		 *
		 * Asymmetric (top only) — `allChange` stays false so the Spacing control
		 * does not treat the four sides as a linked uniform box.
		 *
		 * @return array<string, mixed>
		 */
		public static function pagination_margin_defaults() {
			$margin                             = self::responsive_box_defaults( 0 );
			$margin['device']['Desktop']['top'] = 16;
			$margin['allChange']                = false;

			return $margin;
		}

		/**
		 * Allowed `paginationType` keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_pagination_type_options() {
			return array( 'loadMore', 'number' );
		}

		/**
		 * Allowed `numberType` keys — Number, Number + Arrow, Number + Prev/Next + Arrow.
		 *
		 * @return array<int, string>
		 */
		public static function get_number_type_options() {
			return array( 'number', 'number-arrow', 'number-prevnext-arrow' );
		}

		/**
		 * Allowed Alignment keys (shared by both profiles).
		 *
		 * @return array<int, string>
		 */
		public static function get_alignment_options() {
			return array( 'left', 'center', 'right' );
		}

		/**
		 * Allowed background-type keys (Load More style/solid/gradient toggle).
		 *
		 * @return array<int, string>
		 */
		public static function get_background_type_options() {
			return array( 'solid', 'gradient' );
		}

		/**
		 * Normal/Hover color pair default (Load More).
		 *
		 * @param string $normal Normal color.
		 * @param string $hover Hover color.
		 * @return array<string, string>
		 */
		public static function color_pair_default( $normal = '', $hover = '' ) {
			return array(
				'normal' => $normal,
				'hover'  => $hover,
			);
		}

		/**
		 * Normal/Active color pair default (Number).
		 *
		 * @param string $normal Normal color.
		 * @param string $active Active color.
		 * @return array<string, string>
		 */
		public static function state_color_default( $normal = '', $active = '' ) {
			return array(
				'normal' => $normal,
				'active' => $active,
			);
		}

		/**
		 * Per-state (Normal/Hover) background — style + solid + gradient per state
		 * (Load More button paint).
		 *
		 * @param string $normal_solid Normal solid color.
		 * @param string $hover_solid Hover solid color.
		 * @return array<string, mixed>
		 */
		public static function hover_background_defaults( $normal_solid = '', $hover_solid = '' ) {
			return array(
				'normal' => array(
					'style'    => 'solid',
					'solid'    => $normal_solid,
					'gradient' => '',
				),
				'hover'  => array(
					'style'    => 'solid',
					'solid'    => $hover_solid,
					'gradient' => '',
				),
			);
		}

		/**
		 * Border (style + per-state color) — Normal/Hover, mirrors Ajax Filter's
		 * `border_state_defaults` (Load More).
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
		 * Border (style + per-state color) — Normal/Active, mirrors the existing
		 * Swiper Pagination style-6 `border_default()` shape (Number).
		 *
		 * @return array<string, mixed>
		 */
		public static function number_border_defaults() {
			return array(
				'style'       => 'none',
				'color'       => '#cccccc',
				'activeColor' => '#315cf6',
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
		 * Responsive four-side box default with distinct vertical/horizontal
		 * values (Load More button padding — Figma: 12px vertical, 20px horizontal).
		 *
		 * @param int|string $vertical Top/bottom value.
		 * @param int|string $horizontal Left/right value.
		 * @return array<string, mixed>
		 */
		public static function responsive_box_defaults_vh( $vertical, $horizontal ) {
			return array(
				'device'    => array(
					'Desktop' => array(
						'top'    => $vertical,
						'right'  => $horizontal,
						'bottom' => $vertical,
						'left'   => $horizontal,
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
