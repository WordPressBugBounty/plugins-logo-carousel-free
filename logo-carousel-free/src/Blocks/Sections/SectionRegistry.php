<?php
/**
 * Registry of the shared manifest-native sections ("Same as Carousel").
 *
 * Single source of truth for the Tier-1 section set every family block inherits
 * identically (see docs/SECTION_PATTERN.md → "Target adoption model"). Blocks
 * read this instead of hand-listing sections in three places, so a new block
 * (e.g. grid) adopts the whole set without re-declaring it.
 *
 * Tier-2 base-level chrome (Advanced Settings: section / visibility / advanced)
 * is NOT here — it is composed by BaseSchema and always-on by design.
 *
 * Each entry is a capability descriptor, because sections differ in what they
 * own. Display Elements, Tooltips, Scheduling, Motion Effects, Ajax Filter and
 * Ajax Search are Pro-only features: in
 * the free plugin they contribute their attribute schema/defaults (so
 * Pro-saved content round-trips — see MASTER_PLAN D5) and no renderer, gate or
 * runtime. The one exception is the logo card surface (background / border /
 * radius / padding), which lives on the Display Elements branch and is painted
 * by the `logoCard` CSS token. The registry carries the metadata; the wiring
 * points read the capabilities they need and ignore the rest.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections;

use ShapedPlugin\LogoCarouselFree\Blocks\Sections\AjaxFilter\AjaxFilterSchema;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\AjaxSearch\AjaxSearchSchema;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\DisplayElements\DisplayElementsSchema;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\MotionEffects\MotionEffectsSchema;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\Scheduling\SchedulingSchema;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\Tooltips\TooltipsSchema;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SectionRegistry' ) ) {
	/**
	 * Ordered, capability-tagged registry of shared manifest-native sections.
	 */
	final class SectionRegistry {
		/**
		 * The shared section set, in schema-composition order.
		 *
		 * Order mirrors the historical `CarouselSchema` compose order. The four
		 * branches use disjoint top-level keys (displayElements / tooltips /
		 * scheduling / motionEffects), so `DeepMerge` order does not change the
		 * result — order is kept only for deterministic, reviewable output.
		 *
		 * Capabilities:
		 * - `schema`    Schema class exposing `::definition()` (schema compose).
		 * - `css_token` Feature token for the CSS composer, or null when the
		 *               section emits no CSS (Tooltips / Scheduling / Motion
		 *               Effects / Ajax Filter / Ajax Search are schema-only in
		 *               the free plugin).
		 * - `always_on` Whether the CSS token is emitted unconditionally.
		 *
		 * @return array<int, array{schema:class-string, css_token:?string, always_on:bool}>
		 */
		private static function sections() {
			return array(
				array(
					'schema'    => DisplayElementsSchema::class,
					'css_token' => 'logoCard',
					'always_on' => true,
				),
				array(
					'schema'    => TooltipsSchema::class,
					'css_token' => null,
					'always_on' => false,
				),
				array(
					'schema'    => SchedulingSchema::class,
					'css_token' => null,
					'always_on' => false,
				),
				array(
					'schema'    => MotionEffectsSchema::class,
					'css_token' => null,
					'always_on' => false,
				),
				array(
					'schema'    => AjaxFilterSchema::class,
					'css_token' => null,
					'always_on' => false,
				),
				array(
					'schema'    => AjaxSearchSchema::class,
					'css_token' => null,
					'always_on' => false,
				),
			);
		}

		/**
		 * Return each shared section's attribute definition, in compose order.
		 *
		 * Consumed by `CarouselSchema` (and any future family block schema) to
		 * merge the whole shared set in one loop instead of one line per section.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function schema_definitions() {
			$definitions = array();

			foreach ( self::sections() as $section ) {
				$definitions[] = call_user_func( array( $section['schema'], 'definition' ) );
			}

			return $definitions;
		}

		/**
		 * Return the CSS feature tokens that are emitted unconditionally.
		 *
		 * These are the shared-section tokens a family block turns on for every
		 * render. Schema-only sections (tooltips / scheduling / motionEffects /
		 * ajaxFilter / ajaxSearch) are intentionally excluded.
		 *
		 * @return array<int, string>
		 */
		public static function always_on_css_tokens() {
			$tokens = array();

			foreach ( self::sections() as $section ) {
				if ( $section['always_on'] && is_string( $section['css_token'] ) ) {
					$tokens[] = $section['css_token'];
				}
			}

			return $tokens;
		}

		/**
		 * Return every shared section id (matches each schema's `::id()`).
		 *
		 * @return array<int, string>
		 */
		public static function ids() {
			$ids = array();

			foreach ( self::sections() as $section ) {
				$ids[] = call_user_func( array( $section['schema'], 'id' ) );
			}

			return $ids;
		}
	}
}
