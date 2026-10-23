<?php
/**
 * Block types controller.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks;

use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\BlocksVisibility;
use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\VisibilityStoreInterface;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BlockTypesController' ) ) {
	/**
	 * Registers block types from the PHP block architecture layer.
	 */
	class BlockTypesController {
		/**
		 * Block visibility store (dashboard on/off toggles).
		 *
		 * @var VisibilityStoreInterface
		 */
		private $visibility;

		/**
		 * Constructor.
		 *
		 * @param VisibilityStoreInterface|null $visibility Injected for tests;
		 *                                                  defaults to the real store.
		 */
		public function __construct( ?VisibilityStoreInterface $visibility = null ) {
			$this->visibility = $visibility ? $visibility : new BlocksVisibility();
		}

		/**
		 * Wire hooks.
		 *
		 * @return void
		 */
		public function init() {
			add_action( 'init', array( $this, 'register_blocks' ), 20 );
		}

		/**
		 * Register all configured block types.
		 *
		 * @return void
		 */
		public function register_blocks() {
			if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
				return;
			}

			foreach ( $this->get_block_types() as $block_type ) {
				// Skip blocks the dashboard has switched off.
				if ( ! $this->visibility->is_enabled( $this->block_slug( $block_type ) ) ) {
					continue;
				}

				$class = __NAMESPACE__ . '\\BlockTypes\\' . $block_type . '\\' . $block_type;

				if ( class_exists( $class ) ) {
					new $class();
				}
			}
		}

		/**
		 * List of PHP-managed block type folders.
		 *
		 * @return string[]
		 */
		protected function get_block_types() {
			$block_types = array(
				'Carousel',
				'Grid',
			);

			/**
			 * Filters the list of Logo Carousel block type folders.
			 *
			 * @param string[] $block_types Block type folder names.
			 */
			return (array) apply_filters( 'sp_lcpro_block_types', $block_types );
		}

		/**
		 * Map a block-type folder/class name to its dashboard visibility slug.
		 *
		 * @param string $block_type Block type folder/class name.
		 * @return string Dashboard visibility slug.
		 */
		protected function block_slug( $block_type ) {
			return strtolower( $block_type );
		}
	}
}
