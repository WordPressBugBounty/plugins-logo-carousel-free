<?php
/**
 * AI Catalog Builder.
 *
 * Per-block attribute catalogs for the AI builder's attribute-generation path,
 * plus the block-name → Schema class map both the catalog and the validators
 * rely on.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\CarouselSchema;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\GridSchema;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AICatalogBuilder' ) ) {

	/**
	 * The AI-facing view of the block schemas.
	 *
	 * The full PHP attribute schemas (`src/Blocks/Schema/`) are the render-time
	 * source of truth and run hundreds of keys deep (typography, borders,
	 * shadows, per-device spacing) — sending
	 * that whole tree to the model would cost more tokens than the pattern-match
	 * path it falls back from. This catalog is a hand-curated, AI-facing subset:
	 * only the keys worth an AI decision, one shared base plus a small per-block
	 * delta, mirroring how `AIPatternMatcher::to_prompt_payload()` only ever
	 * shows the model a shortlist rather than the full Ready Patterns catalog.
	 *
	 * Layout is deliberately described **flat** (`layout.columns`, not
	 * `layout.standard.columns`). The real attribute is profile-shaped — a
	 * top-level style plus `layout.shared` plus one object per style — and
	 * teaching a model that shape costs tokens and invites it to write the wrong
	 * branch. `AILayoutProfileWriter` fans a flat answer back out to every branch
	 * `resolveLayoutProfiles()` reads, so the model never has to know.
	 */
	class AICatalogBuilder {

		/**
		 * Block slug → Schema class, shared with the validators so the generated
		 * attributes are checked against the block they were generated for.
		 * The free plugin ships only Carousel and Grid, so the AI builder can
		 * only ever produce those two blocks.
		 *
		 * @var array<string, string>
		 */
		const SCHEMA_MAP = array(
			'carousel' => CarouselSchema::class,
			'grid'     => GridSchema::class,
		);

		/**
		 * Fully-qualified block-name prefix for this plugin's blocks.
		 */
		const BLOCK_PREFIX = 'sp-logo-carousel/';

		/**
		 * Blocks whose schema carries the `navigation` and `pagination` groups.
		 *
		 * Only the Carousel runs a Swiper instance with arrows and dots; the Grid
		 * has no such attributes at all, so naming them would just be
		 * dropped by the schema-key filter in `AISchemaValidator`.
		 *
		 * @var string[]
		 */
		const NAV_PAGINATION_BLOCKS = array( 'carousel' );

		/**
		 * Value shapes referenced by more than one catalog line, spelled out once.
		 *
		 * Repeating the full responsive spacing/typography object on every line
		 * that takes one costs more tokens than the whole rest of the catalog;
		 * naming the shape once and referring to it keeps the prompt affordable
		 * while still giving the model the exact structure the schema expects.
		 */
		const SHAPES = 'Value shapes referenced below:
	- <count> = { "device": { "Desktop": int, "Tablet": int, "Mobile": int } }. Always set all three devices.
	- <size> = { "device": { "Desktop": int, "Tablet": int, "Mobile": int }, "unit": { "Desktop": "px", "Tablet": "px", "Mobile": "px" } }.
	- <spacing> = { "device": { "Desktop": { "top": int, "right": int, "bottom": int, "left": int } }, "unit": { "Desktop": "px" }, "allChange": bool }. Set "allChange": true when all four sides are equal. "Tablet"/"Mobile" entries under "device" are optional.
	- <color> = a CSS hex or rgba string, e.g. "#111827" or "rgba(17,24,39,0.85)".
	- <background> = { "style": "solid"|"gradient"|"transparent", "solid": <color>, "gradient": "linear-gradient(…)" }. Set "solid" only with "style": "solid".
	- <pair> = { "normal": <color>, "hover": <color> } — a resting colour and its hover counterpart.
	- <typography> = { "family": { "fontWeight": "300"|"400"|"500"|"600"|"700"|"800", "textTransform": "uppercase"|"capitalize"|"lowercase"|"none" }, "fontSize": <size>, "lineHeight": { "value": "1.5" }, "letterSpacing": { "value": 0, "unit": "px" } }. Never set "family.family" — the site theme owns the typeface.';

		/**
		 * Attributes every block in the family shares.
		 *
		 * `logoSource.items` is deliberately absent: the logos are the user's own
		 * media, never something a model invents. A build with no logos yet
		 * borrows a Ready Pattern's demo logos through `AIPatternLogos`.
		 */
		const BASE_CATALOG = '- "section": { "background": <background>, "padding": <spacing>, "margin": <spacing> } — the band the whole showcase sits in
	- "imagesSettings": { "aspectRatio": "original"|"1:1"|"4:3"|"3:4"|"3:2"|"2:3"|"16:9"|"9:16", "resolution": "full"|"large"|"medium"|"thumbnail", "lazyLoad": bool, "randomOrder": bool, "clickAction": "none"|"link" (open the logo\'s own URL) |"popup" (open the logo details in a lightbox) }
	- "logoImagesStyle": { "hoverAnimation": "none"|"scale-up"|"fade-scale"|"rotate"|"slide-up"|"flip-x"|"flip-y"|"border-ring"|"skew"|"bounce"|"spin-360"|"jelly"|"shake"|"float"|"pulse"|"swing"|"wobble"|"heartbeat", "margin": <spacing>, "normal": { "customColorEnabled": bool, "customColor": <color> (recolours a flat/opaque logo — only meaningful with "customColorEnabled": true), "opacity": 0-100, "imageFilter": { "blur": 0-20, "brightness": -100..100, "contrast": -100..100, "saturation": -100..100, "hue": -180..180 }, "overlay": { "type": "solid"|"gradient", "color": <color>, "opacity": 0-100 } }, "hover": { same shape as "normal" } } — a classic "greyscale logos that colour on hover" strip is "normal.imageFilter.saturation": -100 with "hover.imageFilter.saturation": 0
	- "displayElements": { "cardBackground": { "normal": <background>, "hover": <background> }, "border": { "style": "none"|"solid"|"dashed"|"dotted"|"double", "color": <color>, "hoverColor": <color> }, "borderWidth": <spacing>, "borderRadius": <spacing>, "innerPadding": <spacing>, "alignment": "left"|"center"|"right" } — the card drawn around each logo. Only the card: no text is shown with the logos';

		/**
		 * The `navigation` / `pagination` groups, Carousel only.
		 */
		const NAV_PAGINATION_CATALOG = '- "navigation": { "presetIcon": "arrow-01" … "arrow-12", "navStyle": "style-1" (arrows beside the track) | "style-2" (arrows over the track), "position": "center"|"center-outer"|"center-inner"|"top-left"|"top-center"|"top-right"|"bottom-left"|"bottom-center"|"bottom-right", "iconSize": <size>, "gapBetweenArrows": <size>, "showOnHover": bool, "hideOnMobile": bool, "iconColor": <pair>, "background": { "normal": <background>, "hover": <background> }, "border": { "style": "none"|"solid"|"dashed"|"dotted"|"double", "color": <color>, "hoverColor": <color> }, "borderRadius": <spacing>, "innerPadding": <spacing> } — prev/next arrow styling. Turning the arrows on or off is "layout.navigationArrows", not this group
	- "pagination": { "paginationStyle": "style-1"|"style-2"|"style-3" (dots) |"style-4" (dashes) |"style-5" (scrollbar) |"style-6" (numbers), "width": <size>, "height": <size>, "gapBetweenDots": <size>, "hideOnMobile": bool, "backgroundColor": { "normal": <color>, "active": <color> }, "textColor": { "normal": <color>, "active": <color> }, "borderRadius": <spacing>, "margin": <spacing> } — bullet styling. Turning the bullets on or off is "layout.paginationDots", not this group';

		/**
		 * Per-block layout deltas. Written flat; `AILayoutProfileWriter` fans the
		 * answer out to the branches the renderer reads.
		 *
		 * @var array<string, string>
		 */
		const BLOCK_DELTAS = array(
			'carousel' => '- "layout": { "carouselStyle": "standard"|"center" (the middle logo is scaled up and its neighbours faded), "columns": <count> (logos visible across), "rows": <count> (logos visible down a vertical carousel), "gapBetweenLogos": <size>, "displayOrientation": "horizontal"|"vertical", "alignment": { "horizontal": "flex-start"|"center"|"flex-end", "vertical": "flex-start"|"center"|"flex-end" }, "autoplay": bool, "autoplayDelay": int ms (500-15000), "sliderSpeed": int ms (100-5000), "pauseOnHover": bool, "infiniteLoop": bool, "adaptiveHeight": bool, "navigationArrows": bool, "paginationDots": bool, "partialSlideView": bool (peek at the next logo), "variableWidth": bool (each logo keeps its natural width), "carouselDirection": "left-to-right"|"right-to-left", "slideToScroll": <count>, "slidingEffects": "none"|"slide"|"ease-in-out-slide"|"spring-bounce"|"snappy"|"fade"|"slide-fade", "tabsAndKeyNavigation": bool, "mouseWheelControl": bool, "freeScrollMode": bool, "edgeFade": { "enabled": bool, "strength": 0-100, "width": 0-400, "colorMode": "auto"|"custom", "color": <color> } (fades the track\'s left/right edges), "centeredLogoScale": 1-2 and "slideLogosOpacity": 0-1 (only read when "carouselStyle" is "center") }',
			'grid'     => '- "layout": { "gridStyle": "standard", "columns": <count>, "horizontalGap": <size>, "verticalGap": <size>, "justify": "flex-start"|"center"|"flex-end"|"space-between"|"space-around"|"space-evenly", "alignment": "start"|"center"|"end"|"stretch" }
		- This block is a static grid, not a slider: it has no autoplay, speed, arrows or bullets. Ignore any request for those rather than inventing keys for them.',
		);

		/**
		 * Resolve the Schema class that owns a block's attributes.
		 *
		 * @param string $block_name Block slug, with or without the `sp-logo-carousel/` prefix.
		 * @return string Fully-qualified schema class name.
		 */
		public static function schema_class_for_block( $block_name ) {
			$slug = self::slug( $block_name );

			return isset( self::SCHEMA_MAP[ $slug ] ) ? self::SCHEMA_MAP[ $slug ] : CarouselSchema::class;
		}

		/**
		 * Strip the namespace prefix off a block name.
		 *
		 * @param string $block_name Block slug, with or without the prefix.
		 * @return string Bare slug.
		 */
		public static function slug( $block_name ) {
			return str_replace( self::BLOCK_PREFIX, '', (string) $block_name );
		}

		/**
		 * Build the AI-facing attribute catalog for one block.
		 *
		 * @param string $block_name Block slug, with or without the prefix.
		 * @return string Catalog lines, one attribute group per line.
		 */
		public static function get_catalog( $block_name ) {
			$slug = self::slug( $block_name );
			if ( ! isset( self::SCHEMA_MAP[ $slug ] ) ) {
				$slug = 'carousel';
			}

			$catalog = self::SHAPES . "\n\n" . self::BLOCK_DELTAS[ $slug ] . "\n" . self::BASE_CATALOG;

			if ( in_array( $slug, self::NAV_PAGINATION_BLOCKS, true ) ) {
				$catalog .= "\n" . self::NAV_PAGINATION_CATALOG;
			}

			return $catalog;
		}

		/**
		 * Top-level attribute keys a block's catalog actually names.
		 *
		 * Derived from the same catalog text the model reads, so it can never
		 * drift out of sync with it the way a hand-maintained key list would.
		 * Used to trim what `AIPromptBuilder` echoes back as "current values" on a
		 * refine — the model only needs today's value for options it might
		 * change, not the block's entire attribute tree.
		 *
		 * @param string $block_name Block slug, with or without the prefix.
		 * @return string[] Deduplicated top-level keys.
		 */
		public static function catalog_relevant_keys_for_block( $block_name ) {
			$catalog = self::get_catalog( $block_name );

			// Continuation lines of the multi-line catalog constants carry a
			// leading tab (the file's established indentation convention), so a
			// line doesn't necessarily start at column 0.
			preg_match_all( '/^[ \t]*- "([a-zA-Z0-9_]+)"/m', $catalog, $matches );

			return array_values( array_unique( $matches[1] ) );
		}

		/**
		 * Raw default value per top-level attribute key for one block.
		 *
		 * @param string $block_name Block slug, with or without the prefix.
		 * @return array<string, mixed>
		 */
		public static function schema_defaults( $block_name ) {
			$schema_class = self::schema_class_for_block( $block_name );
			$schema       = new $schema_class();

			$defaults = array();
			foreach ( $schema->get_attributes() as $key => $definition ) {
				if ( is_array( $definition ) && array_key_exists( 'default', $definition ) ) {
					$defaults[ $key ] = $definition['default'];
				}
			}

			return $defaults;
		}
	}
}
