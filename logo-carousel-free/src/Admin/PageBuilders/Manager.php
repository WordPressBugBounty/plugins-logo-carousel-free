<?php
/**
 * Page Builder Integration Manager
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders;

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Integration_Settings;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Divi5\Divi5_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Page builder integrations manager.
 *
 * Conditionally loads integrations based on:
 * 1. Whether the page builder is active
 * 2. The dashboard's Settings → Integrations toggles
 *
 * Every integration renders a Logo Carousel saved template (`[lc_template]`).
 *
 * @since 4.0.0
 */
class Manager {

	/**
	 * Whether init() already ran.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Integrations loaded through a class with a static init().
	 *
	 * @since 4.0.0
	 *
	 * @return array Integration key => class name.
	 */
	private static function get_integrations() {
		return array(
			'elementor' => Elementor\Elementor_Builder::class,
			'divi'      => Divi\Divi_Builder::class,
			'wpbakery'  => WPBakery\WPBakery_Builder::class,
		);
	}

	/**
	 * Check if a page builder is active.
	 *
	 * @since 4.0.0
	 *
	 * @param string $builder Builder key.
	 * @return bool
	 */
	public static function is_builder_active( $builder ) {
		switch ( $builder ) {
			case 'elementor':
				return defined( 'ELEMENTOR_VERSION' );
			case 'divi':
				return defined( 'ET_BUILDER_VERSION' );
			case 'bricks':
				return defined( 'BRICKS_VERSION' );
			case 'beaver':
				return class_exists( 'FLBuilder' );
			case 'wpbakery':
				return defined( 'WPB_VC_VERSION' );
			case 'oxygen':
				return defined( 'CT_VERSION' ) || class_exists( 'OxyEl' );
			default:
				return false;
		}
	}

	/**
	 * Whether a builder's Logo Carousel element renders on this site: the
	 * builder is active and its integration is switched on. Anything else
	 * outputs nothing (or an empty shortcode), so it is not a placement.
	 *
	 * @param string $builder Builder key.
	 * @return bool
	 */
	public static function is_integration_live( $builder ) {
		return self::is_builder_active( $builder ) && Integration_Settings::is_enabled( $builder );
	}

	/**
	 * Initialize all integrations.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		// Divi 5 registers its module through its own dependency tree and REST routes.
		add_action( 'init', array( __CLASS__, 'load_divi5_server_module' ), 10 );
		add_action( 'rest_api_init', array( __CLASS__, 'load_divi5_rest_api' ), 10 );

		// Oxygen and Beaver register on their own hooks once their files are loaded.
		self::load_oxygen_integration();
		self::load_beaver_integration();

		// For Bricks, load on init hook with priority 10 (before init.php registers at priority 11).
		add_action( 'init', array( __CLASS__, 'load_bricks_integration' ), 10 );

		foreach ( self::get_integrations() as $key => $class ) {
			// For Divi, delay the check to init since Divi loads late.
			if ( 'divi' === $key ) {
				add_action( 'init', array( __CLASS__, 'check_and_load_divi' ), 20 );
				continue;
			}

			if ( ! self::is_builder_active( $key ) ) {
				continue;
			}

			if ( ! Integration_Settings::is_enabled( $key ) ) {
				// Pages saved while it was on keep the element's shortcode in their
				// content; render it as nothing rather than leak the raw tag.
				if ( 'wpbakery' === $key ) {
					add_shortcode( WPBakery\WPBakery_Builder::BASE, '__return_empty_string' );
				}
				continue;
			}

			if ( class_exists( $class ) ) {
				$class::init();
			}
		}
	}

	/**
	 * Load Oxygen integration.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private static function load_oxygen_integration() {
		if ( ! class_exists( 'OxyEl' ) || ! Integration_Settings::is_enabled( 'oxygen' ) ) {
			return;
		}

		require_once __DIR__ . '/Oxygen/init.php';
	}

	/**
	 * Load Beaver Builder integration.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private static function load_beaver_integration() {
		if ( ! class_exists( 'FLBuilder' ) || ! Integration_Settings::is_enabled( 'beaver' ) ) {
			return;
		}

		require_once __DIR__ . '/Beaver/Beaver_Builder.php';
	}

	/**
	 * Load Bricks Builder integration.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function load_bricks_integration() {
		if ( ! self::is_builder_active( 'bricks' ) || ! Integration_Settings::is_enabled( 'bricks' ) ) {
			return;
		}

		require_once __DIR__ . '/Bricks/init.php';
	}

	/**
	 * Check and load the Divi integration on init.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function check_and_load_divi() {
		if ( ! Integration_Settings::is_enabled( 'divi' ) ) {
			// Divi 4 stores modules as shortcodes; hide a disabled one instead of leaking the tag.
			if ( defined( 'ET_BUILDER_VERSION' ) && ! Divi5_Builder::is_divi_5_active() ) {
				add_shortcode( Divi\Divi_Builder::MODULE_SLUG, '__return_empty_string' );
			}
			return;
		}

		// Divi 5 first.
		if ( Divi5_Builder::is_divi_5_active() ) {
			Divi5_Builder::init();
			return;
		}

		// Fall back to Divi 4.
		if ( ! defined( 'ET_BUILDER_VERSION' ) && ! class_exists( 'ET_Builder_Module' ) ) {
			return;
		}

		Divi\Divi_Builder::init();
	}

	/**
	 * Whether the Divi theme or the Divi Builder plugin is active.
	 *
	 * `ET_BUILDER_VERSION` may not be defined yet this early, so the theme is checked too.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	private static function is_divi_present() {
		$theme = wp_get_theme();

		return 'Divi' === $theme->get( 'Name' ) || 'divi' === $theme->get( 'Template' ) || defined( 'ET_BUILDER_VERSION' );
	}

	/**
	 * Load the Divi 5 server-side module.
	 *
	 * Runs on init at priority 10 so it registers before Divi fires
	 * `divi_module_library_modules_dependency_tree`.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function load_divi5_server_module() {
		if ( ! self::is_divi_present() || ! Integration_Settings::is_enabled( 'divi' ) ) {
			return;
		}

		// Do NOT assume Divi 5 just because the Divi theme is active (could be Divi 4).
		if ( ! Divi5_Builder::is_divi_5_active() ) {
			return;
		}

		require_once __DIR__ . '/Divi5/server/index.php';
	}

	/**
	 * Load the Divi 5 REST API endpoints.
	 *
	 * Loaded independently of the server module so the endpoints exist even if it fails.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function load_divi5_rest_api() {
		if ( ! self::is_divi_present() || ! Integration_Settings::is_enabled( 'divi' ) ) {
			return;
		}

		require_once __DIR__ . '/Divi5/server/rest-api.php';

		// rest_api_init is already firing, so register directly instead of hooking.
		Divi5\register_rest_routes();
	}
}
