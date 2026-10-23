<?php
/**
 * Contract for on/off visibility stores (blocks, modules).
 *
 * Two real implementers (BlocksVisibility now, ModulesVisibility in the
 * modules phase) — reuse by type, not by name.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/includes/dashboard
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\Dashboard;

defined( 'ABSPATH' ) || exit;

if ( ! interface_exists( 'ShapedPlugin\LogoCarouselFree\Includes\Dashboard\VisibilityStoreInterface' ) ) {

	/**
	 * Read/write a `slug => bool` visibility map backed by a WordPress option.
	 */
	interface VisibilityStoreInterface {

		/**
		 * The full resolved map (defaults merged with saved values).
		 *
		 * @return array<string, bool>
		 */
		public function all();

		/**
		 * Whether a given slug is enabled.
		 *
		 * @param string $slug Item slug.
		 * @return bool
		 */
		public function is_enabled( $slug );

		/**
		 * Persist a (partial) map after sanitising it.
		 *
		 * @param array<string, mixed> $map Incoming slug => bool-ish map.
		 * @return void
		 */
		public function set( array $map );
	}
}
