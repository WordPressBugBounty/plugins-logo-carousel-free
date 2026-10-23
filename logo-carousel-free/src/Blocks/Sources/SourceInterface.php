<?php
/**
 * Contract for block-owned data sources.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sources;

defined( 'ABSPATH' ) || exit;

if ( ! interface_exists( 'SourceInterface' ) ) {
	/**
	 * Resolve normalized items for block rendering.
	 */
	interface SourceInterface {
		/**
		 * Return normalized items from block-owned inputs.
		 *
		 * @param array $attributes Block attributes.
		 * @return array<int, array<string, mixed>>
		 */
		public function get_items( array $attributes );
	}
}
