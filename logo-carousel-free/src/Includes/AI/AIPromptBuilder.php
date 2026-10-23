<?php
/**
 * AI Prompt Builder.
 *
 * Constructs system and user prompts for translating natural language into
 * Logo Carousel block attributes.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AIPromptBuilder' ) ) {

	/**
	 * Builds the two prompts the AI builder sends.
	 */
	class AIPromptBuilder {

		/**
		 * Get the system prompt instructing the model to generate valid attribute JSON.
		 *
		 * The attribute catalog is block-specific: each block reads only the
		 * options that apply to it, not the full PHP schema tree (see
		 * `AICatalogBuilder`).
		 *
		 * @param string $block_name Target block slug.
		 * @return string System prompt.
		 */
		public static function get_system_prompt( $block_name = 'sp-logo-carousel/carousel' ) {
			$catalog  = AICatalogBuilder::get_catalog( $block_name );
			$rules    = self::design_rules();
			$examples = self::worked_examples( AICatalogBuilder::slug( $block_name ) );

			return "You are a senior web designer configuring the Logo Carousel plugin inside the WordPress block editor.
Your job is to translate a user's natural language prompt into a finished, presentable configuration for the \"{$block_name}\" block — not the smallest set of settings that technically answers the words.

This plugin shows logos: client logos, partner and sponsor marks, certifications, award badges, press mentions. The images are wordmarks on transparent or white backgrounds, not photographs, and that is what every choice below has to suit.

Output Requirements:
1. Output ONLY a valid raw JSON object containing only block attributes. Do NOT wrap it in markdown or backticks (no ```json).
2. Structure your JSON to set properties inside the appropriate attribute objects. Attributes available for this block:
{$catalog}
3. Return only valid block attributes matching the catalog above. Do not invent keys, and never use a value the catalog does not list — an unlisted value is discarded and the setting silently stays at its default.
4. Write \"layout\" flat, exactly as the catalog spells it (\"layout\": { \"columns\": … }). Never nest a layout field under \"shared\" or under a style name yourself, except for the style sub-objects the catalog names explicitly (the Grid's \"strip\"/\"staggered\"/\"zigzag\"). The server places every other field in the branch the renderer reads.
5. Never write \"logoSource\" — the logos are the user's own media. The server fills an empty block with placeholders.

Design Rules:
{$rules}

Worked Examples (these show the expected shape and level of finish — adapt the values to the actual request, never copy them):
{$examples}";
		}

		/**
		 * The design rules a generated configuration is judged against.
		 *
		 * Attribute generation runs only when no Ready Pattern fits, so its output
		 * is the whole answer — and the schema defaults it starts from are
		 * deliberately plain (no card fill, no border, no filter, text off).
		 * Left to the catalog alone a model returns the two or three settings the
		 * prompt literally names and stops, which reads as unfinished next to a
		 * pattern. These are the decisions that separate the two, plus the
		 * combinations that quietly cancel each other out at render time.
		 *
		 * @return string Numbered rule list.
		 */
		private static function design_rules() {
			return '1. Answer the whole request, then finish the design. Every setting the prompt names is mandatory; beyond those, set the few more that make the result look deliberate (breathing room, a resting/hover treatment, a section background) and leave everything else alone.
2. Logos need room, not decoration. A logo strip lives on generous gaps: 30-60px between marks on desktop reads far better than the 15px default. Give the section vertical padding (40-80px top and bottom) whenever it sits on its own coloured band.
3. "displayElements" is only the card around each logo — background, border, radius and padding. It never shows text: no names, taglines, descriptions or ratings appear with the logos, so do not promise them.
4. The classic logo treatment is muted at rest, full colour on hover: "logoImagesStyle.normal.imageFilter.saturation": -100 with "logoImagesStyle.hover.imageFilter.saturation": 0, usually alongside "normal.opacity" around 60-75 and "hover.opacity": 100. Reach for it whenever the prompt says greyscale, monochrome, muted, subtle, or "colour on hover" — and consider it for any understated client/partner strip.
5. Set the responsive trio together. Every "<count>" and "<size>" carries Desktop, Tablet and Mobile; fill all three (a sensible column ladder is 6/4/2, 5/3/2 or 4/2/1). Setting Desktop alone leaves unrelated defaults on smaller screens.
6. Respect what a value implies. Autoplay is only comfortable with "pauseOnHover": true. Turning "layout.paginationDots" on is worth a "pagination" colour that contrasts with the band behind it; turning "layout.navigationArrows" on is worth an arrow colour that does the same.
7. Match the subject. A client or partner strip wants a wide carousel with no text and a muted filter. A certification or award wall wants a grid with even gaps and maybe a card border.
8. Prefer restraint. One accent colour, one border treatment, one radius across the block. Do not set an option the request neither asks for nor needs, and never set a typeface — the site theme owns that.
9. Use "layout.edgeFade" when logos scroll past the edge of a band (a carousel with "partialSlideView"): a hard cut at the container edge looks like a rendering bug, a fade reads as intentional.';
		}

		/**
		 * Two complete responses, one per common shape of request.
		 *
		 * A bare type listing tells a model what it may write but nothing about
		 * how much to write; these fix the expected level of finish. They are
		 * deliberately different from each other (a muted logo strip vs. logos
		 * on cards) so the pair reads as a range to work within rather than a
		 * template to reproduce.
		 *
		 * The first example is resolved per block: an example is only worth
		 * showing if the block it is shown to could have produced it.
		 *
		 * @param string $slug Block slug, without the prefix.
		 * @return string Prompt/response pairs.
		 */
		private static function worked_examples( $slug ) {
			$muted = array(
				'layout'          => self::example_layout( $slug ),
				'imagesSettings'  => array(
					'aspectRatio' => 'original',
					'resolution'  => 'large',
					'clickAction' => 'link',
				),
				'logoImagesStyle' => array(
					'normal' => array(
						'opacity'     => 65,
						'imageFilter' => array( 'saturation' => -100 ),
					),
					'hover'  => array(
						'opacity'     => 100,
						'imageFilter' => array( 'saturation' => 0 ),
					),
				),
				'section'         => array(
					'background' => array(
						'style' => 'solid',
						'solid' => '#f8fafc',
					),
					'padding'    => self::example_spacing( 56, 24 ),
				),
			);

			$carded = array(
				'displayElements' => array(
					'cardBackground'  => array(
						'normal' => array(
							'style' => 'solid',
							'solid' => '#ffffff',
						),
					),
					'border'          => array(
						'style' => 'solid',
						'color' => '#e5e7eb',
					),
					'borderWidth'     => self::example_spacing( 1, 1 ),
					'borderRadius'    => self::example_spacing( 12, 12 ),
					'innerPadding'    => self::example_spacing( 24, 20 ),
				),
			);

			$refine = array( 'layout' => self::example_refine( $slug ) );

			return 'Request: "a muted client logo strip that goes full colour on hover"'
				. "\nResponse: " . wp_json_encode( $muted )
				. "\n\n" . 'Request: "put each partner logo on a white rounded card"'
				. "\nResponse: " . wp_json_encode( $carded )
				. "\n\n" . 'Request (a refinement of an existing block): "wider gaps and slower"'
				. "\nResponse: " . wp_json_encode( $refine );
		}

		/**
		 * A layout worth showing the block being generated for.
		 *
		 * @param string $slug Block slug.
		 * @return array<string, mixed>
		 */
		private static function example_layout( $slug ) {
			if ( 'grid' === $slug ) {
				return array(
					'gridStyle'     => 'standard',
					'columns'       => self::example_count( 6, 4, 2 ),
					'horizontalGap' => self::example_size( 48, 32, 20 ),
					'verticalGap'   => self::example_size( 48, 32, 20 ),
					'justify'       => 'center',
				);
			}

			return array(
				'carouselStyle'    => 'standard',
				'columns'          => self::example_count( 6, 4, 2 ),
				'gapBetweenLogos'  => self::example_size( 48, 32, 20 ),
				'autoplay'         => true,
				'autoplayDelay'    => 3000,
				'sliderSpeed'      => 700,
				'pauseOnHover'     => true,
				'infiniteLoop'     => true,
				'navigationArrows' => false,
				'paginationDots'   => false,
			);
		}

		/**
		 * The refine example's layout, in the vocabulary the target block has.
		 *
		 * @param string $slug Block slug.
		 * @return array<string, mixed>
		 */
		private static function example_refine( $slug ) {
			if ( 'grid' === $slug ) {
				return array(
					'horizontalGap' => self::example_size( 64, 44, 30 ),
					'verticalGap'   => self::example_size( 64, 44, 30 ),
				);
			}

			return array(
				'gapBetweenLogos' => self::example_size( 64, 44, 30 ),
				'sliderSpeed'     => 1200,
			);
		}

		/**
		 * Build a `<count>` example value.
		 *
		 * @param int $desktop Desktop value.
		 * @param int $tablet  Tablet value.
		 * @param int $mobile  Mobile value.
		 * @return array<string, mixed>
		 */
		private static function example_count( $desktop, $tablet, $mobile ) {
			return array(
				'device' => array(
					'Desktop' => $desktop,
					'Tablet'  => $tablet,
					'Mobile'  => $mobile,
				),
			);
		}

		/**
		 * Build a `<size>` example value.
		 *
		 * @param int $desktop Desktop value.
		 * @param int $tablet  Tablet value.
		 * @param int $mobile  Mobile value.
		 * @return array<string, mixed>
		 */
		private static function example_size( $desktop, $tablet, $mobile ) {
			return array(
				'device' => array(
					'Desktop' => $desktop,
					'Tablet'  => $tablet,
					'Mobile'  => $mobile,
				),
				'unit'   => array(
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
				),
			);
		}

		/**
		 * Build a `<spacing>` example value.
		 *
		 * @param int $vertical   Top/bottom value.
		 * @param int $horizontal Left/right value.
		 * @return array<string, mixed>
		 */
		private static function example_spacing( $vertical, $horizontal ) {
			return array(
				'device'    => array(
					'Desktop' => array(
						'top'    => $vertical,
						'right'  => $horizontal,
						'bottom' => $vertical,
						'left'   => $horizontal,
					),
				),
				'unit'      => array( 'Desktop' => 'px' ),
				'allChange' => $vertical === $horizontal,
			);
		}

		/**
		 * Get the system prompt for picking a Ready Pattern from a shortlist.
		 *
		 * @return string System prompt.
		 */
		public static function get_pattern_selection_prompt() {
			return 'You match a WordPress user\'s request to one pre-built Logo Carousel "Ready Pattern".

You are given candidate patterns as pipe-delimited lines:
slug | name | block | use-cases | tags | source | complexity | description

Output Requirements:
1. Output ONLY a raw JSON object. No markdown, no backticks, no prose.
2. Shape: {"slug": "<candidate slug or empty string>", "confidence": <number between 0 and 1>, "reason": "<max 20 words>"}
3. Pick a candidate only when it genuinely delivers what the user asked for — the same subject matter (client logos, partners, sponsors, awards, certifications, press, team, …) and a compatible layout or block.
4. A pattern that merely shares a block type is NOT a match. Prefer an empty slug over a loose one.
5. Set "slug" to "" when no candidate fits, when the request is only about settings (columns, speed, colours, autoplay, gaps) rather than a design, or when you are unsure.
6. Never invent a slug: it must be copied exactly from a candidate line.
7. A candidate whose block cannot deliver a structural property the request states outright — a column count, a continuous scroll, a comparison table, a vertical direction — is not a match however closely its wording fits.
8. "confidence" reflects how well the chosen candidate satisfies the request; below 0.6 the pattern is discarded.';
		}

		/**
		 * Build the user message for pattern selection.
		 *
		 * @param string $prompt     Natural language user prompt.
		 * @param string $block_name Target block name.
		 * @param string $candidates Pipe-delimited candidate lines.
		 * @return string User prompt string.
		 */
		public static function build_pattern_selection_message( $prompt, $block_name, $candidates ) {
			$message  = "Requested Block: {$block_name} (a default unless the request names another block)\n";
			$message .= "User Request: \"{$prompt}\"\n\n";
			$message .= "Candidate Patterns:\n{$candidates}\n\n";
			$message .= 'Return the selection JSON:';

			return $message;
		}

		/**
		 * Build the user prompt with block context.
		 *
		 * On a refine, the model only sees today's value for the options its own
		 * catalog covers — not the block's full attribute tree. Everything outside
		 * that (the logos, advanced CSS, scheduling, visibility) is preserved by
		 * `AISchemaValidator::sanitize_and_merge()` on the PHP side regardless of
		 * whether the model ever saw it, so showing it here would only cost tokens.
		 *
		 * @param string $prompt              Natural language user prompt.
		 * @param string $block_name          Target block name.
		 * @param array  $existing_attributes Existing block attributes if updating.
		 * @return string User prompt string.
		 */
		public static function build_user_message( $prompt, $block_name = 'sp-logo-carousel/carousel', array $existing_attributes = array() ) {
			$message  = "Target Block: {$block_name}\n";
			$message .= "User Request: \"{$prompt}\"\n";

			$relevant_keys  = AICatalogBuilder::catalog_relevant_keys_for_block( $block_name );
			$current_values = array_intersect_key( $existing_attributes, array_flip( $relevant_keys ) );

			if ( ! empty( $current_values ) ) {
				$message .= 'Current Values (only for the options above — unlisted options already have a value, leave them alone): '
					. wp_json_encode( $current_values ) . "\n";
			}

			$message .= 'Generate updated attribute JSON, including only the keys you are changing plus anything the catalog requires alongside them:';

			return $message;
		}
	}
}
