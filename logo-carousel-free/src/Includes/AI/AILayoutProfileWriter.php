<?php
/**
 * AI Layout Profile Writer.
 *
 * Turns the flat `layout` object the model is asked for into the profile-shaped
 * one every reader in this plugin expects.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AILayoutProfileWriter' ) ) {

	/**
	 * Fans a flat layout answer out to the branches the renderer reads.
	 *
	 * `layout` is not a flat map. It is a top-level style (`carouselStyle`,
	 * `gridStyle`) plus a `shared` object plus one
	 * object per style, and every reader — `SwiperLayoutProfileResolver`,
	 * `SwiperConfig`, `LayoutCss`, `GridLayoutCss`, `resolveLayoutProfiles()` in
	 * the editor — looks in `layout.shared` / `layout[style]` **before** the flat
	 * key. A model answer of `{"columns": …}` written flat therefore loses to
	 * whatever `layout.standard.columns` already holds, and the prompt silently
	 * does nothing.
	 *
	 * Teaching a model that shape would cost tokens on every request and invite
	 * it to write the wrong branch, so the catalog asks for flat keys and this
	 * writes all three places at once: the flat key (for the legacy fallback),
	 * `shared` (for a shared field), and the active style's object (for a profile
	 * field). A sub-object the model wrote deliberately — `layout.standard`,
	 * `layout.strip`, `layout.staggered`, `layout.zigzag` — is passed through
	 * untouched, so the Grid's per-style branches still work.
	 */
	class AILayoutProfileWriter {

		/**
		 * Layout fields that live under `shared`, per block slug.
		 *
		 * Mirrors `LAYOUT_SHARED_FIELDS` in `blocks/blocks/shared/swiper/swiperConfig.js`
		 * for the carousel, and the `shared` branch of each other block's layout
		 * defaults. A field missing here is treated as a profile field.
		 *
		 * @var array<string, string[]>
		 */
		const SHARED_FIELDS = array(
			'carousel' => array(
				'displayOrientation',
				'partialSlideView',
				'variableWidth',
				'gapBetweenLogos',
				'alignment',
				'edgeFade',
				'autoplay',
				'autoplayDelay',
				'sliderSpeed',
				'pauseOnHover',
				'adaptiveHeight',
				'infiniteLoop',
				'navigationArrows',
				'paginationDots',
				'ajaxLiveFilter',
				'ajaxSearch',
				'filterSearchOrientation',
				'filterSearchGap',
				'justifyFilterSearch',
			),
			'grid'     => array(
				'columns',
				'horizontalGap',
				'verticalGap',
				'justify',
				'alignment',
				'pagination',
				'ajaxLiveFilter',
				'ajaxSearch',
				'filterSearchOrientation',
				'filterSearchGap',
				'justifyFilterSearch',
			),
		);

		/**
		 * The style key each block stores its layout style under.
		 *
		 * @var array<string, string>
		 */
		const STYLE_KEYS = array(
			'carousel' => 'carouselStyle',
			'grid'     => 'gridStyle',
		);

		/**
		 * Style value → the layout sub-object that style reads, per block.
		 *
		 * Only the Carousel renames one ("staggered-carousel" stores under
		 * "staggered"); in the Grid the style value is the branch name.
		 *
		 * @var array<string, array<string, string>>
		 */
		const PROFILE_KEYS = array(
			'carousel' => array(
				'standard'           => 'standard',
				'staggered-carousel' => 'staggered',
				'center'             => 'center',
				'multirow'           => 'multirow',
			),
			'grid'     => array(
				'standard'  => 'standard',
				'strip'     => 'strip',
				'staggered' => 'staggered',
				'zigzag'    => 'zigzag',
			),
		);

		/**
		 * Keys that are style branches rather than layout fields.
		 *
		 * A model writing one of these writes it deliberately (the Grid's
		 * `strip` borders), so it is merged as-is and
		 * never fanned out.
		 *
		 * @var array<string, string[]>
		 */
		const CONTAINER_KEYS = array(
			'carousel' => array( 'shared', 'standard', 'staggered', 'center', 'multirow' ),
			'grid'     => array( 'shared', 'standard', 'strip', 'staggered', 'zigzag' ),
		);

		/**
		 * Fan a layout partial out to every branch its readers consult.
		 *
		 * @param array  $layout_partial Layout keys the model produced, already value-validated.
		 * @param string $slug           Block slug, without the namespace prefix.
		 * @param array  $existing_layout The block's current `layout` attribute, for the style it is already on.
		 * @return array<string, mixed> Layout partial safe to deep-merge over the stored one.
		 */
		public static function expand( array $layout_partial, $slug, array $existing_layout = array() ) {
			if ( empty( $layout_partial ) ) {
				return $layout_partial;
			}

			$containers = isset( self::CONTAINER_KEYS[ $slug ] ) ? self::CONTAINER_KEYS[ $slug ] : array( 'shared' );
			$shared_set = isset( self::SHARED_FIELDS[ $slug ] ) ? self::SHARED_FIELDS[ $slug ] : array();
			$profile    = self::active_profile_key( $layout_partial, $slug, $existing_layout );

			$expanded = $layout_partial;

			foreach ( $layout_partial as $field => $value ) {
				// Style branches and the style key itself are written by name.
				if ( in_array( $field, $containers, true ) || self::is_style_key( $field, $slug ) ) {
					continue;
				}

				if ( in_array( $field, $shared_set, true ) ) {
					$expanded['shared'] = isset( $expanded['shared'] ) && is_array( $expanded['shared'] )
						? $expanded['shared']
						: array();
					// A branch the model wrote by name is the author's own
					// intent and outranks the flat key it also sent.
					if ( ! array_key_exists( $field, $expanded['shared'] ) ) {
						$expanded['shared'][ $field ] = $value;
					}
					continue;
				}

				if ( '' === $profile ) {
					continue;
				}

				$expanded[ $profile ] = isset( $expanded[ $profile ] ) && is_array( $expanded[ $profile ] )
					? $expanded[ $profile ]
					: array();
				if ( ! array_key_exists( $field, $expanded[ $profile ] ) ) {
					$expanded[ $profile ][ $field ] = $value;
				}
			}

			return self::expand_multirow_orientation( $expanded, $slug );
		}

		/**
		 * Layout keys a model answer may legitimately carry at the top level.
		 *
		 * The catalog asks for layout flat, but only the Carousel's schema
		 * default actually mirrors every field at the top level. On the other
		 * four blocks a field like `speed` or `columns` exists only inside
		 * `shared` or a style branch, so a shape gate that checks the top-level
		 * default alone drops exactly the keys the prompt was asked to produce —
		 * "18 second ticker" arriving and going nowhere, with nothing to show the
		 * user why. This is the set the gate has to accept: the default's own
		 * keys, every shared field, and every field any style branch declares.
		 *
		 * @param string $slug           Block slug, without the namespace prefix.
		 * @param array  $layout_default The block's `layout` schema default.
		 * @return string[] Accepted top-level layout keys.
		 */
		public static function allowed_fields( $slug, array $layout_default ) {
			$allowed = array_keys( $layout_default );

			if ( isset( self::SHARED_FIELDS[ $slug ] ) ) {
				$allowed = array_merge( $allowed, self::SHARED_FIELDS[ $slug ] );
			}

			foreach ( self::CONTAINER_KEYS[ $slug ] ?? array( 'shared' ) as $container ) {
				if ( isset( $layout_default[ $container ] ) && is_array( $layout_default[ $container ] ) ) {
					$allowed = array_merge( $allowed, array_keys( $layout_default[ $container ] ) );
				}
			}

			return array_values( array_unique( $allowed ) );
		}

		/**
		 * Whether a key is the block's style selector.
		 *
		 * @param string $field Layout field name.
		 * @param string $slug  Block slug.
		 * @return bool
		 */
		private static function is_style_key( $field, $slug ) {
			return isset( self::STYLE_KEYS[ $slug ] ) && self::STYLE_KEYS[ $slug ] === $field;
		}

		/**
		 * The layout sub-object the block is on after this answer is applied.
		 *
		 * The style the model just chose wins; otherwise the one already stored;
		 * otherwise the block's first branch. A block with no style picker
		 * (Table) has no profile at all, so its fields stay flat + shared.
		 *
		 * @param array  $layout_partial  Layout keys the model produced.
		 * @param string $slug            Block slug.
		 * @param array  $existing_layout The block's current layout attribute.
		 * @return string Profile key, or empty string when the block has none.
		 */
		public static function active_profile_key( array $layout_partial, $slug, array $existing_layout = array() ) {
			if ( ! isset( self::STYLE_KEYS[ $slug ], self::PROFILE_KEYS[ $slug ] ) ) {
				return '';
			}

			$style_key = self::STYLE_KEYS[ $slug ];
			$profiles  = self::PROFILE_KEYS[ $slug ];

			$style = '';
			if ( isset( $layout_partial[ $style_key ] ) && is_string( $layout_partial[ $style_key ] ) ) {
				$style = $layout_partial[ $style_key ];
			} elseif ( isset( $existing_layout[ $style_key ] ) && is_string( $existing_layout[ $style_key ] ) ) {
				$style = $existing_layout[ $style_key ];
			}

			if ( isset( $profiles[ $style ] ) ) {
				return $profiles[ $style ];
			}

			$first = reset( $profiles );

			return false !== $first ? $first : '';
		}

		/**
		 * Mirror multirow's rows/columns into the orientation branch it reads.
		 *
		 * `layout.multirow` is the one profile that splits again, by orientation
		 * (`horizontal` / `vertical`), and the resolver reads
		 * `multirow.<orientation>.rows` first. Without this a "3 rows" answer on a
		 * multi-row carousel writes `multirow.rows`, which nothing reads.
		 *
		 * @param array  $expanded Layout partial after the shared/profile fan-out.
		 * @param string $slug     Block slug.
		 * @return array<string, mixed>
		 */
		private static function expand_multirow_orientation( array $expanded, $slug ) {
			if ( 'carousel' !== $slug || empty( $expanded['multirow'] ) || ! is_array( $expanded['multirow'] ) ) {
				return $expanded;
			}

			$orientation = 'vertical' === self::read_orientation( $expanded ) ? 'vertical' : 'horizontal';
			$branch      = isset( $expanded['multirow'][ $orientation ] ) && is_array( $expanded['multirow'][ $orientation ] )
				? $expanded['multirow'][ $orientation ]
				: array();

			foreach ( array( 'rows', 'columns' ) as $field ) {
				if ( isset( $expanded['multirow'][ $field ] ) && ! array_key_exists( $field, $branch ) ) {
					$branch[ $field ] = $expanded['multirow'][ $field ];
				}
			}

			if ( ! empty( $branch ) ) {
				$expanded['multirow'][ $orientation ] = $branch;
			}

			return $expanded;
		}

		/**
		 * Read the display orientation out of an expanded layout partial.
		 *
		 * @param array $expanded Layout partial.
		 * @return string Orientation, or empty string when unset.
		 */
		private static function read_orientation( array $expanded ) {
			if ( isset( $expanded['shared']['displayOrientation'] ) && is_string( $expanded['shared']['displayOrientation'] ) ) {
				return $expanded['shared']['displayOrientation'];
			}

			return isset( $expanded['displayOrientation'] ) && is_string( $expanded['displayOrientation'] )
				? $expanded['displayOrientation']
				: '';
		}
	}
}
