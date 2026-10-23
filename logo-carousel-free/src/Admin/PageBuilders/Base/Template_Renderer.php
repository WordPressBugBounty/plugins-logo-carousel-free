<?php
/**
 * Saved template renderer for page builder integrations.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base;

use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\ModulesVisibility;
use ShapedPlugin\LogoCarouselFree\Includes\SavedTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Renders a saved template inside any page builder module.
 *
 * Every builder does the same thing — validate the picked template, render it
 * through the `[lc_template]` shortcode and wrap it in a builder-specific class
 * the re-init script watches — so the flow lives here once and each module only
 * passes its wrapper class.
 *
 * Block CSS needs no extra step: every Logo Carousel block prints its scoped
 * `<style>` next to its own markup. Google Fonts are the exception, since a block
 * enqueues them, and an enqueue made during a builder's AJAX re-render never
 * reaches the page — so inside an editor the font stylesheet is printed inline.
 *
 * @since 4.0.0
 */
class Template_Renderer {

	/**
	 * Handle the block renderer registers its merged Google Fonts under.
	 *
	 * @var string
	 */
	const FONTS_HANDLE = 'sp-lcpro-block-google-fonts';

	/**
	 * Render a saved template, or a placeholder explaining why it cannot be shown.
	 *
	 * Inside a builder editor the "Edit This Template" / "Add New Template" actions
	 * sit above it, unless the builder already offers them in its own panel.
	 *
	 * @since 4.0.0
	 *
	 * @param int|string $template_id   Template post ID.
	 * @param string     $wrapper_class Builder-specific wrapper class.
	 * @param bool       $is_editor     Whether the output is for a builder editor.
	 * @param bool       $show_actions  Whether to print the editor actions (editor only).
	 * @return string
	 */
	public static function render( $template_id, $wrapper_class, $is_editor = false, $show_actions = true ) {
		$template_id   = absint( $template_id );
		$template_post = $template_id ? get_post( $template_id ) : null;
		$is_template   = $template_post && SavedTemplates::POST_TYPE === $template_post->post_type;
		$actions       = $is_editor && $show_actions ? Template_Actions::canvas_html( $is_template ? $template_id : 0 ) : '';

		return $actions . self::render_body( $template_id, $template_post, $is_template, $wrapper_class, $is_editor );
	}

	/**
	 * The template markup, or the placeholder that replaces it.
	 *
	 * @param int           $template_id   Template post ID.
	 * @param \WP_Post|null $template_post Fetched post.
	 * @param bool          $is_template   Whether the post is a saved template.
	 * @param string        $wrapper_class Builder-specific wrapper class.
	 * @param bool          $is_editor     Whether the output is for a builder editor.
	 * @return string
	 */
	private static function render_body( $template_id, $template_post, $is_template, $wrapper_class, $is_editor ) {
		if ( ! $template_id ) {
			return self::error_message( esc_html__( 'Please Select a Saved Template', 'logo-carousel-free' ) );
		}

		if ( ! $is_template || 'publish' !== $template_post->post_status ) {
			return self::error_message( esc_html__( 'Template not found or not published.', 'logo-carousel-free' ) );
		}

		if ( '' === trim( (string) $template_post->post_content ) ) {
			return self::error_message( esc_html__( 'Template content is empty.', 'logo-carousel-free' ) );
		}

		if ( ! ( new ModulesVisibility() )->is_enabled( SavedTemplates::MODULE ) ) {
			// Visitors get nothing, the same as the shortcode; editors learn why.
			if ( ! current_user_can( 'edit_posts' ) ) {
				return '';
			}

			return self::error_message( esc_html__( 'Enable the Saved Templates module in Logo Carousel → Modules to display this template.', 'logo-carousel-free' ) );
		}

		$content = (string) do_shortcode( SavedTemplates::build_shortcode( $template_id ) );

		return sprintf(
			'<div class="%1$s" data-builder-template-id="%2$s">%3$s</div>%4$s',
			esc_attr( $wrapper_class ),
			esc_attr( $template_id ),
			$content,
			$is_editor ? self::fonts_markup() : ''
		);
	}

	/**
	 * Stylesheet link for the Google Fonts the rendered blocks asked for.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	private static function fonts_markup() {
		$styles = wp_styles();

		if ( empty( $styles->registered[ self::FONTS_HANDLE ] ) || empty( $styles->registered[ self::FONTS_HANDLE ]->src ) ) {
			return '';
		}

		return sprintf(
			'<link rel="stylesheet" id="%1$s-builder" href="%2$s" media="all">', // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- An enqueue made during a builder re-render never reaches the page.
			esc_attr( self::FONTS_HANDLE ),
			esc_url( $styles->registered[ self::FONTS_HANDLE ]->src )
		);
	}

	/**
	 * Placeholder markup shown in place of a template.
	 *
	 * @since 4.0.0
	 *
	 * @param string $message Message.
	 * @return string
	 */
	public static function error_message( $message ) {
		return sprintf(
			'<div style="text-align:center;padding:20px;border:2px dashed #ccc;color:#999;font-size:14px;">%s</div>',
			esc_html( $message )
		);
	}
}
