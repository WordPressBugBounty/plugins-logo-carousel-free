<?php
/**
 * "Edit This Template" / "Add New Template" actions for page builder integrations.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base;

use ShapedPlugin\LogoCarouselFree\Includes\SavedTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Single source of the template action buttons every builder shows.
 *
 * "Edit This Template" opens the picked template's own block-editor screen, and
 * "Add New Template" starts a new one. Both open in a new tab so leaving the page
 * builder never drops its unsaved changes. Elementor and Bricks print them in
 * their settings panel; the other builders get them above the template in their
 * editor canvas, since their setting panels cannot hold a link that follows the
 * picked template.
 *
 * @since 4.0.0
 */
class Template_Actions {

	/**
	 * Brand colour of the dashboard (`--lcpro-color-primary`).
	 *
	 * @var string
	 */
	const BRAND = '#1d8958';

	/**
	 * Brand hover colour (`--lcpro-color-primary-hover`).
	 *
	 * @var string
	 */
	const BRAND_HOVER = '#176d46';

	/**
	 * Edit screen of a saved template.
	 *
	 * Built directly rather than through get_edit_post_link(), which returns null
	 * whenever the CPT is not registered for the current request.
	 *
	 * @since 4.0.0
	 *
	 * @param int $template_id Template post ID.
	 * @return string
	 */
	public static function edit_url( $template_id ) {
		return admin_url( 'post.php?post=' . absint( $template_id ) . '&action=edit' );
	}

	/**
	 * Screen that starts a new saved template.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public static function new_url() {
		return admin_url( 'post-new.php?post_type=' . SavedTemplates::POST_TYPE );
	}

	/**
	 * Whether the current user may see the actions.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * The buttons, stacked, for a builder settings panel.
	 *
	 * @since 4.0.0
	 *
	 * @param int    $template_id   Picked template ID; 0 shows only "Add New Template".
	 * @param string $wrapper_style Spacing around the buttons.
	 * @return string
	 */
	public static function panel_html( $template_id = 0, $wrapper_style = 'margin-top:20px;' ) {
		$buttons = '';

		if ( $template_id ) {
			$buttons .= self::edit_button( $template_id, 'display:inline-block;margin-bottom:15px;' );
		}

		$buttons .= self::new_button( 'display:inline-block;' );

		return '<div class="lcpro-builder-template-buttons" style="' . esc_attr( $wrapper_style ) . '">' . $buttons . '</div>';
	}

	/**
	 * The buttons in a row, for a builder editor canvas.
	 *
	 * @since 4.0.0
	 *
	 * @param int $template_id Picked template ID; 0 shows only "Add New Template".
	 * @return string Empty when the current user cannot edit posts.
	 */
	public static function canvas_html( $template_id = 0 ) {
		if ( ! self::can_manage() ) {
			return '';
		}

		$buttons = '';

		if ( $template_id ) {
			$buttons .= self::edit_button( $template_id, 'display:inline-flex;align-items:center;' );
		}

		$buttons .= self::new_button( 'display:inline-flex;align-items:center;' );

		// Positioned above the hover overlays and drop zones builders lay over
		// a module (Divi's sit at z-index 9), which would take the click.
		return '<div class="lcpro-builder-template-actions" style="position:relative;z-index:20;display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end;margin:0 0 12px;">' . $buttons . '</div>';
	}

	/**
	 * Keep the canvas buttons working in builders that swallow clicks inside
	 * a module.
	 *
	 * Beaver Builder and Divi 5 cancel link clicks in their editor canvas and
	 * select the module on any press inside it, so the buttons did nothing.
	 * Capture-phase listeners on window run before those handlers, stop the
	 * press and the click there, and open the link themselves.
	 *
	 * @since 4.0.0
	 *
	 * @param string $handle Script handle loaded in the builder editor.
	 * @return void
	 */
	public static function enqueue_canvas_link_handler( $handle ) {
		static $attached = false;

		if ( $attached ) {
			return;
		}

		$attached = true;

		wp_add_inline_script(
			$handle,
			'(function(){
	function link( e ){
		var el = e.target && e.target.closest ? e.target.closest( ".lcpro-builder-template-actions a[href]" ) : null;
		if ( ! el ) { return null; }
		e.stopImmediatePropagation();
		return el;
	}
	window.addEventListener( "click", function( e ){
		var el = link( e );
		if ( ! el ) { return; }
		e.preventDefault();
		window.open( el.href, "_blank", "noopener,noreferrer" );
	}, true );
	[ "pointerdown", "pointerup", "mousedown", "mouseup", "touchstart", "touchend" ].forEach( function( name ){
		window.addEventListener( name, link, true );
	} );
})();'
		);
	}

	/**
	 * "Edit This Template" button.
	 *
	 * @param int    $template_id Template post ID.
	 * @param string $layout      Layout declarations for the button.
	 * @return string
	 */
	private static function edit_button( $template_id, $layout ) {
		return sprintf(
			'<a class="lcpro-edit-template-btn" href="%1$s" target="_blank" rel="noopener noreferrer" style="%2$scolor:#fff;background-color:#3e3e40;padding:10px 20px;border-radius:4px;font-size:14px;line-height:1.4;text-decoration:none;" onmouseover="this.style.backgroundColor=\'#4b4b4d\'" onmouseout="this.style.backgroundColor=\'#3e3e40\'"><span style="display:inline-block;transform:rotate(70deg);margin-right:6px;">✎</span><span>%3$s</span></a>',
			esc_url( self::edit_url( $template_id ) ),
			esc_attr( $layout ),
			esc_html__( 'Edit This Template', 'logo-carousel-free' )
		);
	}

	/**
	 * "Add New Template" button, in the dashboard brand colour.
	 *
	 * @param string $layout Layout declarations for the button.
	 * @return string
	 */
	private static function new_button( $layout ) {
		return sprintf(
			'<a class="lcpro-add-template-btn" href="%1$s" target="_blank" rel="noopener noreferrer" style="%2$scolor:#fff;background-color:%3$s;padding:10px 20px;border-radius:4px;font-size:14px;line-height:1.4;text-decoration:none;" onmouseover="this.style.backgroundColor=\'%4$s\'" onmouseout="this.style.backgroundColor=\'%3$s\'"><span style="display:inline-block;font-size:18px;line-height:1;margin-right:6px;">+</span><span>%5$s</span></a>',
			esc_url( self::new_url() ),
			esc_attr( $layout ),
			esc_attr( self::BRAND ),
			esc_attr( self::BRAND_HOVER ),
			esc_html__( 'Add New Template', 'logo-carousel-free' )
		);
	}
}
