<?php
/**
 * Base block registration class.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\AttributeSchema;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AbstractBlock' ) ) {
	/**
	 * Base class for block registration from generated metadata.
	 */
	abstract class AbstractBlock {
		/**
		 * Block namespace.
		 *
		 * @var string
		 */
		protected $namespace = 'sp-logo-carousel';

		/**
		 * Block slug without namespace.
		 *
		 * @var string
		 */
		protected $block_name = '';

		/**
		 * Optional render callback override.
		 *
		 * @var callable|null
		 */
		protected $render_callback;

		/**
		 * Constructor.
		 *
		 * @param callable|null $render_callback Optional render callback.
		 */
		public function __construct( $render_callback = null ) {
			$this->render_callback = $render_callback;
			$this->initialize();
		}

		/**
		 * Initialize block registration.
		 *
		 * @return void
		 */
		protected function initialize() {
			if ( empty( $this->block_name ) || ! function_exists( 'register_block_type' ) ) {
				return;
			}

			$this->register_block_type();
		}

		/**
		 * Register the block from generated metadata.
		 *
		 * @return void
		 */
		protected function register_block_type() {
			$block_name = $this->get_block_name();
			$metadata   = $this->get_block_metadata();

			if ( empty( $metadata ) ) {
				return;
			}

			$args = array_merge(
				$metadata,
				array(
					'editor_script' => $this->get_editor_script_handle(),
					'editor_style'  => $this->get_editor_style_handle(),
					'view_script'   => $this->get_view_script_handle(),
				)
			);
			if ( is_callable( $this->render_callback ) ) {
				$args['render_callback'] = $this->render_callback;
			} elseif ( method_exists( $this, 'render' ) ) {
				$args['render_callback'] = array( $this, 'render' );
			}

			$schema = $this->get_schema();
			if ( $schema instanceof AttributeSchema ) {
				$args['attributes'] = $schema->get_attributes();
			}

			if ( isset( $args['usesContext'] ) ) {
				$args['uses_context'] = $args['usesContext'];
				unset( $args['usesContext'] );
			}

			if ( isset( $args['providesContext'] ) ) {
				$args['provides_context'] = $args['providesContext'];
				unset( $args['providesContext'] );
			}

			register_block_type( $block_name, $args );
		}

		/**
		 * Return the block attribute schema.
		 *
		 * @return AttributeSchema|null
		 */
		protected function get_schema() {
			return null;
		}

		/**
		 * Get the full block name.
		 *
		 * @return string
		 */
		protected function get_block_name() {
			return $this->namespace . '/' . $this->block_name;
		}

		/**
		 * Get generated metadata for the current block.
		 *
		 * @return array<string, mixed>
		 */
		protected function get_block_metadata() {
			static $blocks_metadata = null;

			if ( null === $blocks_metadata ) {
				$blocks_file     = dirname( SP_LOGO_CAROUSEL_FREE_FILE ) . '/src/Blocks/blocks.php';
				$blocks_metadata = file_exists( $blocks_file ) ? require $blocks_file : array();
			}

			return isset( $blocks_metadata[ $this->block_name ] ) ? $blocks_metadata[ $this->block_name ] : array();
		}

		/**
		 * Shared editor script handle.
		 *
		 * @return string
		 */
		protected function get_editor_script_handle() {
			return 'sp-logo-carousel-editor';
		}

		/**
		 * Shared editor style handle.
		 *
		 * @return string
		 */
		protected function get_editor_style_handle() {
			return 'sp-logo-carousel-editor';
		}

		/**
		 * Shared frontend script handle.
		 *
		 * @return string
		 */
		protected function get_view_script_handle() {
			return 'sp-logo-carousel-frontend';
		}
	}
}
