<?php
/**
 * Block-system orchestrator for the free plugin.
 *
 * Boots the Carousel + Grid blocks, the React dashboard, Saved Templates,
 * Ready Patterns and the AI builder. The legacy 3.x shortcode system is
 * still booted by `SP_Logo_Carousel` in main.php; this class only owns the
 * block-based system ported from Logo Carousel Pro.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/includes
 */

namespace ShapedPlugin\LogoCarouselFree\Includes;

use ShapedPlugin\LogoCarouselFree\Admin\AdminNotices\AdminNotices;
use ShapedPlugin\LogoCarouselFree\Admin\AdminNotices\DiagnosticConsentNotice;
use ShapedPlugin\LogoCarouselFree\Admin\Dashboard\BlockUsage;
use ShapedPlugin\LogoCarouselFree\Admin\Dashboard\Dashboard;
use ShapedPlugin\LogoCarouselFree\Admin\Dashboard\DiagnosticData;
use ShapedPlugin\LogoCarouselFree\Admin\Dashboard\SavedTemplatesRestController;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Manager as PageBuildersManager;
use ShapedPlugin\LogoCarouselFree\Blocks\BlockTypesController;
use ShapedPlugin\LogoCarouselFree\Includes\AI\AIRestController;
use ShapedPlugin\LogoCarouselFree\Includes\AI\ConnectorBridge;
use ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns\Controller as ReadyPatternsController;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\LogoCarouselFree' ) ) {
	/**
	 * Wires the block-based system's hooks.
	 */
	class LogoCarouselFree {
		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = SP_LOGO_CAROUSEL_FREE_VERSION;

		/**
		 * Constructor.
		 */
		public function __construct() {
			new Dashboard();
			// Stamps widget saves, so the Placements table can date them.
			BlockUsage::register_hooks();
			new AdminNotices();

			// Opt-in diagnostic data: weekly, plus once right after consent.
			DiagnosticData::register_hooks();
			new DiagnosticConsentNotice();

			// Saved Templates: CPT + [lc_template] render and the dashboard
			// REST CRUD. One shared instance; both self-gate on the
			// saved-templates module.
			$saved_templates = new SavedTemplates();
			new SavedTemplatesRestController( $saved_templates );

			$this->init_blocks();
		}

		/**
		 * Initialize the block architecture bootstrap.
		 *
		 * @return void
		 */
		private function init_blocks() {
			if ( version_compare( $GLOBALS['wp_version'], '5.8', '<' ) ) {
				return;
			}

			BlockCategories::init();

			$asset_manager = AssetManager::instance();
			add_action( 'init', array( $asset_manager, 'register_block_editor_assets' ), 10 );
			add_action( 'init', array( $asset_manager, 'register_frontend_assets' ), 10 );

			$block_types_controller = new BlockTypesController();
			$block_types_controller->init();

			new ReadyPatternsController();

			// The AI builder's own route, and the hooks that keep cached
			// WordPress Connectors state honest when a key is rotated or a
			// provider plugin is switched off.
			new AIRestController();
			ConnectorBridge::init();

			$upload_resolver = new LogoUploadResolver();
			$upload_resolver->init();

			add_action( 'enqueue_block_editor_assets', array( $asset_manager, 'enqueue_block_assets' ) );
			add_action( 'enqueue_block_assets', array( $asset_manager, 'enqueue_block_assets' ) );

			// Page Builder Integrations (Elementor, Divi, WPBakery, Beaver, Bricks, Oxygen).
			// On plugins_loaded so builders that load after this plugin are detected.
			// They render Saved Templates, which need the block system above.
			add_action( 'plugins_loaded', array( PageBuildersManager::class, 'init' ) );
		}
	}
}
