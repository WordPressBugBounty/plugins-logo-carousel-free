<?php
/**
 * Module gate for render-time attributes.
 *
 * Glassmorphism is a Pro-only module, so in the free plugin it is ALWAYS
 * treated as off (see ProFeatures), whatever the dashboard module store says.
 * The logo card is the one surface Free paints that can carry a glass
 * background; a block saved in Pro keeps it, and downgrading it here — on a
 * render-time copy, never on the saved post — renders it as a solid fill
 * while the attributes still round-trip. (Scheduling and Motion Effects need
 * no gate: nothing in the free plugin reads them.)
 *
 * 1:1 mirror of `blocks/blocks/shared/utils/moduleGate.js`.
 *
 * @package ShapedPlugin\LogoCarouselFree\Blocks\Rendering
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering;

use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\ModulesVisibility;
use ShapedPlugin\LogoCarouselFree\Includes\ProFeatures;

defined( 'ABSPATH' ) || exit;

/**
 * ModuleGate class.
 */
final class ModuleGate {

	/**
	 * Memoized module flags, keyed by slug (one option read per request).
	 *
	 * @var array<string, bool>
	 */
	private static $flags = array();

	/**
	 * Optional test overrides (slug => bool). Null means use ModulesVisibility.
	 *
	 * @var array<string, bool>|null
	 */
	private static $test_flags = null;

	/**
	 * Neutralize the attribute branches owned by a switched-off module.
	 *
	 * Only rewrites keys the block already declares. Saved posts are never
	 * mutated — re-enabling a module restores the previous behaviour.
	 *
	 * @param array $attributes Attributes merged over the schema defaults.
	 * @return array Gated copy of the attributes (or the input when unchanged).
	 */
	public static function apply( array $attributes ): array {
		if ( self::is_active( 'glassmorphism' ) || ! self::contains_glassmorphism( $attributes ) ) {
			return $attributes;
		}

		$attributes['displayElements'] = self::strip_glass_from_background_owner( $attributes['displayElements'], 'cardBackground' );

		return $attributes;
	}

	/**
	 * Whether a dashboard module is switched on (memoized per request).
	 *
	 * Pro-only modules (ProFeatures::PRO_MODULES) are always off — neither the
	 * dashboard store nor test overrides can switch them on. Other unknown /
	 * missing flags fail open (treated as on), matching `isBlockModuleActive()`
	 * and ModulesVisibility::is_enabled().
	 *
	 * @param string $module_name Module slug from the dashboard registry.
	 * @return bool
	 */
	public static function is_active( string $module_name ): bool {
		if ( ProFeatures::is_pro_module( $module_name ) ) {
			return false;
		}

		if ( is_array( self::$test_flags ) ) {
			return array_key_exists( $module_name, self::$test_flags )
				? (bool) self::$test_flags[ $module_name ]
				: true;
		}

		if ( isset( self::$flags[ $module_name ] ) ) {
			return self::$flags[ $module_name ];
		}

		// Unit-test harnesses may not stub get_option — fail open.
		if ( ! function_exists( 'get_option' ) ) {
			self::$flags[ $module_name ] = true;
			return true;
		}

		self::$flags[ $module_name ] = ( new ModulesVisibility() )->is_enabled( $module_name );

		return self::$flags[ $module_name ];
	}

	/**
	 * Drop the memoized flags (module save, and test harnesses that toggle them).
	 *
	 * @return void
	 */
	public static function flush_cache() {
		self::$flags = array();
	}

	/**
	 * Override module flags for unit tests. Pass null to restore production reads.
	 *
	 * @param array<string, bool>|null $flags Slug => enabled map, or null.
	 * @return void
	 */
	public static function set_test_flags( $flags ) {
		self::$test_flags = is_array( $flags ) ? $flags : null;
		self::flush_cache();
	}

	/**
	 * Whether the logo card background uses glassmorphism.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return bool
	 */
	private static function contains_glassmorphism( array $attributes ) {
		return isset( $attributes['displayElements'] )
			&& is_array( $attributes['displayElements'] )
			&& self::branch_has_glass( $attributes['displayElements'], 'cardBackground' );
	}

	/**
	 * @param array<string, mixed> $owner Parent object.
	 * @param string               $key Background key.
	 * @return bool
	 */
	private static function branch_has_glass( array $owner, $key ) {
		if ( ! isset( $owner[ $key ] ) || ! is_array( $owner[ $key ] ) ) {
			return false;
		}

		$background = $owner[ $key ];

		if ( isset( $background['style'] ) ) {
			return 'glassmorphism' === $background['style'];
		}

		foreach ( array( 'normal', 'hover' ) as $state ) {
			if (
				isset( $background[ $state ]['style'] )
				&& 'glassmorphism' === $background[ $state ]['style']
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Strip glass from a background key that may be a single slice or normal/hover.
	 *
	 * @param array<string, mixed> $owner Parent object.
	 * @param string               $key Background key.
	 * @return array<string, mixed>
	 */
	private static function strip_glass_from_background_owner( array $owner, $key ) {
		if ( ! isset( $owner[ $key ] ) || ! is_array( $owner[ $key ] ) ) {
			return $owner;
		}

		$background = $owner[ $key ];

		if ( isset( $background['style'] ) ) {
			$owner[ $key ] = self::downgrade_glass_slice( $background );
			return $owner;
		}

		foreach ( array( 'normal', 'hover' ) as $state ) {
			if ( isset( $background[ $state ] ) && is_array( $background[ $state ] ) ) {
				$background[ $state ] = self::downgrade_glass_slice( $background[ $state ] );
			}
		}

		$owner[ $key ] = $background;
		return $owner;
	}

	/**
	 * Coerce a glassmorphism slice to solid, preferring glassBase as the fill.
	 *
	 * @param array<string, mixed> $slice Background slice.
	 * @return array<string, mixed>
	 */
	private static function downgrade_glass_slice( array $slice ) {
		if ( ! isset( $slice['style'] ) || 'glassmorphism' !== $slice['style'] ) {
			return $slice;
		}

		$slice['style'] = 'solid';
		if ( ( ! isset( $slice['solid'] ) || '' === $slice['solid'] ) && ! empty( $slice['glassBase'] ) ) {
			$slice['solid'] = $slice['glassBase'];
		}

		return $slice;
	}
}
