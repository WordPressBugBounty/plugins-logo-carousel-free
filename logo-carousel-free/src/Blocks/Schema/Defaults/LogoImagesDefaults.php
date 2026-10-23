<?php
/**
 * Logo Images Attribute default Definition
 *
 * Defines default attributes for the Logo Images section.
 * Handles both logo source data and display settings.
 *
 * @package Logo_Carousel_Pro
 * @since 1.0.0
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoImagesDefaults' ) ) {
	/**
	 * Class LogoImagesDefaults
	 *
	 * Defines default values for logo images management and settings.
	 *
	 * @since 1.0.0
	 */
	class LogoImagesDefaults {
		/**
		 * Minimum accepted numeric value for Custom width/height sizes.
		 *
		 * @since 1.0.0
		 */
		const CUSTOM_ASPECT_RATIO_MIN = 0;

		/**
		 * Maximum accepted numeric value for Custom width/height sizes.
		 *
		 * @since 1.0.0
		 */
		const CUSTOM_ASPECT_RATIO_MAX = 2000;

		/**
		 * Allowed units for Custom width.
		 *
		 * @return array<int, string>
		 */
		public static function get_custom_width_units() {
			return array( 'px', '%', 'em' );
		}

		/**
		 * Allowed units for Custom height (`auto` is a keyword unit).
		 *
		 * @return array<int, string>
		 */
		public static function get_custom_height_units() {
			return array( 'px', '%', 'em', 'auto' );
		}

		/**
		 * Single responsive size default (Desktop/Tablet/Mobile share one value + unit).
		 *
		 * @param int|float|string $value Value applied to every device.
		 * @param string           $unit Shared unit (`px`, `%`, `em`, or `auto`).
		 * @return array<string, mixed>
		 */
		public static function single_responsive_default( $value, $unit = 'px' ) {
			return array(
				'device' => array(
					'Desktop' => $value,
					'Tablet'  => $value,
					'Mobile'  => $value,
				),
				'unit'   => array(
					'Desktop' => $unit,
					'Tablet'  => $unit,
					'Mobile'  => $unit,
				),
			);
		}

		/**
		 * Get default logo images settings values.
		 *
		 * @since 1.0.0
		 *
		 * @return array Default logo images settings values.
		 */
		public static function get_images_settings_defaults() {
			return array(
				'aspectRatio'             => 'original',
				'customAspectRatioWidth'  => self::single_responsive_default( 100, '%' ),
				'customAspectRatioHeight' => self::single_responsive_default( '', 'px' ),
				'resolution'              => 'large',
				'lazyLoad'                => true,
				'randomOrder'             => false,
				'clickAction'             => 'none',
				'allowSvgUploads'         => true,
			);
		}

		/**
		 * Get default logo image style values.
		 *
		 * @since 1.0.0
		 *
		 * @return array Default logo image style values.
		 */
		public static function get_logo_images_style_defaults() {
			return array(
				'hoverAnimation' => 'none',
				'margin'         => self::get_logo_images_margin_defaults(),
				'normal'         => self::get_logo_images_style_state_defaults(),
				'hover'          => self::get_logo_images_style_state_defaults(),
			);
		}

		/**
		 * Get default logo image style state values.
		 *
		 * @since 1.0.0
		 *
		 * @return array Default logo image style state values.
		 */
		public static function get_logo_images_style_state_defaults() {
			return array(
				'customColorEnabled' => false,
				'customColor'        => '',
				'imageFilter'        => self::get_logo_images_filter_defaults(),
				'opacity'            => 100,
				'overlay'            => self::get_logo_images_overlay_defaults(),
			);
		}

		/**
		 * Get default filter values.
		 *
		 * @since 1.0.0
		 *
		 * @return array Default filter values.
		 */
		public static function get_logo_images_filter_defaults() {
			return array(
				'blur'       => 0,
				'brightness' => 0,
				'contrast'   => 0,
				'saturation' => 0,
				'hue'        => 0,
			);
		}

		/**
		 * Get default overlay values.
		 *
		 * @since 1.0.0
		 *
		 * @return array Default overlay values.
		 */
		public static function get_logo_images_overlay_defaults() {
			return array(
				'type'     => 'solid',
				'color'    => '#000000',
				'gradient' => 'linear-gradient(90deg, rgba(0, 0, 0, 0.68) 0.09%, rgba(0, 0, 0, 0.07) 99.95%)',
				'opacity'  => 0,
			);
		}

		/**
		 * Get default margin values.
		 *
		 * @since 1.0.0
		 *
		 * @return array Default margin values.
		 */
		public static function get_logo_images_margin_defaults() {
			return array(
				'device'    => array(
					'Desktop' => array(
						'top'    => 0,
						'right'  => 0,
						'bottom' => 0,
						'left'   => 0,
					),
					'Tablet'  => array(
						'top'    => 0,
						'right'  => 0,
						'bottom' => 0,
						'left'   => 0,
					),
					'Mobile'  => array(
						'top'    => 0,
						'right'  => 0,
						'bottom' => 0,
						'left'   => 0,
					),
				),
				'unit'      => array(
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
				),
				'allChange' => true,
			);
		}
	}
}
