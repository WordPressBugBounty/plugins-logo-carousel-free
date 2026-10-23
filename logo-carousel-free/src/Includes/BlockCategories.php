<?php
/**
 * Block Categories
 *
 * Registers the Logo Carousel block categories: the free blocks and the
 * "Logo Carousel Pro Blocks" section that holds the Pro teaser blocks.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BlockCategories' ) ) {
	/**
	 * Block categories helper.
	 */
	class BlockCategories {
		/**
		 * Initialize category registration.
		 *
		 * @return void
		 */
		public static function init() {
			add_filter( 'block_categories_all', array( __CLASS__, 'register_categories' ), 10, 2 );
		}

		/**
		 * Register custom block categories.
		 *
		 * @param array                    $categories Block categories.
		 * @param \WP_Block_Editor_Context $context Block editor context.
		 * @return array
		 */
		public static function register_categories( $categories, $context ) {
			unset( $context );

			return array_merge(
				array(
					array(
						'slug'  => 'sp-logo-carousel',
						'title' => __( 'Logo Carousel', 'logo-carousel-free' ),
						'icon'  => null,
					),
					array(
						'slug'  => 'sp-logo-carousel-pro-blocks',
						'title' => __( 'Pro Blocks', 'logo-carousel-free' ),
						'icon'  => null,
					),
				),
				$categories
			);
		}
	}
}
