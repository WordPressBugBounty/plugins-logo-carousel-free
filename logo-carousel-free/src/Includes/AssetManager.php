<?php
/**
 * Asset Manager
 *
 * Registers the block architecture asset handles used by Logo Carousel.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\CarouselSchema;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\GridSchema;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\PerformanceConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\GoogleFontsResolver;
use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\BlocksVisibility;
use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\ModulesVisibility;
use ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns\PatternRepository;
use ShapedPlugin\LogoCarouselFree\Includes\AI\AIFeature;
use ShapedPlugin\LogoCarouselFree\Includes\AI\AIKeyManager;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AssetManager' ) ) {
	/**
	 * Block asset registry and enqueue helper.
	 */
	class AssetManager {
		/**
		 * Singleton instance.
		 *
		 * @var AssetManager|null
		 */
		private static $instance = null;

		/**
		 * Whether frontend assets were already enqueued this request.
		 *
		 * @var bool
		 */
		private $frontend_assets_enqueued = false;

		/**
		 * Resolved handle for the remodal popup script (block or legacy).
		 *
		 * @var string
		 */
		private $popup_script_handle = '';

		/**
		 * Google Font families enqueued so far this request (merged across every
		 * block instance so a page with several blocks loads every family once).
		 *
		 * @var array<int, string>
		 */
		private $enqueued_google_fonts = array();

		/**
		 * Whether custom block frontend CSS/JS were already attached this request.
		 *
		 * @var bool
		 */
		private $block_custom_assets_attached = false;

		/**
		 * Get singleton instance.
		 *
		 * @return AssetManager
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Register editor assets.
		 *
		 * @return void
		 */
		public function register_block_editor_assets() {
			$asset_file = SP_LOGO_CAROUSEL_FREE_FILE ? dirname( SP_LOGO_CAROUSEL_FREE_FILE ) . '/assets/editor/index.asset.php' : '';
			$script     = 'assets/editor/index.js';
			$style      = 'assets/editor/index.css';

			if ( ! file_exists( $this->asset_path( $script ) ) ) {
				return;
			}

			$deps = array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' );
			$ver  = SP_LOGO_CAROUSEL_FREE_VERSION;

			if ( $asset_file && file_exists( $asset_file ) ) {
				$asset = require $asset_file;

				if ( ! empty( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ) {
					$deps = $asset['dependencies'];
				}

				if ( ! empty( $asset['version'] ) ) {
					$ver = $asset['version'];
				}
			}

			wp_register_script(
				'sp-logo-carousel-editor',
				$this->asset_url( $script ),
				$deps,
				$ver,
				true
			);
			wp_localize_script(
				'sp-logo-carousel-editor',
				'spLcproBlockSchemas',
				$this->get_editor_block_schemas()
			);

			if ( file_exists( $this->asset_path( $style ) ) ) {
				wp_register_style(
					'sp-logo-carousel-editor',
					$this->asset_url( $style ),
					array(),
					$this->asset_version( $style )
				);
				wp_style_add_data( 'sp-logo-carousel-editor', 'rtl', 'replace' );
			}
		}

		/**
		 * Register shared frontend assets.
		 *
		 * @return void
		 */
		public function register_frontend_assets() {
			$asset_file   = dirname( SP_LOGO_CAROUSEL_FREE_FILE ) . '/assets/js/frontend.asset.php';
			$script       = 'assets/js/frontend.js';
			$style        = 'assets/js/style-frontend.css';
			$bundle_style = 'assets/js/frontend.css';

			if ( ! file_exists( $this->asset_path( $script ) ) ) {
				return;
			}

			$deps = array();
			$ver  = SP_LOGO_CAROUSEL_FREE_VERSION;

			if ( file_exists( $asset_file ) ) {
				$asset = require $asset_file;

				if ( ! empty( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ) {
					$deps = $asset['dependencies'];
				}

				if ( ! empty( $asset['version'] ) ) {
					$ver = $asset['version'];
				}
			}

			wp_register_script(
				'sp-logo-carousel-frontend',
				$this->asset_url( $script ),
				$deps,
				$ver,
				true
			);
			wp_set_script_translations( 'sp-logo-carousel-frontend', 'logo-carousel-free' );

			if ( file_exists( $this->asset_path( $style ) ) ) {
				wp_register_style(
					'sp-logo-carousel-frontend-style',
					$this->asset_url( $style ),
					array(),
					$this->asset_version( $style )
				);
				wp_style_add_data( 'sp-logo-carousel-frontend-style', 'rtl', 'replace' );
			}

			// Register slot dimensions CSS (universal containment strategy)
			$slot_dimensions_style = 'src/Blocks/assets/css/slot-dimensions.css';
			if ( file_exists( $this->asset_path( $slot_dimensions_style ) ) ) {
				wp_register_style(
					'sp-logo-carousel-slot-dimensions',
					$this->asset_url( $slot_dimensions_style ),
					array(),
					$this->asset_version( $slot_dimensions_style )
				);
				wp_style_add_data( 'sp-logo-carousel-slot-dimensions', 'rtl', 'replace' );
			}

			// Register the shared hover-animation keyframes. Constant across every
			// block, so they ship once rather than being regenerated per block.
			$hover_animations_style = 'src/Blocks/assets/css/hover-animations.css';
			if ( file_exists( $this->asset_path( $hover_animations_style ) ) ) {
				wp_register_style(
					'sp-logo-carousel-hover-animations',
					$this->asset_url( $hover_animations_style ),
					array(),
					$this->asset_version( $hover_animations_style )
				);
			}

			// Register missing media frontend styles
			$missing_media_style = 'src/Blocks/assets/css/missing-media-frontend.css';
			if ( file_exists( $this->asset_path( $missing_media_style ) ) ) {
				wp_register_style(
					'sp-logo-carousel-missing-media-style',
					$this->asset_url( $missing_media_style ),
					array(),
					$this->asset_version( $missing_media_style )
				);
				wp_style_add_data( 'sp-logo-carousel-missing-media-style', 'rtl', 'replace' );
			}

			$shared_missing_media_style = 'src/Blocks/assets/css/missing-media-placeholder.css';
			if ( file_exists( $this->asset_path( $shared_missing_media_style ) ) ) {
				wp_register_style(
					'sp-logo-carousel-missing-media-shared-style',
					$this->asset_url( $shared_missing_media_style ),
					array(),
					$this->asset_version( $shared_missing_media_style )
				);
				wp_style_add_data( 'sp-logo-carousel-missing-media-shared-style', 'rtl', 'replace' );
			}

			if ( file_exists( $this->asset_path( $bundle_style ) ) ) {
				wp_register_style(
					'sp-logo-carousel-frontend-bundle-style',
					$this->asset_url( $bundle_style ),
					array(),
					$this->asset_version( $bundle_style )
				);
				wp_style_add_data( 'sp-logo-carousel-frontend-bundle-style', 'rtl', 'replace' );
			}
		}

		/**
		 * Register the shared popup runtime asset.
		 *
		 * @return void
		 */
		public function register_popup_assets() {
			// The classic frontend registers the same remodal file under
			// 'lcp-remodal-js' on wp_loaded (unconditionally, before block
			// render). Reuse it so a page mixing shortcodes and blocks does not
			// load remodal.min.js twice — a second copy re-runs its IIFE and
			// binds the document click delegation again, firing each popup twice.
			if ( wp_script_is( 'lcp-remodal-js', 'registered' ) ) {
				$this->popup_script_handle = 'lcp-remodal-js';
				return;
			}

			$script = 'src/Frontend/assets/js/remodal.min.js';

			if ( ! file_exists( $this->asset_path( $script ) ) ) {
				return;
			}

			wp_register_script(
				'sp-logo-carousel-remodal',
				$this->asset_url( $script ),
				array( 'jquery' ),
				$this->asset_version( $script ),
				true
			);

			$this->popup_script_handle = 'sp-logo-carousel-remodal';
		}

		/**
		 * Enqueue block editor assets.
		 *
		 * @return void
		 */
		public function enqueue_block_assets() {
			$this->register_block_editor_assets();

			if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
				wp_enqueue_script( 'sp-logo-carousel-editor' );
				wp_enqueue_style( 'sp-logo-carousel-editor' );
				$this->attach_custom_editor_css();

				if ( wp_style_is( 'sp-logo-carousel-frontend-style', 'registered' ) ) {
					wp_enqueue_style( 'sp-logo-carousel-frontend-style' );
				}

				if ( wp_style_is( 'sp-logo-carousel-slot-dimensions', 'registered' ) ) {
					wp_enqueue_style( 'sp-logo-carousel-slot-dimensions' );
				}

				if ( wp_style_is( 'sp-logo-carousel-hover-animations', 'registered' ) ) {
					wp_enqueue_style( 'sp-logo-carousel-hover-animations' );
				}

				if ( wp_style_is( 'sp-logo-carousel-missing-media-style', 'registered' ) ) {
					wp_enqueue_style( 'sp-logo-carousel-missing-media-style' );
				}

				if ( wp_style_is( 'sp-logo-carousel-missing-media-shared-style', 'registered' ) ) {
					wp_enqueue_style( 'sp-logo-carousel-missing-media-shared-style' );
				}
			}
		}

		/**
		 * Enqueue shared frontend block assets.
		 *
		 * @return void
		 */
		public function enqueue_block_frontend_assets() {
			if ( $this->frontend_assets_enqueued ) {
				return;
			}

			$this->frontend_assets_enqueued = true;

			$this->register_frontend_assets();

			if ( wp_style_is( 'sp-logo-carousel-frontend-style', 'registered' ) ) {
				wp_enqueue_style( 'sp-logo-carousel-frontend-style' );
			}

			if ( wp_style_is( 'sp-logo-carousel-slot-dimensions', 'registered' ) ) {
				wp_enqueue_style( 'sp-logo-carousel-slot-dimensions' );
			}

			if ( wp_style_is( 'sp-logo-carousel-hover-animations', 'registered' ) ) {
				wp_enqueue_style( 'sp-logo-carousel-hover-animations' );
			}

			if ( wp_style_is( 'sp-logo-carousel-missing-media-style', 'registered' ) ) {
				wp_enqueue_style( 'sp-logo-carousel-missing-media-style' );
			}

			if ( wp_style_is( 'sp-logo-carousel-missing-media-shared-style', 'registered' ) ) {
				wp_enqueue_style( 'sp-logo-carousel-missing-media-shared-style' );
			}

			// The missing-media compact-card icon (dashicons-format-image) needs the
			// 'dashicons' font, which WordPress core only auto-loads in wp-admin.
			// Without this the icon glyph is blank on the frontend even though the
			// rest of the placeholder renders correctly.
			if ( wp_style_is( 'dashicons', 'registered' ) ) {
				wp_enqueue_style( 'dashicons' );
			}

			if ( wp_style_is( 'sp-logo-carousel-frontend-bundle-style', 'registered' ) ) {
				wp_enqueue_style( 'sp-logo-carousel-frontend-bundle-style' );
			}

			if ( wp_script_is( 'sp-logo-carousel-frontend', 'registered' ) ) {
				wp_enqueue_script( 'sp-logo-carousel-frontend' );
			}

			$this->attach_custom_block_frontend_assets();
		}

		/**
		 * Enqueue the popup runtime asset.
		 *
		 * @return void
		 */
		public function enqueue_popup_assets() {
			$this->register_popup_assets();

			if ( '' !== $this->popup_script_handle && wp_script_is( $this->popup_script_handle, 'registered' ) ) {
				wp_enqueue_script( $this->popup_script_handle );
			}
		}

		/**
		 * Enqueue the Google Fonts actually selected in a block's typography
		 * controls. Merges across every block instance rendered this request so
		 * a page with several blocks (each picking different fonts) still loads
		 * every family, instead of the first block's pick winning.
		 *
		 * @param array<string, mixed> $attributes Block attributes.
		 * @return void
		 */
		public function enqueue_google_fonts( array $attributes ) {
			$families = GoogleFontsResolver::collect_families( $attributes );
			if ( empty( $families ) ) {
				return;
			}

			$merged = array_values( array_unique( array_merge( $this->enqueued_google_fonts, $families ) ) );
			if ( $merged === $this->enqueued_google_fonts ) {
				return;
			}

			$this->enqueued_google_fonts = $merged;

			$handle = 'sp-lcpro-block-google-fonts';
			if ( wp_style_is( $handle, 'enqueued' ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}

			wp_enqueue_style( $handle, GoogleFontsResolver::build_url( $merged ), array(), SP_LOGO_CAROUSEL_FREE_VERSION );
		}

		/**
		 * Convert a relative asset path into an absolute plugin path.
		 *
		 * @param string $relative_path Relative path from plugin root.
		 * @return string
		 */
		private function asset_path( $relative_path ) {
			return dirname( SP_LOGO_CAROUSEL_FREE_FILE ) . '/' . ltrim( $relative_path, '/' );
		}

		/**
		 * Convert a relative asset path into a plugin URL.
		 *
		 * @param string $relative_path Relative path from plugin root.
		 * @return string
		 */
		private function asset_url( $relative_path ) {
			return plugin_dir_url( SP_LOGO_CAROUSEL_FREE_FILE ) . ltrim( $relative_path, '/' );
		}

		/**
		 * Get an asset version from filemtime when possible.
		 *
		 * @param string $relative_path Relative path from plugin root.
		 * @return string
		 */
		private function asset_version( $relative_path ) {
			$asset_path = $this->asset_path( $relative_path );

			if ( file_exists( $asset_path ) ) {
				return (string) filemtime( $asset_path );
			}

			return SP_LOGO_CAROUSEL_FREE_VERSION;
		}

		/**
		 * Return the shared block schemas exposed to the editor bundle.
		 *
		 * @return array<string, array<string, array<string, mixed>>>
		 */
		private function get_editor_block_schemas() {
			$modules = new ModulesVisibility();

			return array_merge(
				self::block_schemas(),
				array(
					'visibility'       => ( new BlocksVisibility() )->all(),
					'settings'         => array(
						'upload'      => $this->get_editor_upload_settings(),
						'performance' => $this->get_performance_settings(),
					),
					'extensionModules' => self::build_extension_modules(
						array(
							'saved-templates' => $modules->is_enabled( 'saved-templates' ),
							'ready-patterns'  => $modules->is_enabled( 'ready-patterns' ),
							'ai-builder'      => AIFeature::is_module_enabled(),
						)
					),
					'readyPatterns'    => array(
						'enabled'       => PatternRepository::is_enabled(),
						'restBase'      => PatternRepository::rest_base(),
						'pluginVersion' => ProFeatures::PRO_COMPAT_VERSION,
						'modulesUrl'    => admin_url( 'edit.php?post_type=sp_logo_carousel&page=lcpro_dashboard#modules' ),
						'activePlugins' => array(),
					),
					// One flag for every AI entry point, so the module toggle and the
					// site's own AI support cannot disagree between the header
					// launcher, the source screen, the block toolbar and the route.
					'aiBuilder'        => array(
						'enabled'     => AIFeature::is_available(),
						'settingsUrl' => admin_url( 'edit.php?post_type=sp_logo_carousel&page=lcpro_dashboard#settings=ai-configuration' ),
						'restPath'    => '/logo-carousel-pro/v1/ai-generate',
					),
					// Connector state is read from cache only here: this payload is
					// built while the editor loads, and probing a provider would put
					// an outbound HTTP request on that path.
					'aiSettings'       => AIFeature::is_available()
						? AIKeyManager::get_public_settings()
						: array(),
				)
			);
		}

		/**
		 * Attribute schemas for the blocks the free plugin registers.
		 *
		 * Only Carousel and Grid: List, Marquee and Table are Pro-only blocks.
		 *
		 * @return array<string, array<string, array<string, mixed>>>
		 */
		public static function block_schemas() {
			return array(
				'carousel' => ( new CarouselSchema() )->get_attributes(),
				'grid'     => ( new GridSchema() )->get_attributes(),
			);
		}

		/**
		 * Build the `extensionModules` flag map localized to the editor.
		 *
		 * Free modules carry their dashboard state; every Pro-only module
		 * (ProFeatures::PRO_MODULES) is always reported as `false`, so the
		 * editor's module gate neutralises those features on its render copy.
		 *
		 * @param array<string, bool> $free_modules Free module slug => enabled.
		 * @return array<string, bool>
		 */
		public static function build_extension_modules( array $free_modules ) {
			$flags = array();

			foreach ( $free_modules as $slug => $enabled ) {
				$flags[ (string) $slug ] = ProFeatures::is_pro_module( $slug ) ? false : (bool) $enabled;
			}

			foreach ( ProFeatures::PRO_MODULES as $slug ) {
				$flags[ $slug ] = false;
			}

			return $flags;
		}

		/**
		 * Return editor upload settings derived from WordPress limits.
		 *
		 * @return array<string, mixed>
		 */
		private function get_editor_upload_settings() {
			$max_upload_size = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
			$max_upload_size = $max_upload_size > 0 ? $max_upload_size : 0;

			return array(
				'maxUploadSizeBytes'  => $max_upload_size,
				'maxUploadSizeLabel'  => $this->format_upload_size_label( $max_upload_size ),
				'allowedImageTypes'   => array(
					'image/png',
					'image/jpeg',
					'image/webp',
					'image/gif',
				),
				'svgMimeType'         => 'image/svg+xml',
				'canEnableSvgUploads' => current_user_can( 'manage_options' ),
			);
		}

		/**
		 * Return performance settings for the editor.
		 *
		 * @return array<string, mixed> Performance limits and settings.
		 */
		private function get_performance_settings() {
			return array(
				'limits' => PerformanceConfig::get_limits(),
			);
		}

		/**
		 * Format the maximum upload size for display.
		 *
		 * @param int $size_bytes Size in bytes.
		 * @return string
		 */
		private function format_upload_size_label( $size_bytes ) {
			if ( $size_bytes <= 0 ) {
				return '';
			}

			if ( function_exists( 'size_format' ) ) {
				return size_format( $size_bytes );
			}

			return (string) $size_bytes . ' bytes';
		}

		/**
		 * Attach Additional CSS to the block editor preview.
		 *
		 * Blocks already render with `sp-logo-carousel-editor`, so this makes the
		 * dashboard's Additional CSS setting visible while editing block content.
		 *
		 * @return void
		 */
		private function attach_custom_editor_css() {
			$custom_css = $this->get_custom_css();
			if ( '' === $custom_css || ! wp_style_is( 'sp-logo-carousel-editor', 'enqueued' ) ) {
				return;
			}

			wp_add_inline_style( 'sp-logo-carousel-editor', $custom_css );
		}

		/**
		 * Attach Additional CSS & JS to block frontend handles.
		 *
		 * Classic shortcodes already attach these via `Frontend.php`. This mirrors
		 * that behavior for pages rendered only by blocks.
		 *
		 * @return void
		 */
		private function attach_custom_block_frontend_assets() {
			if ( $this->block_custom_assets_attached ) {
				return;
			}

			$custom_css = $this->get_custom_css();
			$custom_js  = $this->get_custom_js();

			if ( '' !== $custom_css ) {
				if ( wp_style_is( 'sp-logo-carousel-frontend-style', 'enqueued' ) ) {
					wp_add_inline_style( 'sp-logo-carousel-frontend-style', $custom_css );
				} elseif ( wp_style_is( 'sp-logo-carousel-frontend-bundle-style', 'enqueued' ) ) {
					wp_add_inline_style( 'sp-logo-carousel-frontend-bundle-style', $custom_css );
				}
			}

			if ( '' !== $custom_js && wp_script_is( 'sp-logo-carousel-frontend', 'enqueued' ) ) {
				wp_add_inline_script( 'sp-logo-carousel-frontend', $custom_js );
			}

			$this->block_custom_assets_attached = true;
		}

		/**
		 * Read Additional CSS from plugin settings.
		 *
		 * @return string
		 */
		private function get_custom_css() {
			$setting_data = get_option( '_sp_lcpro_options', array() );
			return isset( $setting_data['lcpro_custom_css'] ) ? trim( html_entity_decode( (string) $setting_data['lcpro_custom_css'] ) ) : '';
		}

		/**
		 * Read Additional JS from plugin settings.
		 *
		 * @return string
		 */
		private function get_custom_js() {
			$setting_data = get_option( '_sp_lcpro_options', array() );
			return isset( $setting_data['custom_js'] ) ? html_entity_decode( (string) $setting_data['custom_js'] ) : '';
		}
	}
}
