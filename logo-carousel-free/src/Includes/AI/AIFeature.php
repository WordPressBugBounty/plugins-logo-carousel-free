<?php
/**
 * AI Logo Showcase Builder availability.
 *
 * One gate for the whole feature: the "Build with AI" dashboard module, plus
 * whatever WordPress itself says about AI support. Every AI surface — the
 * editor header launcher, the first-run source screen, the block toolbar's
 * Refine entry, the AI Configuration tab, the generation endpoint, and the
 * transport behind it — asks this class rather than re-deriving the answer, so
 * switching the module off cannot leave one screen still offering a feature the
 * server no longer serves.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\ModulesVisibility;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AIFeature' ) ) {

	/**
	 * Feature gate for the AI builder.
	 */
	final class AIFeature {

		/**
		 * Dashboard module slug that owns the AI builder.
		 */
		const MODULE = 'ai-builder';

		/**
		 * Whether the dashboard module is switched on.
		 *
		 * @return bool
		 */
		public static function is_module_enabled() {
			/**
			 * Filter whether the AI builder module is enabled.
			 *
			 * Runs before the stored module state so a site can switch the
			 * feature off in code without touching the dashboard option.
			 *
			 * @param bool $enabled Default enabled state.
			 */
			$enabled = apply_filters( 'lcpro_ai_builder_enabled', true );

			if ( ! $enabled ) {
				return false;
			}

			$modules = new ModulesVisibility();

			return (bool) $modules->is_enabled( self::MODULE );
		}

		/**
		 * Whether AI may be offered and served at all.
		 *
		 * @return bool True when the module is on and the site allows AI.
		 */
		public static function is_available() {
			return self::is_module_enabled() && ConnectorBridge::site_allows_ai();
		}

		/**
		 * Error describing why AI is unavailable, for callers that must say so.
		 *
		 * The two reasons carry different codes and statuses: a module the site
		 * owner switched off is a settings answer the caller can act on, while a
		 * site-wide AI block is not this plugin's to lift. Only meaningful after
		 * `is_available()` has returned false.
		 *
		 * @return \WP_Error
		 */
		public static function unavailable_error() {
			if ( ! self::is_module_enabled() ) {
				return new \WP_Error(
					'lcpro_ai_module_disabled',
					__( 'The Build with AI module is turned off.', 'logo-carousel-free' ),
					array( 'status' => 403 )
				);
			}

			return new \WP_Error(
				'lcpro_ai_disabled',
				__( 'AI features are disabled on this site.', 'logo-carousel-free' ),
				array( 'status' => 503 )
			);
		}
	}
}
