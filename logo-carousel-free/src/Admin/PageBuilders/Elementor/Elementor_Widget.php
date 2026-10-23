<?php
/**
 * Elementor widget for Logo Carousel saved templates.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Assets;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Builder_Icon;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Actions;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_List;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor widget for Logo Carousel saved templates.
 *
 * @since 4.0.0
 */
class Elementor_Widget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @since 4.0.0
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'sp_logo_carousel_pro_saved_template';
	}

	/**
	 * Get widget title.
	 *
	 * @since 4.0.0
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Logo Carousel Saved Template', 'logo-carousel-free' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 4.0.0
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		// Same glyph as the classic "Logo Carousel Shortcode" widget.
		return Builder_Icon::FONT_CLASS;
	}

	/**
	 * Get widget categories.
	 *
	 * @since 4.0.0
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'basic' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 4.0.0
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'logo', 'carousel', 'slider', 'showcase', 'template' );
	}

	/**
	 * Scripts the widget needs, taken from the Builder_Assets manifest.
	 *
	 * @since 4.0.0
	 *
	 * @return array Script handles.
	 */
	public function get_script_depends() {
		return Builder_Assets::manifest()['scripts'];
	}

	/**
	 * Styles the widget needs, taken from the Builder_Assets manifest.
	 *
	 * @since 4.0.0
	 *
	 * @return array Style handles.
	 */
	public function get_style_depends() {
		return Builder_Assets::manifest()['styles'];
	}

	/**
	 * Controls register.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Settings', 'logo-carousel-free' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'template_id',
			array(
				'label'       => __( 'Saved Template', 'logo-carousel-free' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => '0',
				'options'     => Template_List::options( '0' ),
			)
		);

		$this->register_template_actions();

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function render() {
		$settings    = $this->get_settings_for_display();
		$template_id = isset( $settings['template_id'] ) ? (int) $settings['template_id'] : 0;
		$is_editor   = \Elementor\Plugin::$instance->editor->is_edit_mode();

		// The actions live in the panel here, so the canvas stays template-only.
		echo Template_Renderer::render( $template_id, 'lcpro-elementor-template-wrapper', $is_editor, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block markup, escaped by the block renderers.
	}

	/**
	 * Add the "Edit This Template" / "Add New Template" buttons.
	 *
	 * RAW_HTML is static, so there is one copy per template, each shown only
	 * while that template is picked — "Edit This Template" then always opens the
	 * selected template's own edit screen. A last copy with only "Add New
	 * Template" covers no pick, a cleared pick and a template deleted since.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function register_template_actions() {
		$template_ids = array();

		foreach ( array_keys( Template_List::all() ) as $template_id ) {
			$template_ids[] = (string) $template_id;

			$this->add_control(
				'lcpro_template_actions_' . $template_id,
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => Template_Actions::panel_html( $template_id ),
					'content_classes' => 'lcpro-elementor-template-actions',
					'condition'       => array( 'template_id' => (string) $template_id ),
				)
			);
		}

		$this->add_control(
			'lcpro_template_actions_new',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => Template_Actions::panel_html( 0 ),
				'content_classes' => 'lcpro-elementor-template-actions',
				'condition'       => array( 'template_id!' => $template_ids ),
			)
		);
	}
}
