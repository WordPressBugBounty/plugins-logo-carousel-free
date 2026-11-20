<?php
/**
 * Masonry layout attribute group.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\Masonry;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\MasonryLayoutDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\MasonryLayoutAttributeGroup', false ) ) {
	/**
	 * Masonry layout branch (`layout.shared` only — one style).
	 *
	 * Every Layouts-panel field lives on `shared`, matching Grid,
	 * so the shared Pagination / Ajax readers (shared-first, flat fallback)
	 * work unchanged.
	 */
	class MasonryLayoutAttributeGroup {
		/**
		 * Return the Masonry layout attribute definition.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function definition() {
			return array(
				'layout' => AttributeFactory::object(
					MasonryLayoutDefaults::get_layout_defaults(),
					array(
						'shared' => AttributeFactory::object(
							MasonryLayoutDefaults::get_shared_layout_defaults(),
							self::shared_properties()
						),
					)
				),
			);
		}

		/**
		 * Shared layout properties.
		 *
		 * @return array<string, mixed>
		 */
		private static function shared_properties() {
			return array(
				'columns'                 => AttributeFactory::responsive_count( 6, 4, 2 ),
				'horizontalGap'           => AttributeFactory::single_responsive( 15, 15, 15, 'px' ),
				'verticalGap'             => AttributeFactory::single_responsive( 15, 15, 15, 'px' ),
				'pagination'              => AttributeFactory::boolean( false ),
				'ajaxLiveFilter'          => AttributeFactory::boolean( false ),
				'ajaxSearch'              => AttributeFactory::boolean( false ),
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
	}
}
