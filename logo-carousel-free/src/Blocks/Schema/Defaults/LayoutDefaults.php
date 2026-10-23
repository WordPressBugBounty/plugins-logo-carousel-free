<?php
/**
 * Layout attribute defaults.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LayoutDefaults' ) ) {
	/**
	 * Layout defaults for the carousel family.
	 */
	class LayoutDefaults {
		/**
		 * Get default layout values.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_layout_defaults() {
			$defaults = array_merge(
				array(
					'carouselStyle' => 'standard',
					'shared'                  => self::get_shared_layout_defaults(),
					'standard'                => self::get_standard_layout_defaults(),
					'center'                  => self::get_center_layout_defaults(),
					'staggered'               => self::get_staggered_layout_defaults(),
					'multirow'                => self::get_multirow_layout_defaults(),
				),
				self::get_shared_layout_defaults(),
				self::get_standard_layout_defaults(),
				self::get_center_layout_defaults(),
				self::get_staggered_layout_defaults()
			);

			// The flat keys mirror the ACTIVE profile, but the spreads above run in
			// profile order, so the last profile merged wins any field the profiles
			// disagree on. Standard and Center therefore share their defaults
			// (Center only adds its own emphasis fields), so the flat mirror always
			// describes the default `standard` style.
			return $defaults;
		}

		/**
		 * Get shared layout defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_shared_layout_defaults() {
			return array(
				'displayOrientation'      => 'horizontal',
				'partialSlideView'        => false,
				'variableWidth'           => false,
				'gapBetweenLogos'         => self::get_gap_defaults(),
				'alignment'               => self::get_alignment_defaults(),
				'edgeFade'                => self::get_edge_fade_defaults(),
				'autoplay'                => true,
				'autoplayDelay'           => 3000,
				'sliderSpeed'             => 600,
				'pauseOnHover'            => true,
				'adaptiveHeight'          => false,
				'infiniteLoop'            => true,
				'navigationArrows'        => true,
				'paginationDots'          => true,
				'ajaxLiveFilter'          => false,
				'ajaxSearch'              => false,
				'filterSearchOrientation' => 'inline',
				'filterSearchGap'         => self::get_gap_defaults(),
				'justifyFilterSearch'     => 'flex-start',
			);
		}

		/**
		 * Get standard-style layout defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_standard_layout_defaults() {
			return array(
				'columns'                => self::responsive_count_default( 5, 2, 1 ),
				'rows'                   => self::responsive_count_default( 5, 4, 3 ),
				'carouselDirection'      => 'left-to-right',
				'slideToScroll'          => self::get_slide_to_scroll_defaults(),
				'slidingEffects'         => 'none',
				'carouselStartsOnScreen' => false,
				'tabsAndKeyNavigation'   => true,
				'mouseWheelControl'      => false,
				'freeScrollMode'         => false,
			);
		}

		/**
		 * Get centered-style layout defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_center_layout_defaults() {
			return array(
				'columns'                => self::responsive_count_default( 5, 2, 1 ),
				'rows'                   => self::responsive_count_default( 5, 4, 3 ),
				'carouselDirection'      => 'left-to-right',
				'slideToScroll'          => self::get_slide_to_scroll_defaults(),
				'slidingEffects'         => 'none',
				'carouselStartsOnScreen' => false,
				'tabsAndKeyNavigation'   => true,
				'mouseWheelControl'      => false,
				'freeScrollMode'         => false,
				'centeredLogoScale'      => 1.2,
				'slideLogosOpacity'      => 0.5,
			);
		}

		/**
		 * Get staggered-style layout defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_staggered_layout_defaults() {
			return array(
				'columns'            => self::responsive_count_default( 5, 2, 1 ),
				'rows'               => self::responsive_count_default( 5, 4, 3 ),
				'staggeredAnimation'  => 'slide-to-bottom',
				'randomLogoAnimation' => false,
				'easing'              => 'default',
			);
		}

		/**
		 * Get multi-row layout defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_multirow_layout_defaults() {
			return array(
				'horizontal' => array(
					'rows'    => self::responsive_count_default( 2, 2, 2 ),
					'columns' => self::responsive_count_default( 5, 3, 2 ),
				),
				'vertical'   => array(
					'rows'    => self::responsive_count_default( 5, 3, 2 ),
					'columns' => self::responsive_count_default( 2, 2, 2 ),
				),
			);
		}

		/**
		 * Responsive count default shape (unitless — columns, rows).
		 *
		 * Tablet/Mobile are seeded with the counts SwiperConfig has always derived
		 * for small screens, so turning the field responsive changes nothing about
		 * how a block renders — it only makes those numbers visible and editable.
		 *
		 * @param int      $desktop Desktop value.
		 * @param int|null $tablet Tablet value (defaults to Desktop).
		 * @param int|null $mobile Mobile value (defaults to Tablet).
		 * @return array<string, mixed>
		 */
		public static function responsive_count_default( $desktop, $tablet = null, $mobile = null ) {
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
		 * Get default gap values.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_gap_defaults() {
			return array(
				'device' => array(
					'Desktop' => 15,
					'Tablet'  => 15,
					'Mobile'  => 15,
				),
				'unit'   => array(
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
				),
			);
		}

		/**
		 * Get default alignment values.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_alignment_defaults() {
			return array(
				'horizontal' => 'center',
				'vertical'   => 'center',
			);
		}

		/**
		 * Get default edge fade values.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_edge_fade_defaults() {
			return array(
				'enabled'   => false,
				'strength'  => 0,
				'width'     => 0,
				'colorMode' => 'auto',
				'color'     => '',
			);
		}

		/**
		 * Get default slide-to-scroll values.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_slide_to_scroll_defaults() {
			return array(
				'device' => array(
					'Desktop' => 1,
					'Tablet'  => 1,
					'Mobile'  => 1,
				),
			);
		}
	}
}
