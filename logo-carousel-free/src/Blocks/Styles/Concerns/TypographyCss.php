<?php
/**
 * Typography CSS concern for the block-family server-side composer.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns;

use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssUtils;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'TypographyCss' ) ) {
	/**
	 * Shared heading/caption typography rules.
	 */
	class TypographyCss {
		/**
		 * Base typography rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $options Typography options.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function base_rules( array $selectors, array $options = array() ) {
			$heading_size  = isset( $options['headingSize'] ) ? $options['headingSize'] : '22px';
			$caption_size  = isset( $options['captionSize'] ) ? $options['captionSize'] : '14px';
			$caption_color = CssUtils::sanitize_css_color( isset( $options['captionColor'] ) ? $options['captionColor'] : '#64748b', '#64748b' );

			return array(
				array(
					'selector' => $selectors['heading'],
					'styles'   => array(
						'margin'      => '0',
						'font-size'   => $heading_size,
						'font-weight' => '700',
						'line-height' => '1.2',
						'color'       => CssUtils::sanitize_css_color( '#0f172a', '#0f172a' ),
					),
				),
				array(
					'selector' => $selectors['caption'],
					'styles'   => array(
						'margin'      => '0',
						'font-size'   => $caption_size,
						'font-weight' => '400',
						'line-height' => '1.6',
						'color'       => $caption_color,
					),
				),
			);
		}

		/**
		 * Mobile typography tweaks.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $options Typography options.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_rules( array $selectors, array $options = array() ) {
			$heading_size = isset( $options['headingSizeMobile'] ) ? $options['headingSizeMobile'] : '20px';
			$caption_size = isset( $options['captionSizeMobile'] ) ? $options['captionSizeMobile'] : '13px';

			return array(
				array(
					'selector' => $selectors['heading'],
					'styles'   => array(
						'font-size' => $heading_size,
					),
				),
				array(
					'selector' => $selectors['caption'],
					'styles'   => array(
						'font-size' => $caption_size,
					),
				),
			);
		}
	}
}
