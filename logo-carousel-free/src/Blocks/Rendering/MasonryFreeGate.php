<?php
/**
 * Free Masonry feature gate for render-time attributes.
 *
 * The free Masonry ships every Layouts-panel control Pro has (columns and the
 * two gaps). Pagination and Ajax filter/search are Pro, the same as on the
 * free Grid, and the logo rules are the Carousel's (`LogoFreeGate`). A block
 * saved in Pro keeps every one of those values — this runs on a render-time
 * copy, never on the saved post — and renders them neutrally.
 *
 * 1:1 mirror of `blocks/blocks/masonry/freeGate.js`.
 *
 * @package ShapedPlugin\LogoCarouselFree\Blocks\Rendering
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering;

defined( 'ABSPATH' ) || exit;

/**
 * MasonryFreeGate class.
 */
final class MasonryFreeGate {

	/**
	 * Neutralize every Pro-only Masonry value on a render copy.
	 *
	 * @param array $attributes Attributes (already module-gated).
	 * @return array Gated copy.
	 */
	public static function apply( array $attributes ): array {
		$attributes = LogoFreeGate::apply( $attributes );

		$layout = isset( $attributes['layout'] ) && is_array( $attributes['layout'] ) ? $attributes['layout'] : array();
		$shared = isset( $layout['shared'] ) && is_array( $layout['shared'] ) ? $layout['shared'] : array();

		// Layout is read shared-first, then flat, so the neutral value goes
		// into both places a reader may look.
		$layout           = array_merge( $layout, self::toggles_neutral() );
		$layout['shared'] = array_merge( $shared, self::toggles_neutral() );

		$attributes['layout'] = $layout;

		return $attributes;
	}

	/**
	 * Layout toggles reset to off.
	 *
	 * @return array<string, bool>
	 */
	private static function toggles_neutral() {
		return array(
			'pagination'     => false,
			'ajaxLiveFilter' => false,
			'ajaxSearch'     => false,
		);
	}
}
