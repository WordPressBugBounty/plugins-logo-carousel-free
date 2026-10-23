<?php
/**
 * Ajax Search section schema.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\AjaxSearch;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AjaxSearchSchema' ) ) {
	/**
	 * Ajax Search attribute branch builder.
	 */
	class AjaxSearchSchema {
		/** Section id. */
		public static function id() {
			return 'ajaxSearch';
		}

		/** Raw default payload. */
		public static function defaults() {
			return AjaxSearchDefaults::get_ajax_search_defaults();
		}

		/** Return the `ajaxSearch` attribute definition. */
		public static function definition() {
			return array(
				'ajaxSearch' => AttributeFactory::object(
					self::defaults(),
					array(
						'searchBarStyle'        => AttributeFactory::string(
							'style1',
							AjaxSearchDefaults::get_search_bar_style_options()
						),
						'alignment'             => AttributeFactory::string(
							'left',
							AjaxSearchDefaults::get_alignment_options()
						),
						'noResultsText'         => AttributeFactory::string( 'No logos found.' ),
						// Shared across every search bar style — see
						// AjaxSearchDefaults::get_ajax_search_defaults() for why
						// these default to empty.
						'placeholderTypography' => AttributeFactory::typography(),
						'placeholderColor'      => AttributeFactory::color_pair( '', '' ),
						'searchIconColor'       => AttributeFactory::color_pair( '', '' ),
						'style1'                => AttributeFactory::object( AjaxSearchDefaults::style1_defaults() ),
						'style2'                => AttributeFactory::object( AjaxSearchDefaults::style2_defaults() ),
						'style3'                => AttributeFactory::object( AjaxSearchDefaults::style3_defaults() ),
					)
				),
			);
		}
	}
}
