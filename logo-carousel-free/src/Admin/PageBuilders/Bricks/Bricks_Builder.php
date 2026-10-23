<?php
/**
 * Bricks Builder - Logo Carousel Element
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Bricks;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Assets;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Icon;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Actions;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_List;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Logo Carousel Bricks Builder Element.
 */
class LCPRO_Bricks_Logo_Carousel_Element extends \Bricks\Element {

	/**
	 * Element category.
	 *
	 * @var string
	 */
	public $category = 'general';

	/**
	 * Element name.
	 *
	 * @var string
	 */
	public $name = 'lcpro-bricks-logo-carousel';

	/**
	 * Element icon — the plugin glyph, its font loaded from Bricks/init.php.
	 *
	 * @var string
	 */
	public $icon = Builder_Icon::FONT_CLASS;

	/**
	 * CSS selector.
	 *
	 * @var string
	 */
	public $css_selector = '.lcpro-bricks-template-wrapper';

	/**
	 * Element tags for search.
	 *
	 * @var array
	 */
	public $tags = array( 'logo', 'carousel', 'slider', 'showcase' );

	/**
	 * Get element label.
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Logo Carousel', 'logo-carousel-free' );
	}

	/**
	 * Enqueue block scripts for the Bricks builder editor.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! $this->is_bricks_builder() ) {
			return;
		}

		// Re-initialise the block frontend after Bricks re-renders the element.
		Builder_Assets::reinit_script(
			'lcpro-bricks-template-wrapper',
			array(
				'bricksElementsReady',
				'bricks/setup_frontend',
				'bricks/pages/render',
				'bricks/element/after_render',
				'bricksAjaxRender',
			)
		);
	}

	/**
	 * Enqueue block styles for the Bricks builder editor.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( ! $this->is_bricks_builder() ) {
			return;
		}

		Builder_Assets::enqueue();
	}

	/**
	 * Set controls for the element.
	 *
	 * @return void
	 */
	public function set_controls() {
		$this->controls['template_id'] = array(
			'type'        => 'select',
			'label'       => esc_html__( 'Saved Template', 'logo-carousel-free' ),
			'options'     => Template_List::options( '0' ),
			'clearable'   => false,
			'default'     => '0',
			'pasteStyles' => true,
			'inline'      => true,
		);

		$this->controls['separator'] = array(
			'type' => 'separator',
		);

		$this->set_template_action_controls();
	}

	/**
	 * Add the "Edit This Template" / "Add New Template" buttons to the panel.
	 *
	 * Bricks blocks link clicks in its canvas, so the buttons live here, as in
	 * Elementor. A control's content is static, so there is one copy per
	 * template, each shown only while that template is picked. A last copy
	 * covers no pick and a template deleted since.
	 *
	 * Bricks compares a single `required` value strictly but a list loosely,
	 * so each ID goes in a list and matches whether it is stored as a string
	 * or an integer.
	 *
	 * @return void
	 */
	private function set_template_action_controls() {
		$can_manage   = Template_Actions::can_manage();
		$template_ids = array();

		if ( $can_manage ) {
			foreach ( array_keys( Template_List::all() ) as $template_id ) {
				$template_ids[] = (string) $template_id;

				$this->controls[ 'template_actions_' . $template_id ] = array(
					'type'     => 'info',
					'content'  => Template_Actions::panel_html( $template_id, '' ),
					'required' => array( 'template_id', '=', array( (string) $template_id ) ),
				);
			}
		}

		$help = array(
			'type'    => 'info',
			'content' => esc_html__( 'Please Select a Saved Template', 'logo-carousel-free' )
				. ( $can_manage ? Template_Actions::panel_html( 0, 'margin-top:12px;' ) : '' ),
		);

		// An empty list would hide this copy when nothing is picked.
		if ( $template_ids ) {
			$help['required'] = array( 'template_id', '!=', $template_ids );
		}

		$this->controls['help'] = $help;
	}

	/**
	 * Check if currently rendering in Bricks Builder.
	 *
	 * @return bool
	 */
	private function is_bricks_builder() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only builder-context detection, no state change.
		if ( isset( $_GET['bricks'] ) ) {
			return true;
		}

		if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
			return true;
		}

		// Only the builder's own AJAX / REST renders. For a page load,
		// bricks_is_builder_call() also trusts a referer with `bricks=run`,
		// which marks a page opened from the builder's preview as the editor.
		if ( ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}

		return function_exists( 'bricks_is_builder_call' ) && bricks_is_builder_call();
	}

	/**
	 * Render the element — in the editor and on the frontend.
	 *
	 * @return void
	 */
	public function render() {
		$template_id = isset( $this->settings['template_id'] ) ? (int) $this->settings['template_id'] : 0;

		echo '<div ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by render_attributes().
		// The template actions live in the settings panel, not the canvas.
		echo Template_Renderer::render( $template_id, 'lcpro-bricks-template-wrapper', $this->is_bricks_builder(), false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block markup, escaped by the block renderers.
		echo '</div>';
	}
}
