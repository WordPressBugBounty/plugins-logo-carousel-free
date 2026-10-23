<?php
/**
 * Ajax Live Filter section schema.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\AjaxFilter;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AjaxFilterSchema' ) ) {
	/**
	 * Ajax Live Filter attribute branch builder.
	 */
	class AjaxFilterSchema {
		/** Section id. */
		public static function id() {
			return 'ajaxFilter';
		}

		/** Raw default payload. */
		public static function defaults() {
			return AjaxFilterDefaults::get_ajax_filter_defaults();
		}

		/** Return the `ajaxFilter` attribute definition. */
		public static function definition() {
			return array(
				'ajaxFilter' => AttributeFactory::object(
					self::defaults(),
					array(
						'filterStyle' => AttributeFactory::string(
							'minimal',
							AjaxFilterDefaults::get_filter_style_options()
						),
						'minimal'     => AttributeFactory::object( AjaxFilterDefaults::minimal_defaults() ),
						'button'      => AttributeFactory::object( AjaxFilterDefaults::button_defaults() ),
						'dropdown'    => AttributeFactory::object( AjaxFilterDefaults::dropdown_defaults() ),
					)
				),
			);
		}
	}
}
