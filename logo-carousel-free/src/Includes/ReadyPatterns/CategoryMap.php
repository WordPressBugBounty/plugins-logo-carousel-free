<?php
/**
 * Ready Patterns category → block mapping.
 *
 * @package ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns;

defined( 'ABSPATH' ) || exit;

/**
 * Maps demo-server category slugs to registered block names and display labels.
 *
 * Fallback resolver and label source only. Normalization never drops an item whose
 * category is missing here — a lookup table that decides which patterns exist is
 * how server-side terms silently vanished before. Explicit `blocks[]` on the item
 * wins; this map answers only when the source declares nothing.
 */
class CategoryMap {

	/**
	 * Category slug → block name.
	 *
	 * List, Marquee and Table stay mapped in the free plugin: their patterns are
	 * still listed (flagged `requiresPro` by the Normalizer) so the library can
	 * offer an Upgrade action for them.
	 *
	 * @var array<string, string>
	 */
	public const BLOCK_MAP = array(
		'carousel' => 'sp-logo-carousel/carousel',
		'marquee'  => 'sp-logo-carousel/marquee',
		'grid'     => 'sp-logo-carousel/grid',
		'list'     => 'sp-logo-carousel/list',
		'table'    => 'sp-logo-carousel/table',
	);

	/**
	 * Category slug → sidebar label.
	 *
	 * @var array<string, string>
	 */
	public const LABEL_MAP = array(
		'carousel' => 'Carousel',
		'marquee'  => 'Marquee',
		'grid'     => 'Grid',
		'list'     => 'List',
		'table'    => 'Table',
	);

	/**
	 * Sidebar category slugs in display order (excludes "all").
	 *
	 * @return string[]
	 */
	public static function sidebar_slugs(): array {
		return array_keys( self::BLOCK_MAP );
	}

	/**
	 * Resolve a block name from a source category slug.
	 *
	 * @param string $category_slug Source category slug.
	 * @return string|null Block name or null when unmapped.
	 */
	public static function block_name_for_category( string $category_slug ): ?string {
		return self::BLOCK_MAP[ $category_slug ] ?? null;
	}

	/**
	 * Resolve a category slug from a block name.
	 *
	 * A v2 item declares `blocks[]` and may omit `category` entirely; without this
	 * the block axis would show a zero badge for a pattern it plainly belongs to.
	 *
	 * @param string $block_name Registered block name.
	 * @return string Category slug, or an empty string when unmapped.
	 */
	public static function category_for_block_name( string $block_name ): string {
		foreach ( self::BLOCK_MAP as $category_slug => $mapped_block ) {
			if ( $mapped_block === $block_name ) {
				return $category_slug;
			}
		}

		return '';
	}

	/**
	 * Bare block slug from a block name (`sp-logo-carousel/marquee` → `marquee`).
	 *
	 * @param string $block_name Registered block name.
	 * @return string Slug, or an empty string for a block outside this plugin.
	 */
	public static function slug_for_block_name( string $block_name ): string {
		$prefix = 'sp-logo-carousel/';

		if ( 0 !== strpos( $block_name, $prefix ) ) {
			return '';
		}

		return substr( $block_name, strlen( $prefix ) );
	}

	/**
	 * Resolve a display label from a source category slug.
	 *
	 * @param string $category_slug Source category slug.
	 * @return string
	 */
	public static function label_for_category( string $category_slug ): string {
		return self::LABEL_MAP[ $category_slug ] ?? ucwords( str_replace( '-', ' ', $category_slug ) );
	}
}
