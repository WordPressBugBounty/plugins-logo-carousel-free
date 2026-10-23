<?php
/**
 * Beaver Builder - Logo Carousel Module
 *
 * Loaded by Manager only when Beaver Builder is active and the integration is enabled.
 * Beaver resolves modules by global class name, so this file is not namespaced.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Assets;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Icon;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Actions;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_List;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Cannot access directly.
}

if ( ! class_exists( 'LCPRO_Beaver_Logo_Carousel_Module' ) ) {

	/**
	 * Logo Carousel Beaver Builder Module.
	 *
	 * Registers a module in Beaver Builder with a select field to choose and render
	 * a Logo Carousel saved template.
	 */
	class LCPRO_Beaver_Logo_Carousel_Module extends \FLBuilderModule {

		/**
		 * Constructor - defines module properties.
		 */
		public function __construct() {
			parent::__construct(
				array(
					'name'            => __( 'Logo Carousel', 'logo-carousel-free' ),
					'description'     => __( 'Display a Logo Carousel saved template.', 'logo-carousel-free' ),
					'category'        => __( 'SP Plugins', 'logo-carousel-free' ),
					// frontend.php must be inside this directory's includes/.
					'dir'             => plugin_dir_path( __FILE__ ),
					'url'             => plugin_dir_url( __FILE__ ),
					'icon'            => $this->get_module_icon(),
					'editor_export'   => true,
					'enabled'         => true,
					'partial_refresh' => true,
				)
			);

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Get module icon SVG.
		 *
		 * Beaver accepts raw SVG markup for the module icon.
		 *
		 * @return string SVG icon content.
		 */
		private function get_module_icon() {
			$icon_svg = Builder_Icon::svg();

			return ! empty( $icon_svg ) ? $icon_svg : 'ti-layout-slider';
		}

		/**
		 * Enqueue block assets for the Beaver Builder editor.
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			if ( ! \FLBuilderModel::is_builder_active() ) {
				return;
			}

			// Re-initialise the block frontend after Beaver re-renders the layout.
			Builder_Assets::reinit_script(
				'lcpro-beaver-template-wrapper',
				array( 'fl-builder-preview-render', 'fl-builder-layout-rendered' )
			);

			// Beaver blocks link clicks in its canvas; keep the template actions working.
			Template_Actions::enqueue_canvas_link_handler( Builder_Assets::RUNTIME_HANDLE );
		}

		/**
		 * Register the module and its settings fields with Beaver Builder.
		 *
		 * @return void
		 */
		public static function register() {
			\FLBuilder::register_module(
				__CLASS__,
				array(
					'general' => array(
						'title'    => __( 'General', 'logo-carousel-free' ),
						'sections' => array(
							'content' => array(
								'title'  => __( 'Logo Carousel', 'logo-carousel-free' ),
								'fields' => array(
									'template_id' => array(
										'type'    => 'select',
										'label'   => __( 'Saved Templates', 'logo-carousel-free' ),
										'default' => '',
										// Beaver stores an empty string when nothing is selected.
										'options' => Template_List::options( '' ),
										'help'    => __( 'Please Select a Saved Template', 'logo-carousel-free' ),
									),
								),
							),
						),
					),
				)
			);
		}
	}
}

add_action( 'init', array( 'LCPRO_Beaver_Logo_Carousel_Module', 'register' ) );
