<?php
/**
 * Block visibility store.
 *
 * Owns the `sp_lcpro_blocks_visibility` option: which registrable blocks the
 * dashboard has enabled. Read by BlockTypesController (skip registration when
 * off) and by AssetManager (editor unregisters off blocks). Pure resolve/
 * sanitize logic is separated from WordPress I/O for unit testing.
 *
 * The free plugin registers only Carousel and Grid; the Pro-only blocks
 * (ProFeatures::PRO_BLOCKS) are never stored here and always read as off.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/includes/dashboard
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\Dashboard;

use ShapedPlugin\LogoCarouselFree\Includes\ProFeatures;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\Dashboard\BlocksVisibility' ) ) {

	/**
	 * Enabled/disabled state for the registrable blocks.
	 */
	class BlocksVisibility implements VisibilityStoreInterface {

		/**
		 * Option name.
		 *
		 * @var string
		 */
		const OPTION = 'sp_lcpro_blocks_visibility';

		/**
		 * Registrable block slugs and their default state (all on).
		 *
		 * Coming-soon blocks are display-only in the dashboard and are NOT
		 * listed here — they have no server registration to gate.
		 *
		 * @var array<string, bool>
		 */
		const DEFAULTS = array(
			'carousel' => true,
			'grid'     => true,
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
		 * Whether a block slug is enabled. Pro-only blocks are always off;
		 * other unknown slugs default to visible.
		 *
		 * @param string $slug Block slug.
		 * @return bool
		 */
		public function is_enabled( $slug ) {
			if ( ProFeatures::is_pro_block( $slug ) ) {
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
