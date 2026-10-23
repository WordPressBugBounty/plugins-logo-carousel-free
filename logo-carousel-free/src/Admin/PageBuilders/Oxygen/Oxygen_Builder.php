<?php
/**
 * Oxygen Builder - Logo Carousel Element
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Oxygen;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Assets;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Icon;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_List;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Logo Carousel Oxygen Builder Element.
 */
class LCPRO_Oxygen_Logo_Carousel_Element extends \OxyEl {

	/**
	 * Element name.
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Logo Carousel', 'logo-carousel-free' );
	}

	/**
	 * Element slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'sp-logo-carousel-pro';
	}

	/**
	 * Element icon.
	 *
	 * @return string
	 */
	public function icon() {
		return Builder_Icon::wide_url();
	}

	/**
	 * Button priority.
	 *
	 * @return int
	 */
	public function button_priority() {
		return 9;
	}

	/**
	 * Element tag.
	 *
	 * @return string
	 */
	public function tag() {
		return 'div';
	}

	/**
	 * Enable full CSS.
	 *
	 * @return bool
	 */
	public function enableFullCSS() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Oxygen API.
		return true;
	}

	/**
	 * After init hook.
	 *
	 * @return void
	 */
	public function afterInit() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Oxygen API.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_page_builder_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_page_builder_scripts' ) );
	}

	/**
	 * Whether this is an Oxygen AJAX element render.
	 *
	 * @return bool
	 */
	private function is_ajax_render() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only builder-context detection, no state change.
		return isset( $_GET['action'] ) && false !== strpos( sanitize_key( wp_unslash( $_GET['action'] ) ), 'oxy_render_oxy' );
	}

	/**
	 * Check if currently in the Oxygen builder editor.
	 *
	 * @return bool
	 */
	private function is_oxygen_builder_editor() {
		if ( function_exists( 'ct_get_current_screen' ) && 'oxy_settings_iframe' === ct_get_current_screen() ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only builder-context detection, no state change.
		if ( isset( $_GET['oxygen_iframe'] ) ) {
			return true;
		}

		return $this->is_ajax_render();
	}

	/**
	 * Enqueue block assets for the Oxygen builder editor.
	 *
	 * @return void
	 */
	public function enqueue_page_builder_scripts() {
		if ( ! $this->is_oxygen_builder_editor() ) {
			return;
		}

		// Re-initialise the block frontend after Oxygen swaps element markup in.
		Builder_Assets::reinit_script(
			'lcpro-oxygen-template-wrapper',
			array( 'oxygen-ajax-element-loaded' )
		);
	}

	/**
	 * Render element output.
	 *
	 * @param array  $options  Element options.
	 * @param array  $defaults Default values.
	 * @param string $content  Inner content.
	 * @return void
	 */
	public function render( $options, $defaults, $content ) {
		$template_id = isset( $options['template_id'] ) ? absint( $options['template_id'] ) : 0;

		// With nothing picked, the placeholder only shows inside the builder.
		if ( ! $template_id && ! $this->is_ajax_render() ) {
			return;
		}

		echo Template_Renderer::render( $template_id, 'lcpro-oxygen-template-wrapper', $this->is_oxygen_builder_editor() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block markup, escaped by the block renderers.
	}

	/**
	 * Element controls.
	 *
	 * @return void
	 */
	public function controls() {
		$this->addOptionControl(
			array(
				'type'    => 'dropdown',
				'name'    => esc_html__( 'Saved Template', 'logo-carousel-free' ),
				'slug'    => 'template_id',
				'default' => 0,
			)
		)->setValue( Template_List::options( 0 ) )->rebuildElementOnChange();
	}
}
