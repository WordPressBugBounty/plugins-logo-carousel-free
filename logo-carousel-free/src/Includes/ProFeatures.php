<?php
/**
 * Free/Pro feature contract.
 *
 * Single place that names what the free plugin leaves to Pro. The editor
 * mirror lives in `blocks/common/proFeatures.js` and must list the same
 * slugs.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/includes
 */

namespace ShapedPlugin\LogoCarouselFree\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\ProFeatures' ) ) {
	/**
	 * Names the Pro-only modules, blocks and upgrade links.
	 */
	final class ProFeatures {
		/**
		 * Dashboard modules that exist only in Pro.
		 *
		 * @var string[]
		 */
		const PRO_MODULES = array( 'scheduling', 'analytics', 'motion-effects', 'glassmorphism' );

		/**
		 * Modules a Ready Pattern can declare that the free plugin cannot honour.
		 *
		 * Every Pro module, plus Popup Details: the free gate turns logo click
		 * actions (URL, popup) off, so a popup pattern would insert without its
		 * popup. Kept apart from PRO_MODULES, which also drives the dashboard
		 * and the module gate.
		 *
		 * @var string[]
		 */
		const PRO_PATTERN_MODULES = array( 'scheduling', 'analytics', 'motion-effects', 'glassmorphism', 'popup-details' );

		/**
		 * Block slugs that exist only in Pro.
		 *
		 * @var string[]
		 */
		const PRO_BLOCKS = array( 'list', 'marquee', 'table' );

		/**
		 * Block slugs the free plugin registers.
		 *
		 * @var string[]
		 */
		const FREE_BLOCKS = array( 'carousel', 'grid' );

		/**
		 * Pro release the ported block code matches.
		 *
		 * Ready Patterns and the AI matcher gate on `minPluginVersion`, which
		 * the shared catalog writes against Pro's version line. Free's own
		 * version (4.x) runs on a different line, so compatibility checks use
		 * this value instead. Bump it whenever block code is re-synced from Pro.
		 *
		 * @var string
		 */
		const PRO_COMPAT_VERSION = '4.1.5';

		/**
		 * Pricing page.
		 *
		 * @var string
		 */
		const UPGRADE_URL = 'https://logocarousel.com/pricing/?ref=1';

		/**
		 * Whether a module slug is Pro-only.
		 *
		 * @param string $slug Module slug.
		 * @return bool
		 */
		public static function is_pro_module( $slug ) {
			return in_array( (string) $slug, self::PRO_MODULES, true );
		}

		/**
		 * Whether a pattern's declared module needs Pro to work as designed.
		 *
		 * @param string $slug Module slug.
		 * @return bool
		 */
		public static function is_pro_pattern_module( $slug ) {
			return in_array( (string) $slug, self::PRO_PATTERN_MODULES, true );
		}

		/**
		 * Whether a block slug is Pro-only.
		 *
		 * @param string $slug Block slug.
		 * @return bool
		 */
		public static function is_pro_block( $slug ) {
			return in_array( (string) $slug, self::PRO_BLOCKS, true );
		}
	}
}
