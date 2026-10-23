<?php
/**
 * AI Block Router.
 *
 * Picks the block an attribute-generation request should be built on when the
 * caller only supplied a default.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\BlocksVisibility;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AIBlockRouter' ) ) {

	/**
	 * Resolves the block a prompt is describing.
	 *
	 * A Ready Pattern carries its own block: whatever the editor asked for, an
	 * inserted pattern lands as the block its markup was authored on. Attribute
	 * generation has no such step — left alone it builds for the block name the
	 * request arrived with, which for a prompt typed into the editor-header
	 * launcher is always the carousel default. Everything downstream is
	 * per-block: the catalog `AICatalogBuilder` hands the model, the enums
	 * `AIValueValidator` accepts, the schema `AISchemaValidator` merges onto,
	 * the branches `AILayoutProfileWriter` fans out to. So a "bento wall of
	 * logos" prompt would be answered with the carousel catalog, and every
	 * grid-only value the model did produce (`gridStyle`, `strip`) dropped as
	 * unknown. The free plugin routes only between Carousel and Grid.
	 *
	 * This resolves the block from the prompt itself, on the vocabulary
	 * `AIPatternMatcher` already maintains, and only when the words are decisive:
	 * "carousel", "slider" and "logos" describe the container rather than
	 * choosing one, and a prompt that names two blocks equally names neither.
	 */
	class AIBlockRouter {

		/**
		 * Resolve the block a prompt is asking for.
		 *
		 * @param string $prompt          Natural language prompt.
		 * @param string $requested_block Block the request arrived with, prefixed or bare.
		 * @return string Fully-qualified block name — the requested one unless the
		 *                prompt decisively names another.
		 */
		public static function resolve( $prompt, $requested_block ) {
			$candidates = self::candidates();
			$terms      = AIPatternMatcher::prompt_terms( $prompt );
			$floor      = AIPatternMatcher::generic_term_weight();

			$best       = '';
			$best_score = 0.0;
			$tied       = false;

			foreach ( $candidates as $slug ) {
				// A block scores only through its own slug: the alias rows in
				// `AIPatternMatcher` are keyed by it, so "ticker", "comparison" or
				// "bento" already resolve to one without a second keyword table to
				// keep in step with them.
				$score = isset( $terms[ $slug ] ) ? (float) $terms[ $slug ] : 0.0;
				if ( $score <= $floor ) {
					continue;
				}

				if ( $score > $best_score ) {
					$best       = $slug;
					$best_score = $score;
					$tied       = false;
					continue;
				}

				if ( $score === $best_score ) {
					$tied = true;
				}
			}

			if ( '' === $best || $tied ) {
				return $requested_block;
			}

			return AICatalogBuilder::BLOCK_PREFIX . $best;
		}

		/**
		 * Blocks attribute generation may be routed to.
		 *
		 * A block qualifies when it has an attribute schema to generate against
		 * (`AICatalogBuilder::SCHEMA_MAP`) and is one this site will actually
		 * insert (`BlocksVisibility`) — routing to a block switched off in the
		 * dashboard would hand the editor a block type it has unregistered
		 * client-side.
		 *
		 * @return string[] Block slugs, without the namespace prefix.
		 */
		public static function candidates() {
			$blocks = new BlocksVisibility();
			$slugs  = array();

			foreach ( array_keys( AICatalogBuilder::SCHEMA_MAP ) as $slug ) {
				if ( ! $blocks->is_enabled( $slug ) ) {
					continue;
				}

				$slugs[] = $slug;
			}

			return $slugs;
		}
	}
}
