<?php
/**
 * Grid Pagination section schema.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\GridPagination;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GridPaginationSchema' ) ) {
	/**
	 * Grid Pagination attribute branch builder.
	 *
	 * Grid-only section (page-based pagination for the static grid, distinct from
	 * the Swiper bullet `pagination` token). Composed only into `GridSchema` — not
	 * the shared `SectionRegistry` — because carousel/marquee have no page model.
	 * Profile-shaped like Ajax Filter: a `paginationType` selector plus one object
	 * per mode (`loadMore` / `number`), so each mode's settings/style stay isolated.
	 */
	class GridPaginationSchema {
		/** Section id. */
		public static function id() {
			return 'gridPagination';
		}

		/** Raw default payload. */
		public static function defaults() {
			return GridPaginationDefaults::get_grid_pagination_defaults();
		}

		/** Return the `gridPagination` attribute definition. */
		public static function definition() {
			return array(
				'gridPagination' => AttributeFactory::object(
					self::defaults(),
					array(
						'paginationType'   => AttributeFactory::string(
							'loadMore',
							GridPaginationDefaults::get_pagination_type_options()
						),
						'logoShowPerClick' => AttributeFactory::integer( 10 ),
						'alignment'        => AttributeFactory::string(
							'center',
							GridPaginationDefaults::get_alignment_options()
						),
						'loadMore'         => AttributeFactory::object( GridPaginationDefaults::load_more_defaults() ),
						'number'           => AttributeFactory::object( GridPaginationDefaults::number_defaults() ),
					)
				),
			);
		}
	}
}
