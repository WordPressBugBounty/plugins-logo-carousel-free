<?php
/**
 * Logo source CSS concern.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoSourceCss' ) ) {
	/**
	 * Styles for logo showcase source output.
	 */
	class LogoSourceCss {
		/**
		 * Base logo source rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function base_rules( array $selectors ) {
			return array(
				array(
					'selector' => $selectors['logoSourceEmpty'],
					'styles'   => array(
						'border'        => '1px dashed #cbd5e1',
						'border-radius' => '8px',
						'color'         => '#64748b',
						'padding'       => '28px',
						'text-align'    => 'center',
					),
				),
				array(
					'selector' => $selectors['logoSourceItems'],
					'styles'   => array(
						'list-style' => 'none',
						'margin'     => '0',
						'padding'    => '0',
					),
				),
				array(
					'selector' => $selectors['logoSourceFigure'],
					'styles'   => array(
						'align-items'     => 'center',
						'background'      => 'transparent',
						'border'          => '1px solid #dddddd',
						'border-radius'   => '5px',
						'display'         => 'flex',
						'flex-direction'  => 'column',
						'gap'             => '0',
						'justify-content' => 'center',
						'margin'          => '0',
						'padding'         => '8px 6px',
						'width'           => 'auto',
					),
				),
				array(
					'selector' => $selectors['logoSourceImage'],
					'styles'   => array(
						'display'         => 'block',
						'object-fit'      => 'contain',
						'object-position' => 'center',
						'width'           => 'auto',
					),
				),
				array(
					'selector' => $selectors['logoSourceMeta'],
					'styles'   => array(
						'display' => 'none',
					),
				),
			);
		}

		/**
		 * Tablet logo source rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function tablet_rules( array $selectors ) {
			return array();
		}

		/**
		 * Mobile logo source rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_rules( array $selectors ) {
			return array();
		}
	}
}
