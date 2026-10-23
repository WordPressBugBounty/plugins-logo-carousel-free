<?php
/**
 * Grid layout attribute group.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\Grid;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\GridLayoutDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GridLayoutAttributeGroup' ) ) {
	/**
	 * Profile-shaped Grid layout branch (standard / strip / staggered / zigzag).
	 *
	 * Static CSS grid — no Swiper, no runtime. `gridStyle` selects the style;
	 * `shared` holds columns / gaps / justify / alignment; each style object holds
	 * only its own fields (Strip owns the bordered-cell styling, Staggered / Zigzag
	 * own the transform-offset amount).
	 */
	class GridLayoutAttributeGroup {
		/**
		 * Return the Grid layout attribute definition.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function definition() {
			return array(
				'layout' => AttributeFactory::object(
					GridLayoutDefaults::get_layout_defaults(),
					array(
						'gridStyle' => AttributeFactory::string(
							'standard',
							GridLayoutDefaults::get_grid_style_options()
						),
						'shared'    => AttributeFactory::object(
							GridLayoutDefaults::get_shared_layout_defaults(),
							self::shared_properties()
						),
						'standard'  => AttributeFactory::object(
							GridLayoutDefaults::get_standard_layout_defaults(),
							array()
						),
						'strip'     => AttributeFactory::object(
							GridLayoutDefaults::get_strip_layout_defaults(),
							self::strip_properties()
						),
						'staggered' => AttributeFactory::object(
							GridLayoutDefaults::get_staggered_layout_defaults(),
							array(
								'preserveLayoutGaps' => AttributeFactory::integer( 20 ),
							)
						),
						'zigzag'    => AttributeFactory::object(
							GridLayoutDefaults::get_zigzag_layout_defaults(),
							array(
								'preserveLayoutGaps' => AttributeFactory::integer( 10 ),
							)
						),
					)
				),
			);
		}

		/**
		 * Shared (cross-style) layout properties.
		 *
		 * @return array<string, mixed>
		 */
		private static function shared_properties() {
			return array(
				// Responsive: Tablet/Mobile seeded with the Desktop count, which is
				// what every breakpoint rendered before the field could vary.
				'columns'       => AttributeFactory::responsive_count( 6, 4, 3 ),
				'horizontalGap' => AttributeFactory::single_responsive( 15, 15, 15, 'px' ),
				'verticalGap'   => AttributeFactory::single_responsive( 15, 15, 15, 'px' ),
				'justify'       => AttributeFactory::string(
					'center',
					GridLayoutDefaults::get_justify_options()
				),
				'alignment'      => AttributeFactory::string(
					'start',
					GridLayoutDefaults::get_alignment_options()
				),
				'pagination'     => AttributeFactory::boolean( false ),
				'ajaxLiveFilter' => AttributeFactory::boolean( false ),
				'ajaxSearch'     => AttributeFactory::boolean( false ),
				// Combined Filter + Search layout (shown only when BOTH Ajax controls
				// are on). Stored on `shared`, Grid's declared home for every
				// Layouts-panel toggle — unlike Carousel, which stores these flat.
				// `LayoutCss.php` / `layoutDynamicCss.js` already read shared-first
				// then flat, so no CSS-side change is needed.
				'filterSearchOrientation' => AttributeFactory::string(
					'inline',
					array( 'inline', 'stacked' )
				),
				'filterSearchGap'         => AttributeFactory::single_responsive( 15, 15, 15, 'px' ),
				'justifyFilterSearch'     => AttributeFactory::string(
					'flex-start',
					array(
						'flex-start',
						'center',
						'flex-end',
						'space-between',
						'space-around',
						'space-evenly',
					)
				),
			);
		}

		/**
		 * Grid Strip (bordered-cell) properties.
		 *
		 * Normal / Hover states mirror the shared Border control shape; Inner
		 * Padding is a single shared field applied in both states.
		 *
		 * @return array<string, mixed>
		 */
		private static function strip_properties() {
			return array(
				'normal'       => AttributeFactory::object(
					GridLayoutDefaults::get_strip_state_defaults(),
					self::strip_state_properties()
				),
				'hover'        => AttributeFactory::object(
					GridLayoutDefaults::get_strip_state_defaults(),
					self::strip_state_properties()
				),
				'innerPadding' => AttributeFactory::single_responsive( 12, 12, 12, 'px' ),
			);
		}

		/**
		 * Per-state Strip properties (Border object + border-width spacing).
		 *
		 * @return array<string, mixed>
		 */
		private static function strip_state_properties() {
			return array(
				'border'      => AttributeFactory::object(
					array(
						'style' => 'solid',
						'color' => '#dddddd',
					),
					array(
						'style' => AttributeFactory::string(
							'solid',
							GridLayoutDefaults::get_line_style_options()
						),
						'color' => AttributeFactory::string( '#dddddd' ),
					)
				),
				'borderWidth' => AttributeFactory::responsive_spacing( 1, 1, 1, 1, 'px' ),
			);
		}
	}
}
