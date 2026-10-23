<?php
/**
 * AI Schema Validator.
 *
 * Sanitizes and merges AI-generated block attributes against the PHP attribute
 * schemas.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Util\DeepMerge;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AISchemaValidator' ) ) {

	/**
	 * Turns a raw model response into attributes `setAttributes()` can take.
	 */
	class AISchemaValidator {

		/**
		 * Attribute branches owned by Pro-only features (Tooltips, Scheduling,
		 * Motion Effects, Ajax Filter, Ajax Search, Grid Pagination). They stay
		 * in the free schema so Pro-saved content round-trips, but the AI
		 * builder never writes them: a refine keeps whatever the block already
		 * stored and drops anything the model sent.
		 *
		 * @var string[]
		 */
		const PRO_ONLY_KEYS = array( 'tooltips', 'scheduling', 'motionEffects', 'ajaxFilter', 'ajaxSearch', 'gridPagination' );

		/**
		 * `layout` keys owned by a Pro-only feature — the Ajax Filter / Ajax
		 * Search / Grid Pagination toggles, and the settings and profile branches
		 * of the Pro carousel styles (Staggered, Multi Row) and grid styles (Grid
		 * Strip, Staggered, Zigzag). Dropped from a model response — flat and on
		 * the shared branch — the same way as `PRO_ONLY_KEYS`.
		 *
		 * @var string[]
		 */
		const PRO_ONLY_LAYOUT_KEYS = array( 'ajaxLiveFilter', 'ajaxSearch', 'pagination', 'filterSearchOrientation', 'filterSearchGap', 'justifyFilterSearch', 'staggeredAnimation', 'randomLogoAnimation', 'easing', 'staggered', 'multirow', 'strip', 'zigzag' );

		/**
		 * The `displayElements` fields the free plugin renders: the logo card.
		 * The rest of the branch (text, rating, Read More) is Pro, and is dropped
		 * from a model response the same way as `PRO_ONLY_KEYS`.
		 *
		 * @var string[]
		 */
		const CARD_KEYS = array( 'cardBackground', 'border', 'borderWidth', 'borderRadius', 'innerPadding', 'alignment' );

		/**
		 * Sanitize and merge raw AI-generated attributes.
		 *
		 * Layered defaults -> existing attributes -> the AI's output, in that
		 * order, so a refinement request only overwrites what the model actually
		 * decided to change. The catalog never mentions `logoSource`, `advanced`,
		 * `scheduling` or `visibility` — a merge onto bare schema defaults would
		 * silently reset every one of those on every refine, not just the setting
		 * the prompt asked for.
		 *
		 * Logos are not this layer's concern: a block left with none is filled
		 * by `AIPatternLogos` afterwards, from the Ready Patterns.
		 *
		 * @param array  $ai_attributes       Raw parsed JSON attributes from the model.
		 * @param string $block_name          Target block slug, with or without the prefix.
		 * @param array  $existing_attributes Block's current attributes when refining an existing block.
		 * @return array<string, mixed> Sanitized attributes ready for `setAttributes()`.
		 */
		public static function sanitize_and_merge( array $ai_attributes, $block_name = 'sp-logo-carousel/carousel', array $existing_attributes = array() ) {
			$slug     = AICatalogBuilder::slug( $block_name );
			$defaults = AICatalogBuilder::schema_defaults( $slug );

			$valid_keys = array_keys( $defaults );

			// Layer the block's current attributes over the schema defaults first,
			// so anything the AI doesn't mention keeps what the block already had.
			$existing = array_intersect_key( $existing_attributes, array_flip( $valid_keys ) );
			$base     = DeepMerge::recursive( $defaults, $existing );

			// Filter AI keys to only valid schema keys, drop or clamp every leaf
			// the block cannot actually use, then merge what survives on top of
			// that base. A rejected leaf leaves the merge alone, so the block
			// keeps its schema default (or, on a refine, the value it already had).
			$filtered = array_intersect_key( $ai_attributes, array_flip( $valid_keys ) );
			$filtered = array_diff_key( $filtered, array_flip( self::PRO_ONLY_KEYS ) );
			if ( isset( $filtered['layout'] ) && is_array( $filtered['layout'] ) ) {
				$filtered['layout'] = array_diff_key( $filtered['layout'], array_flip( self::PRO_ONLY_LAYOUT_KEYS ) );
				if ( isset( $filtered['layout']['shared'] ) && is_array( $filtered['layout']['shared'] ) ) {
					$filtered['layout']['shared'] = array_diff_key( $filtered['layout']['shared'], array_flip( self::PRO_ONLY_LAYOUT_KEYS ) );
				}
			}
			if ( isset( $filtered['displayElements'] ) && is_array( $filtered['displayElements'] ) ) {
				$filtered['displayElements'] = array_intersect_key( $filtered['displayElements'], array_flip( self::CARD_KEYS ) );
			}
			$filtered = AIValueValidator::validate( $filtered, $slug, $existing_attributes );

			return DeepMerge::recursive( $base, $filtered );
		}
	}
}
