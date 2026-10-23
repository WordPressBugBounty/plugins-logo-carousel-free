<?php
/**
 * Shared average-rating star model.
 *
 * ONE source of truth for "how does a 0–5 rating become five star icons".
 * LogoInfoPopupRenderer (Logo Details modal) paints stars from it — in Pro the
 * Display Elements card and the tooltip paint from the same model, so the
 * same logo paints identically wherever you look at it.
 * A JS mirror (`blocks/blocks/shared/utils/rating.js`) keeps the editor
 * previews in the same lock-step.
 *
 * The ICONS follow WordPress core's `wp_star_rating()`:
 *
 *   1. read the value (comma decimals tolerated, as core does);
 *   2. snap it to the 0..5 scale in 0.5 increments — core reaches this with
 *      `round( $rating / 10, 0 ) / 2` on a percentage, which is exactly
 *      `round( $rating * 2 ) / 2` on a 0–5 rating;
 *   3. `full = floor( $rating )`, `half = ceil( $rating - $full )`,
 *      `empty = 5 - $full - $half`.
 *
 * Snapping BEFORE floor/ceil is what makes 4.3 paint four-and-a-half stars
 * (core's own behaviour) instead of four, and 4.8 paint five instead of
 * four-and-a-half.
 *
 * The VALUE is never snapped. A rating the user typed is their data: 4.3 is
 * stored as 4.3, printed as "4.3" and read back into the edit field as 4.3 —
 * only the five icons round it, exactly as WordPress does on a plugin card.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Support;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RatingStars' ) ) {
	/**
	 * Resolve a raw rating into the five star states WordPress would paint.
	 */
	class RatingStars {
		/**
		 * Highest rating, i.e. the number of star icons.
		 *
		 * @var int
		 */
		const MAX = 5;

		/**
		 * Smallest step WordPress paints — half a star.
		 *
		 * @var float
		 */
		const STEP = 0.5;

		/**
		 * Read a raw rating, keeping the value the user actually gave.
		 *
		 * Clamped to 0..MAX and rounded to one decimal — nothing more. The
		 * half-step snap belongs to `states()`, which paints icons; it must
		 * never reach the stored number.
		 *
		 * @param mixed $rating Raw rating (number or numeric string; a comma
		 *                      decimal separator is accepted, as core does).
		 * @return float|null Rating, or null when there is no rating.
		 */
		public static function parse( $rating ) {
			if ( null === $rating || is_bool( $rating ) || is_array( $rating ) ) {
				return null;
			}

			// Core's `wp_star_rating()` does the same swap so a locale that
			// writes "4,5" is not silently read as no rating at all.
			$value = is_string( $rating ) ? str_replace( ',', '.', trim( $rating ) ) : $rating;

			if ( '' === $value || ! is_numeric( $value ) ) {
				return null;
			}

			return max( 0.0, min( (float) self::MAX, round( (float) $value, 1 ) ) );
		}

		/**
		 * Snap a value to the 0..MAX scale in STEP increments.
		 *
		 * For ICONS only — see the class docblock. Callers that persist or
		 * print a rating want `parse()`.
		 *
		 * @param mixed $rating Raw rating.
		 * @return float
		 */
		public static function snap( $rating ) {
			$value = self::parse( $rating );
			$value = null === $value ? 0.0 : $value;

			return round( $value / self::STEP ) * self::STEP;
		}

		/**
		 * Resolve everything a renderer needs to paint one rating.
		 *
		 * Returns null when there is nothing to paint — no rating at all, or a
		 * rating that snaps to zero — so every caller shares one "is there a
		 * rating here?" answer instead of testing the raw value itself.
		 *
		 * @param mixed $rating Raw rating.
		 * The number fields all carry the rating AS GIVEN; only `states` is
		 * rounded to half stars.
		 *
		 * @return array<string, mixed>|null {
		 *     @type float               $value   Rating as given (e.g. 4.3).
		 *     @type string              $display Machine value for `data-rating` — always a
		 *                                        dot decimal, no trailing ".0".
		 *     @type string              $text    Same number for human eyes, through the
		 *                                        site locale (e.g. "4,3" in de_DE).
		 *     @type array<int, string>  $states  Five states: full|half|empty.
		 *     @type string              $label   Translated accessible label.
		 * }
		 */
		public static function resolve( $rating ) {
			$value = self::parse( $rating );

			if ( null === $value || $value <= 0 ) {
				return null;
			}

			$text = self::localized( $value );

			return array(
				'value'   => $value,
				'display' => self::display( $value ),
				'text'    => $text,
				'states'  => self::states( $value ),
				/* translators: %s: numeric rating value (e.g. 4.5). */
				'label'   => sprintf( __( 'Rating: %s out of 5', 'logo-carousel-free' ), $text ),
			);
		}

		/**
		 * The five star states, in paint order.
		 *
		 * This is the ONLY place a rating is rounded: five icons cannot show
		 * 4.3, so they show what WordPress would show for 4.3.
		 *
		 * @param mixed $rating Raw rating.
		 * @return array<int, string> Five entries of full|half|empty.
		 */
		public static function states( $rating ) {
			$value = self::snap( $rating );

			// wp_star_rating(): full stars are whole units, the leftover half
			// unit (if any) becomes one half star, the rest stay empty.
			$full  = (int) floor( $value );
			$half  = (int) ceil( $value - $full );
			$empty = self::MAX - $full - $half;

			return array_merge(
				array_fill( 0, $full, 'full' ),
				array_fill( 0, $half, 'half' ),
				array_fill( 0, max( 0, $empty ), 'empty' )
			);
		}

		/**
		 * Machine value for `data-rating` — dot decimal, no trailing ".0".
		 *
		 * Kept locale-free on purpose: scripts, styles and tests read this
		 * attribute, so "4,5" would break them. `localized()` is the one for
		 * human eyes.
		 *
		 * @param mixed $rating Raw rating.
		 * @return string
		 */
		public static function display( $rating ) {
			$value = self::parse( $rating );
			$value = null === $value ? 0.0 : $value;

			return number_format( $value, self::decimals( $value ), '.', '' );
		}

		/**
		 * The same number for human eyes, through the site locale.
		 *
		 * @param mixed $rating Raw rating.
		 * @return string
		 */
		public static function localized( $rating ) {
			$value = self::parse( $rating );
			$value = null === $value ? 0.0 : $value;

			if ( function_exists( '\\number_format_i18n' ) ) {
				return (string) \number_format_i18n( $value, self::decimals( $value ) );
			}

			return self::display( $value );
		}

		/**
		 * Decimal places: a whole rating prints bare, a fractional one keeps
		 * its single digit.
		 *
		 * @param float $value Rating.
		 * @return int
		 */
		private static function decimals( $value ) {
			return abs( (float) $value - round( (float) $value ) ) < 0.01 ? 0 : 1;
		}
	}
}
