<?php
/**
 * Display Elements section schema.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\DisplayElements;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DisplayElementsSchema' ) ) {
	/**
	 * Display Elements attribute branch builder.
	 */
	class DisplayElementsSchema {
		/** Section id. */
		public static function id() {
			return 'displayElements';
		}

		/** Raw default payload. */
		public static function defaults() {
			return DisplayElementsDefaults::get_display_elements_defaults();
		}

		/** Return the `displayElements` attribute definition. */
		public static function definition() {
			return array(
				'displayElements' => self::branch(),
			);
		}

		/**
		 * The Display Elements object attribute (defaults + property shape).
		 *
		 * Extracted so callers that need per-context copies of the same branch
		 * can reuse the exact shape instead of duplicating it.
		 *
		 * @return array<string, mixed>
		 */
		public static function branch() {
			return AttributeFactory::object(
				self::defaults(),
				array(
					'order'                 => AttributeFactory::array(
						DisplayElementsDefaults::get_default_order()
					),
					'enabled'               => AttributeFactory::object(
						DisplayElementsDefaults::get_enabled_defaults(),
						array(
							'title'       => AttributeFactory::boolean( false ),
							'tagline'     => AttributeFactory::boolean( false ),
							'description' => AttributeFactory::boolean( false ),
							'rating'      => AttributeFactory::boolean( false ),
							'readMore'    => AttributeFactory::boolean( false ),
						)
					),
					'titleSource'           => AttributeFactory::string(
						'title',
						DisplayElementsDefaults::get_title_source_options()
					),
					'descriptionSource'     => AttributeFactory::string(
						'description',
						DisplayElementsDefaults::get_description_source_options()
					),
					'descriptionWordLimit'  => AttributeFactory::string(
						'full',
						DisplayElementsDefaults::get_word_limit_options()
					),
					'descriptionWordCount'  => AttributeFactory::single_responsive( 10, 10, 10, 'words' ),
					'descriptionEllipsis'   => AttributeFactory::string( '...' ),
					'ratingIconSize'        => AttributeFactory::single_responsive( 16, 16, 16, 'px' ),
					'readMoreType'          => AttributeFactory::string(
						'button',
						DisplayElementsDefaults::get_read_more_type_options()
					),
					'readMoreLabel'         => AttributeFactory::string( 'Read More' ),
					'clickAction'           => AttributeFactory::string(
						'url',
						DisplayElementsDefaults::get_click_action_options()
					),
					'gapBetweenElements'    => AttributeFactory::single_responsive( 10, 10, 10, 'px' ),
					'alignment'             => AttributeFactory::string(
						'left',
						DisplayElementsDefaults::get_alignment_options()
					),
					'titleTypography'       => AttributeFactory::object( DisplayElementsDefaults::title_typography_default() ),
					'taglineTypography'     => AttributeFactory::object( DisplayElementsDefaults::tagline_typography_default() ),
					'descriptionTypography' => AttributeFactory::object( DisplayElementsDefaults::description_typography_default() ),
					'titleColor'            => AttributeFactory::color_pair( '#000000', '#000000' ),
					'taglineColor'          => AttributeFactory::color_pair( '#000000', '#000000' ),
					'descriptionColor'      => AttributeFactory::color_pair( '#757575', '#757575' ),
					// Filled star colour (the half star's filled side too). Defaults
					// to the gold `_displayElements.scss` has always drawn, so the
					// control opens on the colour that is already on screen.
					'ratingIconColor'       => AttributeFactory::color_pair( '#f0b849', '#f0b849' ),
					'cardBackground'        => AttributeFactory::object(
						DisplayElementsDefaults::get_background_defaults()
					),
					'border'                => AttributeFactory::object(
						DisplayElementsDefaults::get_card_border_defaults()
					),
					'borderWidth'           => AttributeFactory::responsive_spacing( 1, 1, 1, 1, 'px' ),
					'borderRadius'          => AttributeFactory::responsive_spacing( 4, 4, 4, 4, 'px' ),
					'innerPadding'          => AttributeFactory::responsive_spacing( 8, 8, 8, 8, 'px' ),
					'readMoreButtonTypography'   => AttributeFactory::object( DisplayElementsDefaults::read_more_typography_default() ),
					'readMoreButtonColor'        => AttributeFactory::color_pair( '#ffffff', '#ffffff' ),
					'readMoreButtonBackground'   => AttributeFactory::color_pair( '#2F2F2F', '#2F2F2F' ),
					'readMoreButtonBorder'       => AttributeFactory::object(
						DisplayElementsDefaults::get_border_defaults()
					),
					'readMoreButtonBorderWidth'  => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px' ),
					'readMoreButtonBorderRadius' => AttributeFactory::responsive_spacing( 4, 4, 4, 4, 'px' ),
					'readMoreButtonPadding'      => AttributeFactory::responsive_spacing( 3, 7, 3, 7, 'px' ),
					'readMoreButtonMargin'       => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px' ),
					'readMoreInlineTypography'   => AttributeFactory::object( DisplayElementsDefaults::read_more_typography_default() ),
					'readMoreInlineColor'        => AttributeFactory::color_pair( 'hsla(152, 78%, 71%, 1)', 'hsla(152, 78%, 71%, 1)' ),
					'readMoreInlinePadding'      => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px' ),
				)
			);
		}
	}
}
