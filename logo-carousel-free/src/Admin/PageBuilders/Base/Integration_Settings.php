<?php
/**
 * Page builder integration toggles.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Single source of the dashboard's Settings → Integrations toggles.
 *
 * The state lives in `_sp_lcpro_options['integrations'][ $key ]['is_active']`, the
 * same shape the React Integrations tab writes. A key that was never saved counts
 * as enabled, so an existing site keeps every integration until someone turns one
 * off.
 *
 * @since 4.0.0
 */
class Integration_Settings {

	/**
	 * Option that stores the plugin settings.
	 *
	 * @var string
	 */
	const OPTION = '_sp_lcpro_options';

	/**
	 * Integration keys, in dashboard order.
	 *
	 * @var string[]
	 */
	const KEYS = array( 'elementor', 'divi', 'wpbakery', 'oxygen', 'beaver', 'bricks' );

	/**
	 * Whether an integration is enabled in the dashboard.
	 *
	 * @since 4.0.0
	 *
	 * @param string $key Integration key.
	 * @return bool
	 */
	public static function is_enabled( $key ) {
		$options = get_option( self::OPTION, array() );

		return self::is_enabled_in( is_array( $options ) ? $options : array(), $key );
	}

	/**
	 * Whether an integration is enabled in a settings map (pure).
	 *
	 * @since 4.0.0
	 *
	 * @param array  $options Settings map.
	 * @param string $key     Integration key.
	 * @return bool
	 */
	public static function is_enabled_in( array $options, $key ) {
		if ( isset( $options['integrations'][ $key ]['is_active'] ) ) {
			return (bool) $options['integrations'][ $key ]['is_active'];
		}

		return true;
	}

	/**
	 * Sanitize an incoming integrations map (pure).
	 *
	 * Unknown keys are dropped; a known key missing from the payload keeps its
	 * stored value, so a partial save cannot silently flip another toggle.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $incoming Incoming map, `key => array( 'is_active' => bool )`.
	 * @param mixed $current  Stored map.
	 * @return array<string, array{is_active: bool}>
	 */
	public static function sanitize( $incoming, $current = array() ) {
		$incoming  = is_array( $incoming ) ? $incoming : array();
		$current   = is_array( $current ) ? $current : array();
		$sanitized = array();

		foreach ( self::KEYS as $key ) {
			if ( isset( $incoming[ $key ] ) && is_array( $incoming[ $key ] ) && array_key_exists( 'is_active', $incoming[ $key ] ) ) {
				$sanitized[ $key ] = array( 'is_active' => self::to_bool( $incoming[ $key ]['is_active'] ) );
				continue;
			}

			$sanitized[ $key ] = array( 'is_active' => self::is_enabled_in( array( 'integrations' => $current ), $key ) );
		}

		return $sanitized;
	}

	/**
	 * Narrow a posted value to a boolean ('0' / 'false' / '' are off).
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function to_bool( $value ) {
		if ( is_string( $value ) ) {
			return '' !== $value && '0' !== $value && 'false' !== strtolower( $value );
		}

		return (bool) $value;
	}
}
