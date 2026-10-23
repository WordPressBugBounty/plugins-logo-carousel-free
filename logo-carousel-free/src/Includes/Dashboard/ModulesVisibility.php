<?php
/**
 * Module visibility store.
 *
 * Owns the `sp_lcpro_modules_visibility` option: which feature modules the
 * dashboard has enabled. Second implementer of VisibilityStoreInterface (with
 * BlocksVisibility) — reuse by type. Pure resolve/sanitize is split from
 * WordPress I/O for unit testing.
 *
 * Pro-only modules (ProFeatures::PRO_MODULES) are not stored here: the free
 * plugin does not ship them, so they always read as disabled.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/includes/dashboard
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\Dashboard;

use ShapedPlugin\LogoCarouselFree\Includes\ProFeatures;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\Dashboard\ModulesVisibility' ) ) {

	/**
	 * Enabled/disabled state for the feature modules.
	 */
	class ModulesVisibility implements VisibilityStoreInterface {

		/**
		 * Option name.
		 *
		 * @var string
		 */
		const OPTION = 'sp_lcpro_modules_visibility';

		/**
		 * Module slugs and their default state (all on).
		 *
		 * Mirrors the cards on the dashboard's Modules tab. A slug listed here is
		 * one a site owner can switch off; anything else — a feature with no card,
		 * or a display-only coming-soon entry — is not listed, and `is_enabled()`
		 * reports it on — except the Pro-only modules, which always report off.
		 *
		 * @var array<string, bool>
		 */
		const DEFAULTS = array(
			'saved-templates' => true,
			'ready-patterns'  => true,
			'ai-builder'      => true,
		);

		/**
		 * The full resolved map.
		 *
		 * @return array<string, bool>
		 */
		public function all() {
			$saved = get_option( self::OPTION, array() );

			return self::resolve( is_array( $saved ) ? $saved : array() );
		}

		/**
		 * Whether a module slug is enabled. Pro-only modules are always off;
		 * other unknown slugs default to enabled.
		 *
		 * @param string $slug Module slug.
		 * @return bool
		 */
		public function is_enabled( $slug ) {
			if ( ProFeatures::is_pro_module( $slug ) ) {
				return false;
			}

			$map = $this->all();

			return array_key_exists( $slug, $map ) ? $map[ $slug ] : true;
		}

		/**
		 * Persist a (partial) map.
		 *
		 * @param array<string, mixed> $map Incoming map.
		 * @return void
		 */
		public function set( array $map ) {
			update_option( self::OPTION, self::sanitize( $map ) );
		}

		/**
		 * Merge saved values over defaults (pure).
		 *
		 * @param array<string, mixed> $saved Saved option value.
		 * @return array<string, bool>
		 */
		public static function resolve( array $saved ) {
			$out = self::DEFAULTS;

			foreach ( self::DEFAULTS as $slug => $default ) {
				if ( array_key_exists( $slug, $saved ) ) {
					$out[ $slug ] = self::to_bool( $saved[ $slug ] );
				}
			}

			return $out;
		}

		/**
		 * Reduce arbitrary input to the known slugs as booleans (pure).
		 *
		 * @param array<string, mixed> $input Incoming map.
		 * @return array<string, bool>
		 */
		public static function sanitize( array $input ) {
			$out = array();

			foreach ( self::DEFAULTS as $slug => $default ) {
				$out[ $slug ] = array_key_exists( $slug, $input )
					? self::to_bool( $input[ $slug ] )
					: $default;
			}

			return $out;
		}

		/**
		 * Cast a mixed truthy/falsey value to bool.
		 *
		 * @param mixed $value Value.
		 * @return bool
		 */
		private static function to_bool( $value ) {
			return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}
	}
}
