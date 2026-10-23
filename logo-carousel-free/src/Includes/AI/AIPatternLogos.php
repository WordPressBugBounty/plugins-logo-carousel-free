<?php
/**
 * AI Pattern Logos.
 *
 * Borrows a Ready Pattern's demo logos for a block the AI builder configured
 * from attributes.
 *
 * @package Logo_Carousel_Free
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

use ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns\PatternRepository;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AIPatternLogos' ) ) {

	/**
	 * Fills an empty AI-built block with the logos a Ready Pattern ships.
	 *
	 * A block the AI just configured with no logos renders as an empty frame:
	 * every gap, filter and hover effect the prompt asked for is invisible until
	 * the user picks their own media. The Ready Patterns already carry a curated
	 * set of demo logos — real images, with titles, descriptions and categories —
	 * so the attribute path borrows them instead of inventing anything.
	 *
	 * When no pattern can lend logos (patterns switched off, the catalog
	 * unreachable, no pattern carrying items) the block is left exactly as the
	 * merge produced it. Nothing is ever fabricated in their place: the block's
	 * own source-selection screen is the honest empty state.
	 *
	 * The borrowed set is flagged `patternDemo`, the same as a pattern insert,
	 * so the editor shows the "replace the demo logos" notice and
	 * `AIPatternMatcher::allows_pattern_insert()` still treats the block as
	 * unconfigured.
	 */
	class AIPatternLogos {

		/**
		 * `logoSource.mode` the first AI builds saved alongside shipped placeholder
		 * SVGs. Those files are gone, so a block still holding them is refilled
		 * rather than kept.
		 */
		const LEGACY_DEMO_MODE = 'ai-demo';

		/**
		 * Most pattern payloads fetched for one build.
		 *
		 * Each uncached payload is a remote request, so a run of patterns without
		 * logos cannot stall the builder. Payloads stay cached for hours, so in
		 * practice only the first build after a cache flush pays for any of them.
		 */
		const MAX_FETCHES = 3;

		/**
		 * Fill a block that has no logos yet with a Ready Pattern's demo logos.
		 *
		 * Logos already on the block are never touched — the user's own media, or
		 * the demo set an earlier pattern insert or AI round brought in. Only an
		 * empty source, or one still holding the legacy shipped placeholders, is
		 * filled.
		 *
		 * @param array      $attributes Merged attributes about to reach the editor.
		 * @param string     $prompt     User prompt, for picking a topic-matched pattern.
		 * @param string     $block_name Block the attributes were generated for.
		 * @param array|null $candidates Patterns already shortlisted for this prompt, or null to shortlist here.
		 * @return array<string, mixed>
		 */
		public static function fill( array $attributes, $prompt, $block_name, $candidates = null ) {
			if ( ! self::needs_logos( $attributes ) ) {
				return $attributes;
			}

			if ( ! PatternRepository::is_enabled() ) {
				return self::apply( $attributes, null );
			}

			$repository = new PatternRepository();
			if ( ! is_array( $candidates ) ) {
				$candidates = AIPatternMatcher::shortlist( $prompt, $block_name );
			}

			$order   = self::candidate_order( $candidates, $repository->get_manifest(), $block_name );
			$fetched = 0;

			foreach ( $order as $pattern_id ) {
				if ( $fetched >= self::MAX_FETCHES ) {
					break;
				}
				++$fetched;

				$content = $repository->get_pattern_content( $pattern_id );
				if ( ! is_string( $content ) || '' === $content ) {
					continue;
				}

				$source = self::source_from_blocks( parse_blocks( $content ), $block_name );
				if ( null !== $source ) {
					return self::apply( $attributes, $source );
				}
			}

			return self::apply( $attributes, null );
		}

		/**
		 * Whether a block still needs logos to show what the AI configured.
		 *
		 * @param array $attributes Block attributes.
		 * @return bool
		 */
		public static function needs_logos( array $attributes ) {
			if ( ! array_key_exists( 'logoSource', $attributes ) ) {
				return false;
			}

			$source = is_array( $attributes['logoSource'] ) ? $attributes['logoSource'] : array();
			$items  = isset( $source['items'] ) && is_array( $source['items'] ) ? $source['items'] : array();
			$mode   = isset( $source['mode'] ) ? (string) $source['mode'] : '';

			return empty( $items ) || self::LEGACY_DEMO_MODE === $mode;
		}

		/**
		 * Pattern ids to borrow logos from, best first.
		 *
		 * Patterns the prompt shortlisted come first, so "tech partners" borrows
		 * from a tech-partner layout; within each group a pattern for the same
		 * block wins, since its items carry the fields that block displays. The
		 * rest of the catalog follows, most popular first, so a prompt that only
		 * names settings ("make it faster") still gets logos.
		 *
		 * @param array  $candidates Shortlisted manifest items, most relevant first.
		 * @param array  $manifest   Full normalized manifest.
		 * @param string $block_name Target block.
		 * @return array<int, int|string> Unique pattern ids.
		 */
		public static function candidate_order( array $candidates, array $manifest, $block_name ) {
			$target = AICatalogBuilder::slug( (string) $block_name );

			$popular = array_values( array_filter( $manifest, 'is_array' ) );
			usort(
				$popular,
				static function ( $left, $right ) {
					$left_hit  = isset( $left['hit'] ) ? (int) $left['hit'] : 0;
					$right_hit = isset( $right['hit'] ) ? (int) $right['hit'] : 0;

					return $right_hit <=> $left_hit;
				}
			);

			$ids = array();
			foreach ( array( $candidates, $popular ) as $group ) {
				$same_block  = array();
				$other_block = array();

				foreach ( $group as $item ) {
					if ( ! is_array( $item ) || ! isset( $item['id'] ) || '' === (string) $item['id'] ) {
						continue;
					}

					$item_block = isset( $item['blockName'] ) ? AICatalogBuilder::slug( (string) $item['blockName'] ) : '';
					if ( $item_block === $target ) {
						$same_block[] = $item['id'];
					} else {
						$other_block[] = $item['id'];
					}
				}

				foreach ( array_merge( $same_block, $other_block ) as $id ) {
					if ( ! in_array( $id, $ids, true ) ) {
						$ids[] = $id;
					}
				}
			}

			return $ids;
		}

		/**
		 * The logo source of the best Logo Carousel block in a parsed pattern.
		 *
		 * A pattern may wrap its showcase in core groups or carry more than one
		 * Logo Carousel block; the one matching the target block wins, otherwise
		 * the first that carries logos.
		 *
		 * @param array  $blocks     Output of `parse_blocks()`.
		 * @param string $block_name Target block.
		 * @return array{mode: string, items: array}|null Null when no block carries usable logos.
		 */
		public static function source_from_blocks( array $blocks, $block_name ) {
			$target   = AICatalogBuilder::slug( (string) $block_name );
			$fallback = null;

			foreach ( self::flatten( $blocks ) as $block ) {
				$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
				if ( 0 !== strpos( $name, 'sp-logo-carousel/' ) ) {
					continue;
				}

				$attrs  = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
				$source = isset( $attrs['logoSource'] ) && is_array( $attrs['logoSource'] ) ? $attrs['logoSource'] : array();
				$items  = self::sanitize_items( isset( $source['items'] ) ? $source['items'] : array() );

				if ( empty( $items ) ) {
					continue;
				}

				// sanitize_key()'s rule, inlined so the pure helpers stay unit-testable.
				$mode = isset( $source['mode'] ) ? preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $source['mode'] ) ) : '';
				$hit  = array(
					'mode'  => '' === $mode || self::LEGACY_DEMO_MODE === $mode ? 'scratch' : $mode,
					'items' => $items,
				);

				if ( AICatalogBuilder::slug( $name ) === $target ) {
					return $hit;
				}

				if ( null === $fallback ) {
					$fallback = $hit;
				}
			}

			return $fallback;
		}

		/**
		 * Make a pattern's logo items safe to drop into another site's block.
		 *
		 * Mirrors the editor's `sanitizePatternAttributes()`: a numeric `mediaId`
		 * or `id` points into the pattern author's media library, not this one, so
		 * it is cleared (and the item re-keyed) while the remote `imageUrl` is
		 * kept. An item with no http(s) image, or one the pattern itself marked as
		 * a placeholder, is dropped — there is nothing real to show for it.
		 *
		 * @param mixed $items Raw `logoSource.items` from a pattern block.
		 * @return array<int, array<string, mixed>>
		 */
		public static function sanitize_items( $items ) {
			if ( ! is_array( $items ) ) {
				return array();
			}

			$clean = array();
			foreach ( array_values( $items ) as $index => $item ) {
				if ( ! is_array( $item ) || ! empty( $item['isPlaceholder'] ) ) {
					continue;
				}

				$image_url = isset( $item['imageUrl'] ) && is_string( $item['imageUrl'] ) ? trim( $item['imageUrl'] ) : '';
				if ( ! preg_match( '#^https?://#i', $image_url ) ) {
					continue;
				}

				$item['imageUrl'] = $image_url;

				if ( self::is_numeric_id( isset( $item['mediaId'] ) ? $item['mediaId'] : '' ) ) {
					$item['mediaId'] = '';
				}

				if ( ! isset( $item['id'] ) || '' === (string) $item['id'] || self::is_numeric_id( $item['id'] ) ) {
					$item['id'] = 'sp-lcpro-remote-logo-' . $index;
				}

				$item['order'] = count( $clean );
				$clean[]       = $item;
			}

			return $clean;
		}

		/**
		 * Write a borrowed logo source onto the attributes.
		 *
		 * With nothing to borrow, an empty block stays empty; legacy placeholder
		 * items are cleared rather than left pointing at files that no longer
		 * ship.
		 *
		 * @param array      $attributes Block attributes.
		 * @param array|null $source     Result of `source_from_blocks()`, or null.
		 * @return array<string, mixed>
		 */
		public static function apply( array $attributes, $source ) {
			if ( is_array( $source ) && ! empty( $source['items'] ) ) {
				$attributes['logoSource']  = array(
					'mode'      => (string) $source['mode'],
					'patternId' => '',
					'items'     => $source['items'],
				);
				$attributes['patternDemo'] = true;

				return $attributes;
			}

			$current = is_array( $attributes['logoSource'] ) ? $attributes['logoSource'] : array();
			$mode    = isset( $current['mode'] ) ? (string) $current['mode'] : '';

			if ( self::LEGACY_DEMO_MODE === $mode ) {
				$attributes['logoSource']  = array(
					'mode'      => '',
					'patternId' => '',
					'items'     => array(),
				);
				$attributes['patternDemo'] = false;
			}

			return $attributes;
		}

		/**
		 * Every block in a parsed tree, depth first.
		 *
		 * @param array $blocks Parsed blocks.
		 * @return array<int, array<string, mixed>>
		 */
		private static function flatten( array $blocks ) {
			$flat = array();
			foreach ( $blocks as $block ) {
				if ( ! is_array( $block ) ) {
					continue;
				}

				$flat[] = $block;
				if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
					$flat = array_merge( $flat, self::flatten( $block['innerBlocks'] ) );
				}
			}

			return $flat;
		}

		/**
		 * Whether an id is a donor-site attachment id.
		 *
		 * @param mixed $id Item or media id.
		 * @return bool
		 */
		private static function is_numeric_id( $id ) {
			if ( is_int( $id ) ) {
				return true;
			}

			return is_string( $id ) && 1 === preg_match( '/^\d+$/', trim( $id ) );
		}
	}
}
