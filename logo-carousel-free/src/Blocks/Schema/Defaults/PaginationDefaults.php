<?php
/**
 * Pagination dots attribute defaults.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PaginationDefaults' ) ) {
	/**
	 * Pagination dots defaults for the logo showcase family.
	 */
	class PaginationDefaults {
		/**
		 * Get default pagination values.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_pagination_defaults() {
			return array(
				'paginationStyle' => 'style-1',
				'width'           => self::single_responsive_default( 10 ),
				'height'          => self::single_responsive_default( 10 ),
				'gapBetweenDots'  => self::single_responsive_default( 8 ),
				'hideOnMobile'    => true,
				'backgroundColor' => self::state_color_default( '#9fb3bf', '#557182' ),
				'textColor'       => self::state_color_default( '#ffffff', '#ffffff' ),
				'border'          => self::border_default(),
				'borderWidth'     => self::responsive_spacing_default( 0, 0, 0, 0 ),
				'borderRadius'    => self::responsive_spacing_default( 3, 3, 3, 3 ),
				'margin'          => self::responsive_spacing_default( 24, 0, 16, 0, false ),
				'typography'      => self::typography_default(),
			);
		}

		/**
		 * Allowed pagination style keys (6 visual presets).
		 *
		 *  - style-1 : row of dots (default)
		 *  - style-2 : dynamic bullets (Swiper dynamicBullets)
		 *  - style-3 : active dot inside a ring
		 *  - style-4 : short dash / bar
		 *  - style-5 : Swiper scrollbar (track + draggable thumb)
		 *  - style-6 : numbers (1 2 3 4)
		 *
		 * @return array<int, string>
		 */
		public static function get_style_options() {
			return array(
				'style-1',
				'style-2',
				'style-3',
				'style-4',
				'style-5',
				'style-6',
			);
		}

		/**
		 * Width/Height presets applied when the editor switches pagination style.
		 * Keep in sync with `blocks/.../pagination/paginationStyleSizes.js`.
		 *
		 * @param string $style Style key.
		 * @return array{width:int|float, height:int|float}
		 */
		public static function get_size_defaults_for_style( $style ) {
			$sizes = array(
				'style-1' => array( 'width' => 10, 'height' => 10 ),
				'style-2' => array( 'width' => 10, 'height' => 10 ),
				'style-3' => array( 'width' => 10, 'height' => 10 ),
				'style-4' => array( 'width' => 20, 'height' => 5 ),
				'style-5' => array( 'width' => 20, 'height' => 5 ),
				'style-6' => array( 'width' => 20, 'height' => 20 ),
			);

			return isset( $sizes[ $style ] ) ? $sizes[ $style ] : $sizes['style-1'];
		}

		/**
		 * Build a single-responsive default (mirrors AttributeFactory::single_responsive).
		 *
		 * @param int|float $value Desktop/Tablet/Mobile value.
		 * @return array<string, mixed>
		 */
		private static function single_responsive_default( $value ) {
			return array(
				'device' => array(
					'Desktop' => $value,
					'Tablet'  => $value,
					'Mobile'  => $value,
				),
				'unit'   => array(
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
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
		private static function spacing_value_default( $value = 0, $unit = 'px' ) {
			return array(
				'value' => $value,
				'unit'  => $unit,
			);
		}

		/**
		 * Build Normal/Active color defaults.
		 *
		 * @param string $normal Normal color.
		 * @param string $active Active color.
		 * @return array<string, string>
		 */
		private static function state_color_default( $normal, $active ) {
			return array(
				'normal' => $normal,
				'active' => $active,
			);
		}

		/**
		 * Build border defaults.
		 *
		 * @return array<string, string>
		 */
		private static function border_default() {
			return array(
				'style'       => 'none',
				'color'       => '#9fb3bf',
				'activeColor' => '#557182',
			);
		}

		/**
		 * Build a responsive spacing default.
		 *
		 * @param int|float $top Top value.
		 * @param int|float $right Right value.
		 * @param int|float $bottom Bottom value.
		 * @param int|float $left Left value.
		 * @param bool      $all_change Whether the Spacing control opens linked. Pass
		 *                              false when the sides differ, otherwise the
		 *                              control shows one combined input and the first
		 *                              edit flattens every side to the same value.
		 * @return array<string, mixed>
		 */
		private static function responsive_spacing_default( $top, $right, $bottom, $left, $all_change = true ) {
			$box = array(
				'top'    => $top,
				'right'  => $right,
				'bottom' => $bottom,
				'left'   => $left,
			);

			return array(
				'device'    => array(
					'Desktop' => $box,
					'Tablet'  => $box,
					'Mobile'  => $box,
				),
				'unit'      => array(
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
				),
				'allChange' => $all_change,
			);
		}

		/**
		 * Build typography defaults for style-6 numbers.
		 *
		 * @return array<string, mixed>
		 */
		private static function typography_default() {
			return array(
				'family'        => array(
					'family'         => '',
					'fontWeight'     => '600',
					'style'          => '',
					'textTransform'  => '',
					'textDecoration' => '',
				),
				'fontSize'      => self::single_responsive_default( 11 ),
				'lineHeight'    => array( 'value' => 1 ),
				'letterSpacing' => self::spacing_value_default( 0 ),
				'wordSpacing'   => self::spacing_value_default( 0 ),
			);
		}
	}
}
