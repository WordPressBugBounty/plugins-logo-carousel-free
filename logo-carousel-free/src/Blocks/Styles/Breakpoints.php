<?php
/**
 * Shared breakpoint constants for block-family CSS.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Breakpoints' ) ) {
	/**
	 * CSS media-query breakpoints mirrored by the editor preview.
	 */
	final class Breakpoints {
		public const DESKTOP          = '(min-width: 901px)';
		public const TABLET = '(max-width: 900px)';
		public const MOBILE = '(max-width: 600px)';
		public const TABLET_ONLY      = '(min-width: 601px) and (max-width: 900px)';
		public const MOBILE_PORTRAIT  = '(max-width: 600px) and (orientation: portrait)';
		public const MOBILE_LANDSCAPE = '(max-width: 900px) and (orientation: landscape)';
	}
}
