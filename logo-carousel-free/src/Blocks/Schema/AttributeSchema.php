<?php
/**
 * Block attribute schema contract.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema;

defined( 'ABSPATH' ) || exit;

if ( ! interface_exists( 'AttributeSchema' ) ) {
	/**
	 * Attribute schema interface.
	 */
	interface AttributeSchema {
		/**
		 * Return the block attribute map.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public function get_attributes(): array;
	}
}
