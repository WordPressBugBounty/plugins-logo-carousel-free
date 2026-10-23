<?php
/**
 * AI Pattern Matcher.
 *
 * Shortlists Ready Patterns for a natural language prompt and resolves the
 * model's pick.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\BlocksVisibility;
use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\ModulesVisibility;
use ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns\PatternRepository;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AIPatternMatcher' ) ) {

	/**
	 * Lexical shortlist over the Ready Patterns catalog.
	 *
	 * The AI builder is pattern-first: a prompt that a published Ready Pattern
	 * already answers is served by inserting that pattern's markup, so the model
	 * never has to invent attribute JSON for a layout somebody already designed.
	 * Attribute generation is the fallback for prompts no pattern covers.
	 *
	 * Only a shortlist reaches the model. The catalog runs to hundreds of
	 * patterns and sending all of them would cost more tokens than the attribute
	 * path it replaces, so a local lexical pass narrows the field and the model
	 * only judges relevance.
	 */
	class AIPatternMatcher {

		/**
		 * Maximum candidates handed to the model.
		 */
		const MAX_CANDIDATES = 24;

		/**
		 * Minimum confidence the model must report before a pattern is inserted.
		 */
		const MIN_CONFIDENCE = 0.6;

		/**
		 * Confidence assumed when the model picks a pattern but reports no number.
		 */
		const ASSUMED_CONFIDENCE = 0.75;

		/**
		 * Characters kept from a pattern description in the candidate list.
		 */
		const DESCRIPTION_LIMIT = 120;

		/**
		 * Weight applied to terms the prompt implies rather than states.
		 */
		const SYNONYM_WEIGHT = 0.8;

		/**
		 * Weight applied to a word that names the container rather than the subject.
		 */
		const GENERIC_WEIGHT = 0.35;

		/**
		 * Score a pattern must reach before it is worth a slot in the shortlist.
		 *
		 * A prompt that only names a block ("make the carousel faster") lands
		 * under this on every item, so a settings request costs no model
		 * round-trip at all.
		 */
		const MIN_SCORE = 4.0;

		/**
		 * Prompt words that carry no matching signal.
		 *
		 * Two groups: conversational filler, and the settings vocabulary
		 * ("columns", "speed", "color") that describes a knob rather than a
		 * design. "Logo" and "logos" are deliberately absent — in a logo-showcase
		 * plugin almost every prompt says them, so they are demoted to generic
		 * terms below rather than dropped, which would blind the matcher to the
		 * one subject the whole catalog is about.
		 *
		 * @var string[]
		 */
		private static $stop_words = array(
			'the',
			'and',
			'with',
			'for',
			'from',
			'that',
			'this',
			'have',
			'has',
			'want',
			'wants',
			'need',
			'needs',
			'make',
			'made',
			'create',
			'creates',
			'build',
			'builds',
			'please',
			'show',
			'using',
			'use',
			'uses',
			'add',
			'into',
			'some',
			'more',
			'like',
			'give',
			'set',
			'put',
			'can',
			'you',
			'not',
			'but',
			'are',
			'its',
			'each',
			'per',
			'all',
			'new',
			'our',
			'ours',
			'your',
			'yours',
			'their',
			'them',
			'they',
			'who',
			'what',
			'when',
			'where',
			'how',
			'why',
			'about',
			'here',
			'there',
			'these',
			'those',
			'will',
			'would',
			'could',
			'should',
			'get',
			'got',
			'let',
			'see',
			'look',
			'looks',
			'looking',
			'nice',
			'nicer',
			'good',
			'better',
			'really',
			'very',
			'change',
			'update',
			'turn',
			'increase',
			'decrease',
			'design',
			'designs',
			'layout',
			'layouts',
			'style',
			'styles',
			'thing',
			'things',
			'stuff',
			'color',
			'colors',
			'colour',
			'colours',
			'speed',
			'column',
			'columns',
			'row',
			'rows',
			'size',
			'width',
			'height',
			'margin',
			'padding',
			'font',
			'gap',
			'spacing',
			'delay',
			'duration',
			'arrow',
			'arrows',
			'dots',
			'loop',
		);

		/**
		 * Words that name the container, not the subject.
		 *
		 * These appear in almost every prompt this plugin sees and describe what
		 * the block already is, so they steer without outvoting the thing being
		 * built.
		 *
		 * @var string[]
		 */
		private static $generic_terms = array(
			'carousel',
			'slider',
			'slideshow',
			'slide',
			'slides',
			'block',
			'widget',
			'showcase',
			'logo',
			'logos',
		);

		/**
		 * Short prompt words that still carry meaning; every other one is dropped.
		 *
		 * @var string[]
		 */
		private static $short_terms = array( '3d', 'b2b' );

		/**
		 * Canonical taxonomy term => the everyday words that imply it.
		 *
		 * Patterns are tagged with the vocabularies in `PatternVocabulary` and
		 * `CategoryMap` plus hand-written keywords; visitors type something else.
		 * This table is the bridge, and it is keyed by the canonical term so a
		 * new phrasing is one word added to an existing row.
		 *
		 * A multi-word alias is matched against consecutive prompt words, so
		 * "as seen in" reaches `logos` without "seen" and "in" having to carry
		 * it alone.
		 *
		 * @var array<string, string[]>
		 */
		private static $term_aliases = array(
			// Use cases (PatternVocabulary::USE_CASES).
			'hero'         => array( 'hero', 'banner', 'masthead', 'splash', 'above the fold', 'front banner', 'header image' ),
			'features'     => array( 'feature', 'features', 'service', 'services', 'benefit', 'benefits', 'solution', 'solutions', 'what we do', 'what we offer' ),
			'portfolio'    => array( 'portfolio', 'project', 'projects', 'case study', 'case studies', 'agency', 'studio', 'creative', 'our work' ),
			'gallery'      => array( 'gallery', 'galleries', 'photo', 'photos', 'image', 'images', 'album', 'mosaic', 'collage' ),
			'testimonials' => array( 'testimonial', 'testimonials', 'review', 'reviews', 'quote', 'quotes', 'feedback', 'praise', 'endorsement', 'social proof', 'what people say', 'what our customers say', 'happy customers' ),
			'team'         => array( 'team', 'staff', 'member', 'members', 'employee', 'employees', 'crew', 'founder', 'founders', 'leadership', 'meet the team' ),
			'logos'        => array( 'brand', 'brands', 'partner', 'partners', 'sponsor', 'sponsors', 'supplier', 'suppliers', 'client', 'clients', 'customer', 'customers', 'certification', 'certifications', 'badge', 'badges', 'award', 'awards', 'accreditation', 'integration', 'integrations', 'company', 'companies', 'wordmark', 'wordmarks', 'trusted by', 'as seen in', 'featured in', 'companies we work with', 'brands we work with', 'who we work with' ),
			'pricing'      => array( 'pricing', 'price', 'prices', 'plan', 'plans', 'package', 'packages', 'tier', 'tiers', 'subscription' ),
			'cta'          => array( 'cta', 'call to action', 'newsletter', 'subscribe', 'signup', 'sign up', 'opt in', 'promo', 'promotion' ),
			'faq'          => array( 'faq', 'faqs', 'question', 'questions', 'answers', 'accordion' ),
			'blog'         => array( 'blog', 'post', 'posts', 'article', 'articles', 'news', 'editorial', 'magazine', 'press', 'story', 'stories' ),
			'shop'         => array( 'shop', 'store', 'storefront', 'product', 'products', 'ecommerce', 'woocommerce', 'woo', 'retail', 'catalog', 'catalogue', 'merchandise', 'boutique', 'online store', 'online shop' ),
			'video'        => array( 'video', 'videos', 'youtube', 'vimeo', 'film', 'clip', 'clips', 'reel', 'showreel', 'streaming' ),
			'audio'        => array( 'audio', 'music', 'podcast', 'podcasts', 'episode', 'episodes', 'track', 'tracks', 'playlist', 'spotify', 'radio' ),
			'events'       => array( 'event', 'events', 'conference', 'schedule', 'agenda', 'speaker', 'speakers', 'session', 'sessions', 'workshop', 'meetup', 'webinar', 'expo', 'trade show' ),
			'listings'     => array( 'listing', 'listings', 'property', 'properties', 'real estate', 'directory', 'inventory', 'dealership', 'vehicle', 'vehicles' ),
			'education'    => array( 'course', 'courses', 'lesson', 'lessons', 'class', 'classes', 'student', 'students', 'school', 'university', 'academy', 'training', 'elearning', 'campus' ),
			'hospitality'  => array( 'hotel', 'hotels', 'resort', 'restaurant', 'cafe', 'menu', 'food', 'booking', 'reservation', 'spa', 'salon', 'travel', 'tour', 'tours', 'destination', 'destinations' ),
			'downloads'    => array( 'download', 'downloads', 'pdf', 'document', 'documents', 'brochure', 'datasheet' ),
			'social-feed'  => array( 'instagram', 'facebook', 'twitter', 'tiktok', 'linkedin', 'rss', 'feed', 'feeds', 'social', 'timeline' ),
			'page'         => array( 'page', 'homepage', 'home page', 'landing', 'landing page', 'front page', 'website', 'site', 'template', 'one pager', 'full page', 'web page' ),

			// Layout traits (PatternVocabulary::RECOMMENDED_TRAITS).
			'fullscreen'   => array( 'fullscreen', 'full screen', 'full width', 'fullwidth', 'edge to edge', 'immersive' ),
			'boxed'        => array( 'boxed', 'contained', 'card', 'cards', 'framed', 'tile', 'tiles' ),
			'overlay'      => array( 'overlay', 'caption', 'captions', 'text on image', 'text over' ),
			'classic'      => array( 'classic', 'traditional', 'standard' ),
			'bento'        => array( 'bento', 'masonry', 'mosaic', 'collage', 'asymmetric' ),
			'vertical'     => array( 'vertical', 'sidebar', 'up and down', 'stacked', 'top to bottom' ),
			'filterable'   => array( 'filter', 'filters', 'filterable', 'filtering', 'sortable', 'tabs', 'dropdown', 'category', 'categories', 'search', 'searchable' ),
			'autoplay'     => array( 'autoplay', 'auto play', 'auto rotate', 'plays itself' ),
			'lightbox'     => array( 'lightbox', 'popup', 'pop up', 'modal', 'enlarge' ),
			'ajax'         => array( 'ajax', 'load more', 'loadmore', 'pagination', 'paginated', 'infinite scroll', 'lazy load', 'page numbers' ),
			'dark'         => array( 'dark', 'night', 'black background' ),
			'light'        => array( 'light', 'bright', 'white background' ),
			'minimal'      => array( 'minimal', 'clean', 'simple', 'subtle', 'understated', 'plain' ),
			'bold'         => array( 'bold', 'striking', 'dramatic', 'punchy', 'loud' ),

			// Blocks (CategoryMap::BLOCK_MAP).
			'marquee'      => array( 'marquee', 'ticker', 'scrolling', 'scroll', 'sideways', 'continuous', 'endless', 'non stop', 'never stops', 'moving strip', 'ribbon', 'wave', 'infinite strip' ),
			'grid'         => array( 'grid', 'wall', 'bento', 'masonry', 'staggered', 'zigzag' ),
			'list'         => array( 'list', 'directory', 'roster', 'index', 'rows of' ),
			'table'        => array( 'table', 'comparison', 'compare', 'spec sheet', 'matrix', 'column headers', 'header row' ),

			// Recurring catalog keywords that no taxonomy term covers.
			'grayscale'    => array( 'grayscale', 'greyscale', 'monochrome', 'black and white', 'desaturated' ),
			'hover'        => array( 'hover', 'mouse over', 'on hover' ),
			'rounded'      => array( 'rounded', 'round', 'soft corners' ),
			'rating'       => array( 'rating', 'ratings', 'star', 'stars', 'rated', 'score' ),
			'tooltip'      => array( 'tooltip', 'tooltips', 'hover card', 'hint' ),
		);

		/**
		 * Alias table indexed for lookup, built once per request.
		 *
		 * @var array<string, mixed>|null
		 */
		private static $alias_map = null;

		/**
		 * Shortlist the patterns worth showing the model for a prompt.
		 *
		 * @param string $prompt     Natural language prompt.
		 * @param string $block_name Block the request is aimed at.
		 * @return array<int, array<string, mixed>> Manifest items, most relevant first.
		 */
		public static function shortlist( $prompt, $block_name = '' ) {
			if ( ! PatternRepository::is_enabled() ) {
				return array();
			}

			$repository = new PatternRepository();

			return self::rank( $repository->get_manifest(), $prompt, $block_name );
		}

		/**
		 * Rank a manifest against a prompt and cut it down to the shortlist.
		 *
		 * @param array  $items      Normalized manifest items.
		 * @param string $prompt     Natural language prompt.
		 * @param string $block_name Block the request is aimed at.
		 * @return array<int, array<string, mixed>> Manifest items, most relevant first.
		 */
		public static function rank( array $items, $prompt, $block_name = '' ) {
			if ( empty( $items ) ) {
				return array();
			}

			$matchers = self::token_matchers( self::weighted_tokens( $prompt ) );
			if ( empty( $matchers ) ) {
				return array();
			}

			$blocks = new BlocksVisibility();

			$scored = array();
			foreach ( $items as $index => $item ) {
				if ( ! is_array( $item ) || empty( $item['slug'] ) || empty( $item['blockName'] ) ) {
					continue;
				}

				// A pattern this site cannot satisfy is never offered: the builder
				// would hand the editor a preflight dialog instead of the showcase
				// it asked for.
				if ( ! self::requirements_met( isset( $item['requires'] ) ? $item['requires'] : array() ) ) {
					continue;
				}

				// A pattern targeting a disabled block is never offered either:
				// `insertPattern()` rejects it client-side anyway, so shortlisting
				// it would only spend a model round-trip to land on a pattern the
				// editor refuses to insert.
				$pattern_block_slug = AICatalogBuilder::slug( (string) $item['blockName'] );
				if ( ! $blocks->is_enabled( $pattern_block_slug ) ) {
					continue;
				}

				// Nor is a pattern the free plugin cannot insert: a Pro-only block
				// (List/Marquee/Table) or a Pro-only module. The AI builder may
				// only ever land on Carousel or Grid.
				if ( ! empty( $item['requiresPro'] ) || ! isset( AICatalogBuilder::SCHEMA_MAP[ $pattern_block_slug ] ) ) {
					continue;
				}

				$score = self::score_item( $item, $matchers, $block_name );
				if ( self::MIN_SCORE > $score ) {
					continue;
				}

				$scored[] = array(
					'score' => $score,
					'hit'   => isset( $item['hit'] ) ? (int) $item['hit'] : 0,
					'index' => $index,
					'item'  => $item,
				);
			}

			usort(
				$scored,
				static function ( $left, $right ) {
					// Rounded: two patterns that score within a hundredth of each
					// other are equally relevant, and popularity is the better
					// answer than manifest order.
					$left_score  = round( $left['score'], 2 );
					$right_score = round( $right['score'], 2 );
					if ( $left_score !== $right_score ) {
						return $left_score < $right_score ? 1 : -1;
					}

					if ( $left['hit'] !== $right['hit'] ) {
						return $left['hit'] < $right['hit'] ? 1 : -1;
					}

					return $left['index'] <=> $right['index'];
				}
			);

			$candidates = array();
			foreach ( array_slice( $scored, 0, self::MAX_CANDIDATES ) as $entry ) {
				$candidates[] = $entry['item'];
			}

			return $candidates;
		}

		/**
		 * The weighted vocabulary a prompt resolves to, alias expansion included.
		 *
		 * The same read of the prompt that ranks patterns also answers "which
		 * block is this person describing?": the alias table already carries
		 * block-slug rows (`marquee`, `grid`, `list`, `table`), so a prompt
		 * saying "ticker" or "comparison" comes back carrying that slug as a
		 * term. Exposing the tokenizer rather than copying it keeps
		 * `AIBlockRouter` on one vocabulary with the pattern shortlist.
		 *
		 * @param string $prompt Natural language prompt.
		 * @return array<string, float> Term => weight.
		 */
		public static function prompt_terms( $prompt ) {
			return self::weighted_tokens( $prompt );
		}

		/**
		 * Weight below which a term is only naming the container, not choosing one.
		 *
		 * @return float
		 */
		public static function generic_term_weight() {
			return self::GENERIC_WEIGHT;
		}

		/**
		 * The ways a prompt states how many logos sit across, tried in order.
		 *
		 * "5 columns" is the phrasing a developer reaches for; the starter briefs —
		 * and most people describing a showcase — say "4 logos per row" or "4 logos
		 * visible" instead.
		 *
		 * JS twin: `COLUMN_COUNT_PATTERNS` in the editor's `columnOverride.js`.
		 *
		 * @var string[]
		 */
		const COLUMN_COUNT_PATTERNS = array(
			// Developer wording: 5 columns, 5-column, 5 cols.
			'/(\d+)\s*[- ]?\s*col(?:umn)?s?\b/i',
			// Counted per row: 4 logos per row, 4 cards per row, 4 per row.
			'/(\d+)\s*(?:logos?|cards?|items?|brands?|marks?)?\s*per\s+row\b/i',
			// Counted across the track: 4 logos visible, 5 logos across, 3 wide.
			'/(\d+)\s*(?:logos?|cards?|items?|brands?|marks?)?\s*(?:visible|across|wide)\b/i',
		);

		/**
		 * Read a column count the prompt states outright.
		 *
		 * PHP twin of `extractColumnCount()` in the editor's `columnOverride.js`,
		 * which enforces the same number onto whatever the AI produces. Both clamp
		 * to the 1-12 range of the real control. A count of 1 is not a
		 * multi-column requirement, so it reads as none here.
		 *
		 * @param string $prompt Natural language prompt.
		 * @return int|null Requested count above 1, or null when the prompt names none.
		 */
		public static function requested_column_count( $prompt ) {
			foreach ( self::COLUMN_COUNT_PATTERNS as $pattern ) {
				if ( 1 !== preg_match( $pattern, (string) $prompt, $matches ) ) {
					continue;
				}

				$columns = min( 12, (int) $matches[1] );

				if ( 1 < $columns ) {
					return $columns;
				}
			}

			return null;
		}

		/**
		 * Render candidates as compact pipe-delimited lines for the model.
		 *
		 * @param array $candidates Shortlisted manifest items.
		 * @return string
		 */
		public static function to_prompt_payload( array $candidates ) {
			$lines = array();

			foreach ( $candidates as $item ) {
				$description = isset( $item['description'] ) ? (string) $item['description'] : '';
				if ( mb_strlen( $description ) > self::DESCRIPTION_LIMIT ) {
					$description = mb_substr( $description, 0, self::DESCRIPTION_LIMIT ) . '…';
				}

				$lines[] = implode(
					' | ',
					array(
						(string) $item['slug'],
						isset( $item['name'] ) ? (string) $item['name'] : '',
						AICatalogBuilder::slug( (string) $item['blockName'] ),
						implode( ',', isset( $item['useCases'] ) && is_array( $item['useCases'] ) ? $item['useCases'] : array() ),
						implode( ',', isset( $item['tags'] ) && is_array( $item['tags'] ) ? $item['tags'] : array() ),
						isset( $item['sourceType'] ) ? (string) $item['sourceType'] : '',
						isset( $item['complexity'] ) ? (string) $item['complexity'] : '',
						str_replace( array( "\r", "\n", '|' ), ' ', $description ),
					)
				);
			}

			return implode( "\n", $lines );
		}

		/**
		 * Resolve the model's selection against the shortlist it was given.
		 *
		 * A slug outside the shortlist is a hallucination, not a pick, so it
		 * resolves to no match and the builder falls through to attribute
		 * generation.
		 *
		 * @param array $selection  Parsed model response.
		 * @param array $candidates Shortlist handed to the model.
		 * @return array<string, mixed>|null
		 */
		public static function resolve_selection( array $selection, array $candidates ) {
			$slug = '';
			foreach ( array( 'slug', 'patternSlug', 'pattern_slug' ) as $key ) {
				if ( ! empty( $selection[ $key ] ) && is_scalar( $selection[ $key ] ) ) {
					$slug = sanitize_title( (string) $selection[ $key ] );
					break;
				}
			}

			if ( '' === $slug ) {
				return null;
			}

			$confidence = self::ASSUMED_CONFIDENCE;
			if ( isset( $selection['confidence'] ) && is_numeric( $selection['confidence'] ) ) {
				$confidence = (float) $selection['confidence'];
			}

			if ( $confidence < self::MIN_CONFIDENCE ) {
				return null;
			}

			foreach ( $candidates as $item ) {
				if ( ! isset( $item['slug'] ) || $slug !== (string) $item['slug'] ) {
					continue;
				}

				return array(
					'pattern'    => $item,
					'confidence' => $confidence,
					'reason'     => isset( $selection['reason'] ) && is_scalar( $selection['reason'] )
						? sanitize_text_field( (string) $selection['reason'] )
						: '',
				);
			}

			return null;
		}

		/**
		 * Whether a pattern may replace what the target block already holds.
		 *
		 * A pattern insert brings its own content, so it is offered only while the
		 * block is still unconfigured — a prompt aimed at a block that already
		 * carries the user's own logos is a refinement request, and refinements go
		 * through attribute generation. Placeholder logos an earlier AI round
		 * added are scaffolding, not content, so they do not lock the block.
		 *
		 * @param array $attributes Existing block attributes.
		 * @return bool
		 */
		public static function allows_pattern_insert( array $attributes ) {
			if ( empty( $attributes ) ) {
				return true;
			}

			$source = isset( $attributes['logoSource'] ) && is_array( $attributes['logoSource'] )
				? $attributes['logoSource']
				: array();

			// Demo logos a Ready Pattern or an earlier AI round brought in are
			// scaffolding too, whatever source mode they were saved under.
			if ( ! empty( $attributes['patternDemo'] ) ) {
				return true;
			}

			$mode = isset( $source['mode'] ) ? (string) $source['mode'] : '';
			if ( AIPatternLogos::LEGACY_DEMO_MODE === $mode ) {
				return true;
			}

			$items = isset( $source['items'] ) && is_array( $source['items'] ) ? $source['items'] : array();

			return empty( $items );
		}

		/**
		 * Whether this site satisfies a pattern's declared requirements.
		 *
		 * Mirrors the editor's `describeRequirements()`: an unknown plugin or
		 * module slug counts as met, so a server-side addition cannot silently
		 * hide the whole catalog from the builder.
		 *
		 * @param mixed $requires Normalized `requires` block.
		 * @return bool
		 */
		public static function requirements_met( $requires ) {
			if ( ! is_array( $requires ) ) {
				return true;
			}

			$modules = isset( $requires['modules'] ) && is_array( $requires['modules'] ) ? $requires['modules'] : array();
			if ( ! empty( $modules ) ) {
				$module_store = new ModulesVisibility();
				foreach ( $modules as $module_slug ) {
					if ( ! $module_store->is_enabled( (string) $module_slug ) ) {
						return false;
					}
				}
			}

			$min_version = isset( $requires['minPluginVersion'] ) ? (string) $requires['minPluginVersion'] : '';
			if ( '' !== $min_version && version_compare( \ShapedPlugin\LogoCarouselFree\Includes\ProFeatures::PRO_COMPAT_VERSION, $min_version, '<' ) ) {
				return false;
			}

			// `plugins` and `sources` are left unchecked on purpose: this plugin
			// declares no companion-plugin dependency and both of its content
			// sources (image, logo) ship with it, so the editor's own
			// `PLUGIN_LABELS` / `SOURCE_PLUGIN_DEPENDENCY` maps are empty too.
			return true;
		}

		/**
		 * Relevance score for one manifest item.
		 *
		 * @param array  $item       Manifest item.
		 * @param array  $matchers   Prompt token matchers.
		 * @param string $block_name Block the request is aimed at.
		 * @return float
		 */
		private static function score_item( array $item, array $matchers, $block_name ) {
			$name        = isset( $item['name'] ) ? strtolower( (string) $item['name'] ) : '';
			$description = isset( $item['description'] ) ? strtolower( (string) $item['description'] ) : '';
			$terms       = self::item_terms( $item );
			$terms_blob  = implode( ' ', $terms );
			$haystack    = $name . ' ' . $description . ' ' . $terms_blob;
			$score       = 0.0;

			foreach ( $matchers as $key => $matcher ) {
				$token = (string) $key;

				// Cheap gate: a token absent from every field cannot score, and
				// skipping it here keeps the regex passes off the great majority
				// of the catalog.
				if ( false === strpos( $haystack, $token ) ) {
					continue;
				}

				$weight = $matcher['weight'];

				if ( in_array( $token, $terms, true ) ) {
					$score += 4 * $weight;
				} elseif ( 1 === preg_match( $matcher['regex'], $terms_blob ) ) {
					$score += 2 * $weight;
				}

				if ( '' !== $name && 1 === preg_match( $matcher['regex'], $name ) ) {
					$score += 5 * $weight;
				}

				if ( '' !== $description && 1 === preg_match( $matcher['regex'], $description ) ) {
					$score += 1.5 * $weight;
				}
			}

			// A nudge, not a filter: the request's block is a default as often as
			// it is a choice, and a prompt that names another block should still
			// reach it.
			if ( '' !== $block_name && isset( $item['blockName'] ) && $block_name === $item['blockName'] ) {
				$score += 2;
			}

			return $score;
		}

		/**
		 * Compile one whole-word matcher per prompt token.
		 *
		 * Whole words only: substring matching let "car" score every pattern with
		 * "cards" in it. The optional plural suffix keeps "photo" reaching
		 * "photos" without reopening that door.
		 *
		 * @param array $weighted_tokens Token => weight.
		 * @return array<string, array<string, mixed>>
		 */
		private static function token_matchers( array $weighted_tokens ) {
			$matchers = array();

			foreach ( $weighted_tokens as $token => $weight ) {
				$matchers[ (string) $token ] = array(
					'weight' => $weight,
					'regex'  => '/\b' . preg_quote( (string) $token, '/' ) . '(?:s|es)?\b/',
				);
			}

			return $matchers;
		}

		/**
		 * Collect a manifest item's searchable slugs.
		 *
		 * @param array $item Manifest item.
		 * @return string[]
		 */
		private static function item_terms( array $item ) {
			$terms = array();

			foreach ( array( 'useCases', 'tags', 'keywords', 'blocks' ) as $key ) {
				if ( empty( $item[ $key ] ) || ! is_array( $item[ $key ] ) ) {
					continue;
				}

				foreach ( $item[ $key ] as $value ) {
					if ( ! is_scalar( $value ) ) {
						continue;
					}

					$terms = array_merge( $terms, self::split_terms( (string) $value ) );
				}
			}

			foreach ( array( 'category', 'sourceType', 'complexity', 'family', 'type', 'blockName' ) as $key ) {
				if ( empty( $item[ $key ] ) || ! is_scalar( $item[ $key ] ) ) {
					continue;
				}

				$terms = array_merge( $terms, self::split_terms( (string) $item[ $key ] ) );
			}

			return array_values( array_unique( $terms ) );
		}

		/**
		 * Split a slug or phrase into lowercase terms, keeping the whole slug too.
		 *
		 * @param string $value Raw value.
		 * @return string[]
		 */
		private static function split_terms( $value ) {
			$value = strtolower( trim( (string) $value ) );
			if ( '' === $value ) {
				return array();
			}

			$value = str_replace( AICatalogBuilder::BLOCK_PREFIX, '', $value );
			$parts = preg_split( '/[^a-z0-9]+/', $value );
			$terms = array( $value );

			if ( ! is_array( $parts ) ) {
				return $terms;
			}

			foreach ( $parts as $part ) {
				if ( '' !== $part ) {
					$terms[] = $part;
				}
			}

			return $terms;
		}

		/**
		 * Tokenize a prompt into scored terms, expanding implied vocabulary.
		 *
		 * @param string $prompt Natural language prompt.
		 * @return array<string, float> Token => weight.
		 */
		private static function weighted_tokens( $prompt ) {
			$normalized = strtolower( wp_strip_all_tags( (string) $prompt ) );
			$words      = preg_split( '/[^a-z0-9]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY );
			$tokens     = array();

			if ( ! is_array( $words ) || empty( $words ) ) {
				return $tokens;
			}

			foreach ( $words as $word ) {
				if ( ! self::is_matchable_word( $word ) ) {
					continue;
				}

				$tokens[ $word ] = self::base_weight( $word );
			}

			// A plural in the prompt ("ratings") must still reach a candidate
			// whose tags or description only carry the singular ("rating") —
			// patterns are hand-written prose, not guaranteed to mirror the
			// user's grammar.
			foreach ( array_keys( $tokens ) as $token ) {
				$stem = self::stem( (string) $token );
				if ( $stem !== $token && ! isset( $tokens[ $stem ] ) ) {
					$tokens[ $stem ] = self::SYNONYM_WEIGHT;
				}
			}

			$aliases = self::alias_map();

			foreach ( array_keys( $tokens ) as $token ) {
				self::add_implied_terms( $tokens, isset( $aliases['words'][ $token ] ) ? $aliases['words'][ $token ] : array() );
			}

			// Phrases run over the raw words, not the filtered tokens: "trusted
			// by" and "as seen in" are carried by words that are stop words on
			// their own.
			foreach ( self::phrases( $words, $aliases['maxPhraseWords'] ) as $phrase ) {
				self::add_implied_terms( $tokens, isset( $aliases['phrases'][ $phrase ] ) ? $aliases['phrases'][ $phrase ] : array() );
			}

			return $tokens;
		}

		/**
		 * Whether a prompt word is worth scoring.
		 *
		 * @param string $word Lowercase prompt word.
		 * @return bool
		 */
		private static function is_matchable_word( $word ) {
			if ( in_array( $word, self::$stop_words, true ) ) {
				return false;
			}

			// A bare number ("3 columns") describes a setting, not a subject.
			if ( 1 === preg_match( '/^\d+$/', $word ) ) {
				return false;
			}

			if ( 3 > strlen( $word ) ) {
				return in_array( $word, self::$short_terms, true );
			}

			return true;
		}

		/**
		 * Starting weight for a prompt word.
		 *
		 * @param string $word Lowercase prompt word.
		 * @return float
		 */
		private static function base_weight( $word ) {
			return in_array( $word, self::$generic_terms, true ) ? self::GENERIC_WEIGHT : 1.0;
		}

		/**
		 * Add the taxonomy terms a prompt word or phrase implies.
		 *
		 * Implied terms never outrank what the user actually typed, and never
		 * lift a weight already assigned.
		 *
		 * @param array $tokens        Token => weight, by reference.
		 * @param array $implied_terms Canonical terms the prompt implies.
		 * @return void
		 */
		private static function add_implied_terms( array &$tokens, array $implied_terms ) {
			foreach ( $implied_terms as $implied ) {
				foreach ( self::split_terms( $implied ) as $term ) {
					if ( isset( $tokens[ $term ] ) ) {
						continue;
					}

					$tokens[ $term ] = in_array( $term, self::$generic_terms, true )
						? self::GENERIC_WEIGHT
						: self::SYNONYM_WEIGHT;
				}
			}
		}

		/**
		 * Every consecutive word run in a prompt, longest first.
		 *
		 * @param array $words     Prompt words in order.
		 * @param int   $max_words Longest alias phrase in the table.
		 * @return string[]
		 */
		private static function phrases( array $words, $max_words ) {
			$phrases = array();
			$count   = count( $words );

			for ( $length = $max_words; 2 <= $length; $length-- ) {
				for ( $start = 0; $count >= $start + $length; $start++ ) {
					$phrases[] = implode( ' ', array_slice( $words, $start, $length ) );
				}
			}

			return $phrases;
		}

		/**
		 * Alias table indexed for lookup: single words, phrases, longest phrase length.
		 *
		 * @return array<string, mixed>
		 */
		private static function alias_map() {
			if ( null !== self::$alias_map ) {
				return self::$alias_map;
			}

			$words            = array();
			$phrases          = array();
			$max_phrase_words = 2;

			foreach ( self::$term_aliases as $canonical => $aliases ) {
				foreach ( $aliases as $alias ) {
					$parts = preg_split( '/\s+/', trim( (string) $alias ), -1, PREG_SPLIT_NO_EMPTY );
					if ( ! is_array( $parts ) || empty( $parts ) ) {
						continue;
					}

					$alias_key = implode( ' ', $parts );

					if ( 1 === count( $parts ) ) {
						$words[ $alias_key ][] = (string) $canonical;
						continue;
					}

					$phrases[ $alias_key ][] = (string) $canonical;
					$max_phrase_words        = max( $max_phrase_words, count( $parts ) );
				}
			}

			self::$alias_map = array(
				'words'          => $words,
				'phrases'        => $phrases,
				'maxPhraseWords' => $max_phrase_words,
			);

			return self::$alias_map;
		}

		/**
		 * Strip a naive trailing plural so "ratings" also reaches "rating".
		 *
		 * Not a real stemmer — just enough to cover simple plurals without
		 * mangling words that already end in a double "s" ("boxes" → keep).
		 *
		 * @param string $token Lowercase token.
		 * @return string Singular form, or the token unchanged when stemming is unsafe.
		 */
		private static function stem( $token ) {
			if ( strlen( $token ) < 4 || 's' !== substr( $token, -1 ) || 'ss' === substr( $token, -2 ) ) {
				return $token;
			}

			return substr( $token, 0, -1 );
		}
	}
}
