<?php
/**
 * Logo Images Attribute Group Definition
 *
 * Defines grouped attributes for the Logo Images section.
 * Handles both logo source data and display settings.
 *
 * @package Logo_Carousel_Pro
 * @since 1.0.0
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\LogoImagesDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

/**
 * Class LogoImagesAttributeGroup
 *
 * Defines grouped attributes for logo images management and settings.
 * Follows grouped naming convention: logoSource.*, imagesSettings.*
 *
 * @since 1.0.0
 */
class LogoImagesAttributeGroup {

	/**
	 * Aspect ratio options for logo images.
	 *
	 * @var array
	 */
	private static $aspect_ratio_options = array(
		'original',
		'1:1',
		'4:3',
		'3:2',
		'3:4',
		'2:3',
		'16:9',
		'9:16',
		'custom',
	);

	/**
	 * Resolution options for WordPress image sizes.
	 *
	 * @var array
	 */
	private static $resolution_options = array(
		'full',
		'large',
		'medium',
		'thumbnail',
	);

	/**
	 * Click action options.
	 *
	 * @var array
	 */
	private static $click_action_options = array(
		'none',
		'link',
		'popup',
	);

	/**
	 * Hover animation options for logo images.
	 *
	 * @var array
	 */
	private static $hover_animation_options = array(
		'none',
		'scale-up',
		'fade-scale',
		'rotate',
		'slide-up',
		'flip-x',
		'border-ring',
		'flip-y',
		'skew',
		'bounce',
		'spin-360',
		'jelly',
		'shake',
		'float',
		'pulse',
		'swing',
		'wobble',
		'heartbeat',
	);

	/**
	 * Define images settings attributes.
	 *
	 * Handles display settings for logo images including
	 * aspect ratio, resolution, lazy loading, and click behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array Images settings attribute definition.
	 */
	public static function images_settings_definition() {
		return array(
			'imagesSettings' => AttributeFactory::object(
				LogoImagesDefaults::get_images_settings_defaults(),
				array(
					'aspectRatio'             => AttributeFactory::string( 'original', self::$aspect_ratio_options ),
					'customAspectRatioWidth'  => AttributeFactory::single_responsive( 100, 100, 100, '%' ),
					'customAspectRatioHeight' => AttributeFactory::single_responsive( '', '', '', 'auto' ),
					'resolution'              => AttributeFactory::string( 'large', self::$resolution_options ),
					'lazyLoad'                => AttributeFactory::boolean( true ),
					'randomOrder'             => AttributeFactory::boolean( false ),
					'clickAction'             => AttributeFactory::string( 'none', self::$click_action_options ),
					'allowSvgUploads'         => AttributeFactory::boolean( true ),
				)
			),
		);
	}

	/**
	 * Define logo image style attributes.
	 *
	 * @since 1.0.0
	 *
	 * @return array Logo image style attribute definition.
	 */
	public static function logo_images_style_definition() {
		return array(
			'logoImagesStyle' => AttributeFactory::object(
				LogoImagesDefaults::get_logo_images_style_defaults(),
				array(
					'hoverAnimation' => AttributeFactory::string(
						'none',
						self::$hover_animation_options
					),
					'margin'         => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px' ),
					'normal'         => self::logo_images_style_state_definition(),
					'hover'          => self::logo_images_style_state_definition(),
				)
			),
		);
	}

	/**
	 * Define a single logo image style state.
	 *
	 * @since 1.0.0
	 *
	 * @return array Logo image style state definition.
	 */
	private static function logo_images_style_state_definition() {
		return AttributeFactory::object(
			LogoImagesDefaults::get_logo_images_style_state_defaults(),
			array(
				'customColorEnabled' => AttributeFactory::boolean( false ),
				'customColor'        => AttributeFactory::string( '' ),
				'imageFilter'        => AttributeFactory::object(
					LogoImagesDefaults::get_logo_images_filter_defaults(),
					array(
						'blur'       => AttributeFactory::integer( 0 ),
						'brightness' => AttributeFactory::integer( 0 ),
						'contrast'   => AttributeFactory::integer( 0 ),
						'saturation' => AttributeFactory::integer( 0 ),
						'hue'        => AttributeFactory::integer( 0 ),
					)
				),
				'opacity'            => AttributeFactory::integer( 100 ),
				'overlay'            => AttributeFactory::object(
					LogoImagesDefaults::get_logo_images_overlay_defaults(),
					array(
						'type'     => AttributeFactory::string(
							'solid',
							array( 'solid', 'gradient' )
						),
						'color'    => AttributeFactory::string( '#000000' ),
						'gradient' => AttributeFactory::string( 'linear-gradient(90deg, rgba(0, 0, 0, 0.68) 0.09%, rgba(0, 0, 0, 0.07) 99.95%)' ),
						'opacity'  => AttributeFactory::number( 0 ),
					)
				),
			)
		);
	}

	/**
	 * Get complete logo images attribute definition.
	 *
	 * Combines both logo source and images settings into
	 * a single grouped attribute definition.
	 *
	 * @since 1.0.0
	 *
	 * @return array Complete logo images attribute definition.
	 */
	public static function definition() {
		return array_merge(
			CommonAttributeGroups::logo_showcase_source(),
			self::images_settings_definition(),
			self::logo_images_style_definition()
		);
	}
}
