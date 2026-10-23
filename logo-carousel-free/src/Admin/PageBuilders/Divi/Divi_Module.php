<?php
/**
 * Divi Builder (Divi 4) module.
 *
 * Loaded only by Divi_Builder::register_module(), once `ET_Builder_Module` exists.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Divi;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_List;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Logo Carousel saved template module for Divi 4.
 *
 * @since 4.0.0
 */
class Divi_Module extends \ET_Builder_Module {

	/**
	 * Module slug.
	 *
	 * @var string
	 */
	public $slug = Divi_Builder::MODULE_SLUG;

	/**
	 * Visual Builder support.
	 *
	 * @var string
	 */
	public $vb_support = 'partial';

	/**
	 * Module credits.
	 *
	 * @var array
	 */
	protected $module_credits = array(
		'module_uri' => 'https://shapedplugin.com',
		'author'     => 'ShapedPlugin',
	);

	/**
	 * Initialize the module.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function init() {
		$this->name = esc_html__( 'Logo Carousel', 'logo-carousel-free' );
	}

	/**
	 * Get fields.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'template_id' => array(
				'label'           => esc_html__( 'Saved Template', 'logo-carousel-free' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => Template_List::options( '0' ),
				'default'         => '0',
				'description'     => esc_html__( 'Select a saved logo carousel template', 'logo-carousel-free' ),
				'toggle_slug'     => 'main_content',
			),
		);
	}

	/**
	 * Render module.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $attrs       Module attributes.
	 * @param string $content     Module content.
	 * @param string $render_slug Module render slug.
	 * @return string Rendered content.
	 */
	public function render( $attrs, $content = null, $render_slug = '' ) {
		// Check attrs first, fall back to props.
		$template_id = isset( $attrs['template_id'] ) ? $attrs['template_id'] : '0';
		if ( empty( $template_id ) && isset( $this->props['template_id'] ) ) {
			$template_id = $this->props['template_id'];
		}

		$html = Template_Renderer::render( $template_id, 'lcpro-divi-template-wrapper', Divi_Builder::is_builder_editor() );

		// The Visual Builder HTML-entity-decodes this module's AJAX render before
		// inserting it, turning the &quot; inside the blocks' JSON data attributes
		// into raw quotes that cut each attribute short, so Swiper starts with no
		// config. Escaping every & once makes that decode give back the markup
		// exactly as rendered.
		if ( self::is_visual_builder_render() ) {
			$html = str_replace( '&', '&amp;', $html );
		}

		return $html;
	}

	/**
	 * Whether this is the Visual Builder's AJAX render of a module.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	private static function is_visual_builder_render() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only context check; Divi verifies the nonce before rendering.
		return wp_doing_ajax() && isset( $_POST['action'] ) && 'et_fb_ajax_render_shortcode' === sanitize_key( wp_unslash( $_POST['action'] ) );
	}
}
