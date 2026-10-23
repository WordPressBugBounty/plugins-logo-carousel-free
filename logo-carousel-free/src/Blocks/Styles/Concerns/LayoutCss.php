<?php
/**
 * Layout CSS concern for the block-family server-side composer.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LayoutCss' ) ) {
	/**
	 * Shared wrapper/header/body layout rules.
	 */
	class LayoutCss {
		/**
		 * Base layout rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $options Layout options.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function base_rules( array $selectors, array $options = array() ) {
			$gap        = isset( $options['gap'] ) ? $options['gap'] : '20px';
			$radius     = isset( $options['borderRadius'] ) ? $options['borderRadius'] : '12px';
			$border     = isset( $options['border'] ) ? $options['border'] : '1px solid #cbd5e1';
			$shadow     = isset( $options['boxShadow'] ) ? $options['boxShadow'] : '0 18px 40px rgba(15, 23, 42, 0.06)';
			$header_gap = isset( $options['headerGap'] ) ? $options['headerGap'] : '8px';
			$body_gap   = isset( $options['bodyGap'] ) ? $options['bodyGap'] : '12px';

			return array(
				array(
					'selector' => $selectors['wrapper'],
					'styles'   => array(
						'display'        => 'flex',
						'flex-direction' => 'column',
						'gap'            => $gap,
						'border-radius'  => $radius,
						'border'         => $border,
						'box-shadow'     => $shadow,
						'color'          => '#0f172a',
					),
				),
				array(
					'selector' => $selectors['header'],
					'styles'   => array(
						'display'        => 'flex',
						'flex-direction' => 'column',
						'gap'            => $header_gap,
					),
				),
				array(
					'selector' => $selectors['body'],
					'styles'   => array(
						'display'        => 'flex',
						'flex-direction' => 'column',
						'gap'            => $body_gap,
					),
				),
			);
		}

		/**
		 * Mobile layout tweaks.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $options Layout options.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_rules( array $selectors, array $options = array() ) {
			$gap      = isset( $options['mobileGap'] ) ? $options['mobileGap'] : '16px';
			$body_gap = isset( $options['mobileBodyGap'] ) ? $options['mobileBodyGap'] : '10px';

			return array(
				array(
					'selector' => $selectors['wrapper'],
					'styles'   => array(
						'gap' => $gap,
					),
				),
				array(
					'selector' => $selectors['body'],
					'styles'   => array(
						'gap' => $body_gap,
					),
				),
			);
		}
	}
}
