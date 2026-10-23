<?php
/**
 * Divi 5 REST API Endpoints
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders/Divi5
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Divi5;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_List;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Renderer;
use ShapedPlugin\LogoCarouselFree\Includes\SavedTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Cannot access directly.
}

if ( ! function_exists( __NAMESPACE__ . '\register_rest_routes' ) ) {

	/**
	 * Register REST API endpoints for the Divi 5 module.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	function register_rest_routes() {
		$can_edit = static function () {
			return current_user_can( 'edit_posts' );
		};

		register_rest_route(
			'sp-lcpro/divi5/v1',
			'/template-html',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => __NAMESPACE__ . '\get_template_html',
					'permission_callback' => $can_edit,
					'args'                => array(
						'template_id' => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
							'description'       => 'Saved template ID',
						),
					),
				),
			)
		);

		register_rest_route(
			'sp-lcpro/divi5/v1',
			'/saved-templates',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => __NAMESPACE__ . '\get_saved_templates',
					'permission_callback' => $can_edit,
				),
			)
		);
	}

	/**
	 * Get a saved template's HTML for the visual builder.
	 *
	 * @since 4.0.0
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response
	 */
	function get_template_html( $request ) {
		$template_id   = absint( $request->get_param( 'template_id' ) );
		$template_post = $template_id ? get_post( $template_id ) : null;

		if ( ! $template_post || SavedTemplates::POST_TYPE !== $template_post->post_type || 'publish' !== $template_post->post_status ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'html'    => Template_Renderer::error_message( esc_html__( 'Template not found or not published.', 'logo-carousel-free' ) ),
				),
				$template_id ? 404 : 400
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'html'    => Template_Renderer::render( $template_id, 'lcpro-divi5-template', true ),
			),
			200
		);
	}

	/**
	 * Get the saved templates list for the settings dropdown.
	 *
	 * @since 4.0.0
	 *
	 * @return \WP_REST_Response
	 */
	function get_saved_templates() {
		$result = array();

		// Divi 5's select control keys options by string.
		foreach ( Template_List::options( '0' ) as $id => $title ) {
			$result[ (string) $id ] = $title;
		}

		return new \WP_REST_Response( $result, 200 );
	}
}
