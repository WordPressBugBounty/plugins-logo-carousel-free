<?php
/**
 * Loads the plugin's translations.
 *
 * The plugin ships its own catalogs in `languages/`: a `.mo` (plus the faster
 * `.l10n.php` on WordPress 6.5+) for PHP strings, and one JSON file per
 * script for JavaScript strings. WordPress looks script translations up by
 * the script's path, so each JSON file is named after the md5 of the bundle
 * path it belongs to.
 *
 * A language pack from translate.wordpress.org always wins. The bundled
 * catalog only fills strings the pack does not translate yet, so a partial
 * or outdated pack never turns parts of the UI back into English.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/includes
 */

namespace ShapedPlugin\LogoCarouselFree\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\I18n' ) ) {
	/**
	 * Text domain and script translation loader.
	 */
	class I18n {
		/**
		 * Text domain.
		 *
		 * @var string
		 */
		const DOMAIN = 'logo-carousel-free';

		/**
		 * Hook the loaders.
		 *
		 * @return void
		 */
		public static function register_hooks() {
			add_action( 'init', array( __CLASS__, 'load_textdomain' ), 0 );
			// switch_to_locale() reloads only the language pack; load both again.
			add_action( 'change_locale', array( __CLASS__, 'load_textdomain' ) );
			add_filter( 'pre_load_script_translations', array( __CLASS__, 'load_script_translations' ), 10, 4 );
		}

		/**
		 * Absolute path of the bundled `languages/` directory.
		 *
		 * @return string
		 */
		public static function languages_dir() {
			return dirname( SP_LOGO_CAROUSEL_FREE_FILE ) . '/languages';
		}

		/**
		 * Load the language pack, then the bundled catalog.
		 *
		 * WordPress resolves a string from the first catalog loaded for a
		 * domain, so the pack keeps priority and the bundled file only covers
		 * what the pack lacks. Missing files are skipped silently.
		 *
		 * @param string $locale Optional locale; the `change_locale` action passes it.
		 * @return void
		 */
		public static function load_textdomain( $locale = '' ) {
			$locale = is_string( $locale ) && '' !== $locale ? $locale : determine_locale();
			/** This filter is documented in wp-includes/l10n.php */
			$locale = apply_filters( 'plugin_locale', $locale, self::DOMAIN ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
			$mofile = self::DOMAIN . '-' . $locale . '.mo';

			// Drop anything loaded just in time, so the order below is the one used.
			unload_textdomain( self::DOMAIN, true );

			load_textdomain( self::DOMAIN, WP_LANG_DIR . '/plugins/' . $mofile, $locale );
			load_textdomain( self::DOMAIN, self::languages_dir() . '/' . $mofile, $locale );
		}

		/**
		 * Resolve a script's JSON translations for this plugin.
		 *
		 * Scripts register translations without a custom path, so WordPress
		 * asks for the language pack file in `wp-content/languages/plugins`.
		 * This adds the bundled file of the same name: used alone when there
		 * is no pack, merged under the pack when there is one.
		 *
		 * @param string|false|null $translations JSON already resolved by another filter, or null.
		 * @param string|false      $file         Language pack JSON path WordPress is about to read.
		 * @param string            $handle       Script handle.
		 * @param string            $domain       Text domain.
		 * @return string|false|null JSON string, or the untouched value to let core continue.
		 */
		public static function load_script_translations( $translations, $file, $handle, $domain ) {
			if ( null !== $translations || self::DOMAIN !== $domain || ! is_string( $file ) || '' === $file ) {
				return $translations;
			}

			$bundled_file = self::languages_dir() . '/' . basename( $file );
			if ( $bundled_file === $file || ! is_readable( $bundled_file ) ) {
				return $translations;
			}

			$bundled = self::read_json( $bundled_file );
			if ( null === $bundled ) {
				return $translations;
			}

			/** This filter is documented in wp-includes/l10n.php */
			$pack_file = apply_filters( 'load_script_translation_file', $file, $handle, $domain ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
			$pack      = is_string( $pack_file ) && is_readable( $pack_file ) ? self::read_json( $pack_file ) : null;

			if ( null === $pack ) {
				$merged = $bundled;
			} else {
				$merged = $pack;
				foreach ( $bundled['locale_data'] as $key => $messages ) {
					if ( ! is_array( $messages ) ) {
						continue;
					}
					$pack_messages                 = isset( $pack['locale_data'][ $key ] ) && is_array( $pack['locale_data'][ $key ] ) ? $pack['locale_data'][ $key ] : array();
					$merged['locale_data'][ $key ] = $pack_messages + $messages;
				}
			}

			$json = wp_json_encode( $merged );

			return false === $json ? $translations : $json;
		}

		/**
		 * Decode a Jed JSON translation file.
		 *
		 * @param string $file Absolute path.
		 * @return array|null Decoded data, or null when unreadable or not Jed JSON.
		 */
		private static function read_json( $file ) {
			$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
			if ( false === $contents ) {
				return null;
			}

			$data = json_decode( $contents, true );

			return is_array( $data ) && isset( $data['locale_data'] ) && is_array( $data['locale_data'] ) ? $data : null;
		}
	}
}
