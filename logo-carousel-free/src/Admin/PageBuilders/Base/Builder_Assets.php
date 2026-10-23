<?php
/**
 * Shared block asset loader for page builder integrations.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base;

use ShapedPlugin\LogoCarouselFree\Includes\AssetManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Single source of the block assets a saved template needs inside a page builder.
 *
 * A saved template is block content, and a Logo Carousel block enqueues its runtime
 * while it renders. Inside a builder editor that render happens in an AJAX request,
 * so the enqueue never reaches the page. Every integration therefore loads the set
 * up front through this class, which delegates to AssetManager so the handle/source
 * map keeps living in exactly one place.
 *
 * @since 4.0.0
 */
class Builder_Assets {

	/**
	 * Frontend runtime handle the re-init script is attached to.
	 *
	 * @var string
	 */
	const RUNTIME_HANDLE = 'sp-logo-carousel-frontend';

	/**
	 * Wrapper classes whose re-init script was attached in this request.
	 *
	 * @var array<string, bool>
	 */
	private static $reinit_attached = array();

	/**
	 * The handles a saved template needs, for builders that declare dependencies.
	 *
	 * @since 4.0.0
	 *
	 * @return array With `scripts` and `styles` keys.
	 */
	public static function manifest() {
		return array(
			'scripts' => array( self::RUNTIME_HANDLE ),
			'styles'  => array(
				'sp-logo-carousel-frontend-style',
				'sp-logo-carousel-slot-dimensions',
				'sp-logo-carousel-hover-animations',
				'sp-logo-carousel-missing-media-style',
				'sp-logo-carousel-missing-media-shared-style',
				'sp-logo-carousel-frontend-bundle-style',
			),
		);
	}

	/**
	 * Register every block frontend handle.
	 *
	 * Safe to call from any enqueue hook and any number of times.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function register() {
		$assets = AssetManager::instance();
		$assets->register_frontend_assets();
		$assets->register_popup_assets();
	}

	/**
	 * Register and enqueue the whole set.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function enqueue() {
		$assets = AssetManager::instance();
		$assets->enqueue_block_frontend_assets();
		$assets->enqueue_popup_assets();
	}

	/**
	 * Inline bootstrap that re-runs the block frontend init after a builder re-render.
	 *
	 * Builders swap module markup in over AJAX without a page load, so Swiper, the
	 * marquee and the filters have to be initialised again. Each builder passes its
	 * own wrapper class and event names; the MutationObserver is the fallback for
	 * builders that fire nothing useful. Written without jQuery because the block
	 * runtime does not depend on it.
	 *
	 * @since 4.0.0
	 *
	 * @param string $wrapper_class Wrapper class the builder renders around the template.
	 * @param array  $events        Document events that signal a re-render.
	 * @return void
	 */
	public static function reinit_script( $wrapper_class, array $events = array() ) {
		if ( isset( self::$reinit_attached[ $wrapper_class ] ) ) {
			return;
		}

		self::$reinit_attached[ $wrapper_class ] = true;

		self::enqueue();

		$script = sprintf(
			'(function(){
	var target = %1$s;
	var events = %2$s;
	function reinit(){
		if ( typeof window.spLcproBlocksInit === "function" ) {
			window.spLcproBlocksInit();
		}
	}
	events.forEach( function( name ){ document.addEventListener( name, reinit ); } );
	if ( window.jQuery ) {
		events.forEach( function( name ){ window.jQuery( document ).on( name, reinit ); } );
	}
	function matches( node ){
		if ( node.nodeType !== 1 ) { return false; }
		return node.classList.contains( target ) || !! node.querySelector( "." + target );
	}
	var observer = new MutationObserver( function( mutations ){
		for ( var m = 0; m < mutations.length; m++ ) {
			var nodes = mutations[ m ].addedNodes;
			for ( var i = 0; i < nodes.length; i++ ) {
				if ( matches( nodes[ i ] ) ) {
					reinit();
					return;
				}
			}
		}
	} );
	function observe(){
		if ( document.body ) {
			observer.observe( document.body, { childList: true, subtree: true } );
		}
	}
	if ( "loading" === document.readyState ) {
		document.addEventListener( "DOMContentLoaded", observe );
	} else {
		observe();
	}
})();',
			wp_json_encode( $wrapper_class ),
			wp_json_encode( array_values( $events ) )
		);

		wp_add_inline_script( self::RUNTIME_HANDLE, $script );
	}
}
