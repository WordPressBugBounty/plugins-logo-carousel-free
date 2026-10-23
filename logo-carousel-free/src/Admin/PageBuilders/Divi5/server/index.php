<?php
/**
 * Divi 5 Module - Server Side Rendering
 *
 * Loaded by Manager::load_divi5_server_module() only on Divi 5 with the
 * integration enabled.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders/Divi5
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Divi5;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Cannot access directly.
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;
use ET\Builder\Packages\Module\Module;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Renderer;

if ( ! interface_exists( DependencyInterface::class ) || ! class_exists( ModuleRegistration::class ) ) {
	return;
}

/**
 * Logo Carousel Divi 5 Module - Server Side.
 *
 * @since 4.0.0
 */
class D5_Logo_Carousel_Module implements DependencyInterface {

	/**
	 * Module instance for singleton pattern.
	 *
	 * @var D5_Logo_Carousel_Module|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 4.0.0
	 *
	 * @return D5_Logo_Carousel_Module
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register the module and its render callback.
	 *
	 * The module definition (module.json) lives beside this integration and is
	 * shared with the visual builder bundle, so there is a single copy of it.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function load() {
		ModuleRegistration::register_module(
			dirname( __DIR__ ) . '/',
			array(
				'render_callback' => array( __CLASS__, 'render_callback' ),
			)
		);
	}

	/**
	 * Render callback for the module.
	 *
	 * @since 4.0.0
	 *
	 * @param array     $attrs    Module attributes.
	 * @param string    $content  Module content.
	 * @param \WP_Block $block    Block object.
	 * @param object    $elements ModuleElements instance.
	 * @return string Rendered HTML.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {
		$template_id = isset( $attrs['templateId']['innerContent']['desktop']['value'] )
			? absint( $attrs['templateId']['innerContent']['desktop']['value'] )
			: 0;

		$html = Template_Renderer::render( $template_id, 'lcpro-divi5-template', self::is_divi5_builder_editor() );

		return Module::render(
			array(
				// Frontend only params.
				'orderIndex'         => $block->parsed_block['orderIndex'] ?? 0,
				'storeInstance'      => $block->parsed_block['storeInstance'] ?? '',

				// Visual builder params.
				'attrs'              => $attrs,
				'elements'           => $elements,
				'id'                 => $block->parsed_block['id'] ?? '',
				'moduleClassName'    => 'lcpro_divi5_logo_carousel',
				'name'               => $block->block_type->name ?? Divi5_Builder::MODULE_NAME,
				'classnamesFunction' => array( __CLASS__, 'module_classnames' ),
				'moduleCategory'     => $block->block_type->category ?? 'module',
				'children'           => sprintf(
					'<div class="et_pb_module_inner lcpro-divi5-template-wrapper" data-builder-template-id="%1$s">%2$s</div>',
					esc_attr( $template_id ),
					$html
				),
			)
		);
	}

	/**
	 * Module classnames callback.
	 *
	 * @since 4.0.0
	 *
	 * @param array $args Callback arguments.
	 * @return void
	 */
	public static function module_classnames( $args ) {
		$classnames_instance = $args['classnamesInstance'] ?? null;

		if ( $classnames_instance && method_exists( $classnames_instance, 'add' ) ) {
			$classnames_instance->add( 'lcpro_divi5_logo_carousel' );
		}
	}

	/**
	 * Check if the current request is for the Divi 5 builder.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	protected static function is_divi5_builder_editor() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only builder-context detection.
		if ( isset( $_GET['et_fb'] ) ) {
			return true;
		}

		if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only builder-context detection.
		return wp_doing_ajax() && isset( $_REQUEST['action'] ) && 0 === strpos( sanitize_key( wp_unslash( $_REQUEST['action'] ) ), 'et_' );
	}
}

D5_Logo_Carousel_Module::get_instance()->load();
