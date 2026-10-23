<?php
/**
 * The Free Loader Class
 *
 * @package logo-carousel-free
 * @since 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The Free Loader Class
 *
 * Loads the legacy admin scripts and the shortcode renderer. The old Get Help,
 * Recommended, Lite vs Pro and Analytics submenus it used to register were
 * replaced in 4.0.0 by the React dashboard (`lcpro_dashboard`), which also
 * redirects their old URLs to the matching tab.
 *
 * @package logo-carousel-free
 * @since 3.0
 */
class SPLC_Free_Loader {

	/**
	 * Free Loader constructor
	 *
	 * @return void
	 */
	public function __construct() {
		require_once SP_LC_PATH . 'admin/views/scripts.php';
		require_once SP_LC_PATH . 'public/views/shortcoderender.php';
	}
}

new SPLC_Free_Loader();
