<?php
/**
 * Tooltips section schema.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\Tooltips;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'TooltipsSchema' ) ) {
	/**
	 * Tooltips attribute branch builder.
	 */
	class TooltipsSchema {
		/** Section id. */
		public static function id() {
			return 'tooltips';
		}

		/** Raw default payload. */
		public static function defaults() {
			return TooltipsDefaults::get_tooltips_defaults();
		}

		/** Return the `tooltips` attribute definition. */
		public static function definition() {
			return array(
				'tooltips' => AttributeFactory::object(
					self::defaults(),
					array(
						'enabled'               => AttributeFactory::boolean( false ),
						'order'                 => AttributeFactory::array(
							TooltipsDefaults::get_default_order()
						),
						'elements'              => AttributeFactory::object(
							TooltipsDefaults::get_enabled_defaults(),
							array(
								'title'       => AttributeFactory::boolean( true ),
								'tagline'     => AttributeFactory::boolean( false ),
								'description' => AttributeFactory::boolean( false ),
								'customText'  => AttributeFactory::boolean( false ),
								'rating'      => AttributeFactory::boolean( false ),
							)
						),
						'visibility'            => AttributeFactory::string(
							'hover',
							TooltipsDefaults::get_visibility_options()
						),
						'position'              => AttributeFactory::string(
							'auto',
							TooltipsDefaults::get_position_options()
						),
						'tooltipEffect'         => AttributeFactory::string(
							'fade',
							TooltipsDefaults::get_effect_options()
						),
						'maxWidth'              => AttributeFactory::single_responsive( 280, 280, 280, 'px' ),
						'speechArrow'           => AttributeFactory::boolean( true ),
						'tooltipDistance'       => AttributeFactory::single_responsive( 10, 10, 10, 'px' ),
						'contentAlignment'      => AttributeFactory::string(
							'center',
							TooltipsDefaults::get_alignment_options()
						),
						'descriptionWordCount'  => AttributeFactory::single_responsive( 20, 20, 20, 'words' ),
						'descriptionEllipsis'   => AttributeFactory::string( '...' ),
						'ratingIconSize'        => AttributeFactory::single_responsive( 16, 16, 16, 'px' ),
						'background'            => AttributeFactory::object(
							TooltipsDefaults::get_background_defaults()
						),
						'titleTypography'       => AttributeFactory::object( TooltipsDefaults::title_typography_default() ),
						'taglineTypography'     => AttributeFactory::object( TooltipsDefaults::tagline_typography_default() ),
						'descriptionTypography' => AttributeFactory::object( TooltipsDefaults::description_typography_default() ),
						'customTextTypography'  => AttributeFactory::object( TooltipsDefaults::custom_text_typography_default() ),
						'titleColor'            => AttributeFactory::string( '#ffffff' ),
						'taglineColor'          => AttributeFactory::string( '#e5e7eb' ),
						'descriptionColor'      => AttributeFactory::string( '#d1d5db' ),
						'customTextColor'       => AttributeFactory::string( '#d1d5db' ),
						'border'                => AttributeFactory::object(
							TooltipsDefaults::get_border_defaults()
						),
						'borderWidth'           => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px' ),
						'borderRadius'          => AttributeFactory::responsive_spacing( 8, 8, 8, 8, 'px' ),
						'boxShadow'             => AttributeFactory::object( TooltipsDefaults::get_box_shadow_defaults() ),
					)
				),
			);
		}
	}
}
