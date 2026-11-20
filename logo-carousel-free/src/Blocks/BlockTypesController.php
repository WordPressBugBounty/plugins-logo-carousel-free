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
		 * Namespace of the plugin's blocks.
		 *
		 * @var string
		 */
		const BLOCK_NAMESPACE = 'sp-logo-carousel/';

		/**
		 * Block metadata key marking a Ready Pattern's own top-level wrapper
		 * (set on insert by `ready-patterns/turnedOffBlocks.js`).
		 *
		 * @var string
		 */
		const PATTERN_WRAPPER_FLAG = 'spLcproPattern';

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
			add_filter( 'pre_render_block', array( $this, 'skip_unavailable_blocks' ), 10, 2 );
		}

		/**
		 * Render nothing for a plugin block that is not registered (switched off
		 * on the Blocks page, or Pro-only), nor for the Ready Pattern wrapper
		 * around one.
		 *
		 * The blocks are dynamic, so on their own they would print only their
		 * (empty) saved markup; the pattern's group, heading and copy would
		 * still show as an empty section. Both come back once the block is on.
		 *
		 * @param string|null $pre_render   Short-circuited output, or null.
		 * @param array       $parsed_block Parsed block.
		 * @return string|null
		 */
		public function skip_unavailable_blocks( $pre_render, $parsed_block ) {
			if ( null !== $pre_render || ! is_array( $parsed_block ) ) {
				return $pre_render;
			}

			if ( self::is_unavailable_block_name( isset( $parsed_block['blockName'] ) ? $parsed_block['blockName'] : '' ) ) {
				return '';
			}

			if (
				! empty( $parsed_block['attrs']['metadata'][ self::PATTERN_WRAPPER_FLAG ] )
				&& self::has_unavailable_block( isset( $parsed_block['innerBlocks'] ) ? $parsed_block['innerBlocks'] : array() )
			) {
				return '';
			}

			return $pre_render;
		}

		/**
		 * Whether a block name is a plugin block that is not registered.
		 *
		 * @param string|null $block_name Block name.
		 * @return bool
		 */
		public static function is_unavailable_block_name( $block_name ) {
			$block_name = (string) $block_name;

			return 0 === strpos( $block_name, self::BLOCK_NAMESPACE )
				&& class_exists( '\WP_Block_Type_Registry' )
				&& ! \WP_Block_Type_Registry::get_instance()->is_registered( $block_name );
		}

		/**
		 * Whether any parsed block, at any depth, is an unavailable plugin block.
		 *
		 * @param array $blocks Parsed blocks.
		 * @return bool
		 */
		public static function has_unavailable_block( $blocks ) {
			foreach ( (array) $blocks as $block ) {
				if ( ! is_array( $block ) ) {
					continue;
				}
				if ( self::is_unavailable_block_name( isset( $block['blockName'] ) ? $block['blockName'] : '' ) ) {
					return true;
				}
				if ( ! empty( $block['innerBlocks'] ) && self::has_unavailable_block( $block['innerBlocks'] ) ) {
					return true;
				}
			}

			return false;
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
				'Masonry',
				'Carousel3d',
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
