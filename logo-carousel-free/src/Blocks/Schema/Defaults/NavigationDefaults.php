<?php
/**
 * Navigation arrows attribute defaults.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'NavigationDefaults' ) ) {
	/**
	 * Navigation arrows defaults for the logo showcase family.
	 *
	 * Settings-tab fields only. Style-tab fields (colors/border/background/shadow)
	 * are added later as sibling keys without breaking this shape.
	 */
	class NavigationDefaults {
		/**
		 * Get default navigation values.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_navigation_defaults() {
			return array(
				'presetIcon'         => 'arrow-01',
				'iconSize'           => self::get_icon_size_defaults(),
				'navStyle'           => 'style-1',
				// Arrows beside the track, never over the logos.
				'position'           => 'center-outer',
				'horizontalDistance' => self::get_horizontal_distance_defaults(),
				'gapBetweenArrows'   => self::get_gap_defaults(),
				// Arrows always visible; hover-only reveal is opt-in.
				'showOnHover'        => false,
				'hideOnMobile'       => true,
				// Style-tab fields (Normal/Hover where stateful).
				'background'         => self::get_background_defaults(),
				'iconColor'          => self::get_icon_color_defaults(),
				'border'             => self::get_border_defaults(),
				'borderWidth'        => self::get_border_width_defaults(),
				'borderRadius'       => self::get_border_radius_defaults(),
				'innerPadding'       => self::get_inner_padding_defaults(),
				'margin'             => self::get_margin_defaults(),
			);
		}

		/**
		 * Get default arrow background (per state).
		 *
		 * No background in either state: a bare arrow by default. Hover feedback
		 * comes from the icon color instead (see get_icon_color_defaults()).
		 * Users add a background from the Style tab.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_background_defaults() {
			return array(
				'normal' => array(
					'style'    => 'solid',
					'solid'    => '',
					'gradient' => '',
				),
				'hover'  => array(
					'style'    => 'solid',
					'solid'    => '',
					'gradient' => '',
				),
			);
		}

		/**
		 * Get default arrow icon color (per state).
		 *
		 * Hover turns the icon the brand green: with no default background, a
		 * white hover icon would vanish against the page.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_icon_color_defaults() {
			return array(
				'normal' => '#1e1e1e',
				'hover'  => '#2ab674',
			);
		}

		/**
		 * Get default arrow border (style + per-state color).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_border_defaults() {
			return array(
				'style'      => 'none',
				'color'      => '#cccccc',
				'hoverColor' => '#cccccc',
			);
		}

		/**
		 * Get default arrow border width (responsive box).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_border_width_defaults() {
			return self::responsive_box_defaults( 0 );
		}

		/**
		 * Get default arrow border radius (responsive box).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_border_radius_defaults() {
			return self::responsive_box_defaults( 4 );
		}

		/**
		 * Get default arrow inner padding (responsive box).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_inner_padding_defaults() {
			return self::responsive_box_defaults( 0 );
		}

		/**
		 * Get default arrow margin (responsive box).
		 *
		 * Default margin is 0 on every side.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_margin_defaults() {
			return self::responsive_box_defaults( 0 );
		}

		/**
		 * Build a responsive four-side box default (mirrors AttributeFactory::responsive_spacing).
		 *
		 * @param int|string $desktop Desktop value applied to all four sides.
		 * @return array<string, mixed>
		 */
		private static function responsive_box_defaults( $desktop ) {
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
		 * Get default icon-size values.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_icon_size_defaults() {
			return array(
				'device' => array(
					'Desktop' => 24,
					'Tablet'  => 22,
					'Mobile'  => 20,
				),
				'unit'   => array(
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
				),
			);
		}

		/**
		 * Get default horizontal-distance values (offset from the carousel edge).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_horizontal_distance_defaults() {
			return array(
				'device' => array(
					'Desktop' => 6,
					'Tablet'  => 6,
					'Mobile'  => 6,
				),
				'unit'   => array(
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
				),
			);
		}

		/**
		 * Get default gap-between-arrows values.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_gap_defaults() {
			return array(
				'device' => array(
					'Desktop' => 10,
					'Tablet'  => 10,
					'Mobile'  => 10,
				),
				'unit'   => array(
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
				),
			);
		}

		/**
		 * Allowed preset arrow icon keys (12 root Icons/Arrrows SVGs).
		 *
		 * @return array<int, string>
		 */
		public static function get_preset_icon_options() {
			return array(
				'arrow-01',
				'arrow-02',
				'arrow-03',
				'arrow-04',
				'arrow-05',
				'arrow-06',
				'arrow-07',
				'arrow-08',
				'arrow-09',
				'arrow-10',
				'arrow-11',
				'arrow-12',
			);
		}

		/**
		 * Allowed navigation position keys (union of both styles).
		 *
		 * Style 1 (split arrows) — placement relative to the carousel edge:
		 *  - center        : arrows centered on the edge (half in / half out)
		 *  - center-outer  : arrows sit outside the slide track
		 *  - center-inner  : arrows sit inside the slide track
		 *
		 * Style 2 (grouped pair) — which corner / edge the pair docks to:
		 *  - top-center / top-right / top-left
		 *  - bottom-left / bottom-center / bottom-right
		 *
		 * The two sets are disjoint; NavigationConfig picks the valid subset per
		 * style and coerces a stale value to that style's default.
		 *
		 * @return array<int, string>
		 */
		public static function get_position_options() {
			return array_merge(
				self::get_style_1_position_options(),
				self::get_style_2_position_options()
			);
		}

		/**
		 * Allowed position keys for Style 1 (split arrows).
		 *
		 * @return array<int, string>
		 */
		public static function get_style_1_position_options() {
			return array(
				'center',
				'center-outer',
				'center-inner',
			);
		}

		/**
		 * Allowed position keys for Style 2 (grouped pair).
		 *
		 * @return array<int, string>
		 */
		public static function get_style_2_position_options() {
			return array(
				'top-center',
				'top-right',
				'top-left',
				'bottom-left',
				'bottom-center',
				'bottom-right',
			);
		}
	}
}
