<?php
/**
 * Default values for the Masonry layout branch.
 *
 * Masonry has a single style, so its `layout` branch is `shared` only — no
 * style selector and no per-style objects. `shared` holds the column count,
 * the two gaps, and the Pagination / Ajax toggles that every static family
 * block keeps on `layout.shared`.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\MasonryLayoutDefaults', false ) ) {
	/**
	 * Masonry layout default provider.
	 */
	class MasonryLayoutDefaults {
		/**
		 * Columns per device when unset. Mirrors `DEFAULT_COLUMNS` in
		 * `masonryLayoutDynamicCss.js`.
		 *
		 * @var array<string, int>
		 */
		const DEFAULT_COLUMNS = array(
			'Desktop' => 6,
			'Tablet'  => 4,
			'Mobile'  => 2,
		);

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
		 * Shared layout defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_shared_layout_defaults() {
			return array(
				'columns'                 => array(
					'device' => self::DEFAULT_COLUMNS,
				),
				'horizontalGap'           => self::single_responsive_default( 15, 15, 15, 'px' ),
				'verticalGap'             => self::single_responsive_default( 15, 15, 15, 'px' ),
				'pagination'              => false,
				'ajaxLiveFilter'          => false,
				'ajaxSearch'              => false,
				'filterSearchOrientation' => 'inline',
				'filterSearchGap'         => self::single_responsive_default( 15, 15, 15, 'px' ),
				'justifyFilterSearch'     => 'flex-start',
			);
		}

		/**
		 * Full Masonry layout branch defaults.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_layout_defaults() {
			return array(
				'shared' => self::get_shared_layout_defaults(),
			);
		}
	}
}
