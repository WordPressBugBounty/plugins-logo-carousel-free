<?php
/**
 * The plugin gutenberg block Initializer.
 *
 * @link       https://shapedplugin.com/
 * @since      3.4.6
 *
 * @package    Logo_Carousel_Free
 * @subpackage Logo_Carousel_Free/Admin
 * @author     ShapedPlugin <support@shapedplugin.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Logo_Carousel_Free_Gutenberg_Block_Init' ) ) {
	/**
	 * Logo_Carousel_Free_Gutenberg_Block_Init class.
	 */
	class Logo_Carousel_Free_Gutenberg_Block_Init {
		/**
		 * Custom Gutenberg Block Initializer.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'splcf_gutenberg_shortcode_block' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'splcf_block_editor_assets' ) );
			add_action( 'enqueue_block_assets', array( $this, 'splcf_block_canvas_assets' ) );
		}

		/**
		 * Register the block script for the outer block editor document.
		 */
		public function splcf_block_editor_assets() {
			$asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
			$asset      = file_exists( $asset_file ) ? require $asset_file : array(
				'dependencies' => array( 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-element', 'wp-escape-html', 'wp-i18n', 'wp-server-side-render' ),
				'version'      => SP_LC_VERSION,
			);

			wp_enqueue_script(
				'sp-logo-carousel-free-shortcode-block',
				plugins_url( '/GutenbergBlock/build/index.js', __DIR__ ),
				array_merge( $asset['dependencies'], array( 'jquery' ) ),
				$asset['version'],
				true
			);

			wp_localize_script(
				'sp-logo-carousel-free-shortcode-block',
				'sp_logo_carousel_free_g',
				array(
					'path'          => SP_LC_URL,
					'loadScript'    => SP_LC_URL . 'public/assets/js/splc-script.min.js',
					'url'           => admin_url( 'post-new.php?post_type=sp_lc_shortcodes' ),
					'shortCodeList' => $this->splcf_post_list(),
				)
			);
		}

		/**
		 * Enqueue the carousel runtime assets inside the block editor canvas.
		 *
		 * Since WordPress 7.1 the post editor canvas is always an iframe and that
		 * iframe document is built only from the assets collected on
		 * `enqueue_block_assets`. Styles and scripts hooked to
		 * `enqueue_block_editor_assets` only reach the outer admin document, so the
		 * server side rendered carousel preview stayed unstyled and uninitialized.
		 */
		public function splcf_block_canvas_assets() {
			if ( ! is_admin() ) {
				return;
			}

			wp_enqueue_style( 'sp-lc-swiper' );
			wp_enqueue_style( 'sp-lc-font-awesome' );
			wp_enqueue_style( 'sp-lc-style' );

			wp_enqueue_script( 'sp-lc-swiper-js' );
			wp_enqueue_script( 'sp-lc-script' );

			// The editor chrome styles are scoped to `.block-editor-page`, which only
			// exists on the outer document, so the canvas needs its own copy.
			wp_add_inline_style(
				'sp-lc-style',
				// The `::after` reset out-ranks core's `:root :where(.editor-styles-wrapper)::after`
				// 40vh spacer (0,2,1 vs 0,1,1), in case an older cached script still
				// puts that canvas class on the selector's wrapper.
				'.splcf-gutenberg-shortcode{padding:0;line-height:24px}'
				. '.splcf-gutenberg-shortcode::after,.splcf-gutenberg-shortcode.editor-styles-wrapper::after{content:none;display:none;height:auto}'
				. '.splcf-gutenberg-shortcode select.splcf-shortcode-selector{appearance:none;-webkit-appearance:none;-moz-appearance:none;box-sizing:border-box;width:100%;max-width:none;min-height:0;margin:0;padding:8px 36px 8px 12px;font-size:14px;line-height:1.4;color:#1e1e1e;background-color:#fff;background-image:url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23555\'%3E%3Cpath d=\'M7 10l5 5 5-5z\'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;background-size:18px;border:1px solid #949494;border-radius:4px;cursor:pointer;transition:border-color .15s ease,box-shadow .15s ease}'
				. '.splcf-gutenberg-shortcode select.splcf-shortcode-selector:hover{border-color:#1e1e1e}'
				. '.splcf-gutenberg-shortcode select.splcf-shortcode-selector:focus{outline:none;border-color:#007cba;box-shadow:0 0 0 1px #007cba}'
				. '.splcf-gutenberg-shortcode select.splcf-shortcode-selector:disabled{background-color:#f0f0f0;color:#a7a7a7;cursor:not-allowed}'
				. '.splcf_shortcode_block_preview_image img{display:block;max-width:100%;height:auto}'
				. '.splcf-block-preview .logo-carousel-free-area i{font-style:normal}'
			);
		}

		/**
		 * Shortcode list.
		 *
		 * @return array
		 */
		public function splcf_post_list() {
			$shortcodes = get_posts(
				array(
					'post_type'      => 'sp_lc_shortcodes',
					'post_status'    => 'publish',
					'posts_per_page' => 9999,
				)
			);

			if ( count( $shortcodes ) < 1 ) {
				return array();
			}

			return array_map(
				function ( $shortcode ) {
					return (object) array(
						'id'    => absint( $shortcode->ID ),
						'title' => esc_html( $shortcode->post_title ),
					);
				},
				$shortcodes
			);
		}

		/**
		 * Register Gutenberg shortcode block.
		 */
		public function splcf_gutenberg_shortcode_block() {
			/**
			 * Register Gutenberg block on server-side.
			 */
			register_block_type(
				'sp-logo-carousel-pro/shortcode',
				array(
					'api_version'     => 3,
					'attributes'      => array(
						'shortcode'          => array(
							'type'    => 'string',
							'default' => '',
						),
						'showInputShortcode' => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'is_admin'           => array(
							'type'    => 'boolean',
							'default' => is_admin(),
						),
						'preview'            => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
					'example'         => array(
						'attributes' => array(
							'preview' => true,
						),
					),
					'render_callback' => array( $this, 'sp_logo_carousel_render_shortcode' ),
				)
			);
		}

		/**
		 * Render callback.
		 *
		 * @param string $attributes Shortcode.
		 * @return string
		 */
		public function sp_logo_carousel_render_shortcode( $attributes ) {
			if ( empty( $attributes['shortcode'] ) || ! get_post_status( $attributes['shortcode'] ) ) {
				return;
			}
			$class_name = '';
			if ( ! empty( $attributes['className'] ) ) {
				$class_name = 'class="' . esc_attr( $attributes['className'] ) . '"';
			}

			if ( empty( $attributes['is_admin'] ) ) {
				return '<div ' . $class_name . ' >' . do_shortcode( '[logocarousel id="' . intval( $attributes['shortcode'] ) . '"]' ) . '</div>';
			}
			$edit_page_link = get_edit_post_link( intval( $attributes['shortcode'] ) );
			return '<div id="' . uniqid() . '" ' . $class_name . '><a href="' . $edit_page_link . '" target="_blank" class="sp_logo_block_edit_button">Edit View</a>' . do_shortcode( '[logocarousel id="' . intval( $attributes['shortcode'] ) . '"]' ) . '</div>';
		}
	}
}
