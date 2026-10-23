<?php
/**
 * Free Grid feature gate for render-time attributes.
 *
 * The free Grid follows the Figma Grid sidebar kit: Standard Grid is the free
 * grid style (Grid Strip, Staggered and Zigzag are Pro), Left and Right are the
 * free justify styles (Center is Pro), and Pagination and Ajax filter/search
 * are Pro. The logo rules are the Carousel's (`LogoFreeGate`). A block saved in Pro keeps every one of those values —
 * this runs on a render-time copy, never on the saved post — and renders them
 * neutrally.
 *
 * 1:1 mirror of `blocks/blocks/grid/freeGate.js`.
 *
 * @package ShapedPlugin\LogoCarouselFree\Blocks\Rendering
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering;

defined( 'ABSPATH' ) || exit;

/**
 * GridFreeGate class.
 */
final class GridFreeGate {

	/**
	 * Grid styles the free plugin offers.
	 *
	 * @var string[]
	 */
	const FREE_GRID_STYLES = array( 'standard' );

	/**
	 * Justify styles the free plugin offers (Figma: Left, Right).
	 *
	 * @var string[]
	 */
	const FREE_GRID_JUSTIFY = array( 'start', 'end' );

	/**
	 * Justify a Pro (or unset) value renders as.
	 *
	 * @var string
	 */
	const DEFAULT_FREE_GRID_JUSTIFY = 'start';

	/**
	 * Neutralize every Pro-only Grid value on a render copy.
	 *
	 * @param array $attributes Attributes (already module-gated).
	 * @return array Gated copy.
	 */
	public static function apply( array $attributes ): array {
		$attributes = LogoFreeGate::apply( $attributes );

		$attributes['layout'] = self::gate_layout(
			isset( $attributes['layout'] ) && is_array( $attributes['layout'] ) ? $attributes['layout'] : array()
		);

		return $attributes;
	}

	/**
	 * Layout toggles reset to off, on the shared branch and flat.
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

	/**
	 * Justify as the renderers read it — shared first, then flat, then the
	 * schema's `center` — mapped onto a free justify style.
	 *
	 * @param array<string, mixed> $layout Layout branch.
	 * @param array<string, mixed> $shared Shared layout branch.
	 * @return string `start` or `end`.
	 */
	private static function resolve_justify( array $layout, array $shared ) {
		$justify = 'center';

		if ( isset( $shared['justify'] ) && '' !== $shared['justify'] ) {
			$justify = $shared['justify'];
		} elseif ( isset( $layout['justify'] ) && '' !== $layout['justify'] ) {
			$justify = $layout['justify'];
		}

		return in_array( $justify, self::FREE_GRID_JUSTIFY, true ) ? $justify : self::DEFAULT_FREE_GRID_JUSTIFY;
	}

	/**
	 * Layout is read shared-first, then flat, so a neutral value goes into both
	 * places a reader may look.
	 *
	 * @param array<string, mixed> $layout Layout branch.
	 * @return array<string, mixed>
	 */
	private static function gate_layout( array $layout ) {
		$shared  = isset( $layout['shared'] ) && is_array( $layout['shared'] ) ? $layout['shared'] : array();
		$justify = self::resolve_justify( $layout, $shared );

		$style = isset( $layout['gridStyle'] ) ? $layout['gridStyle'] : 'standard';
		if ( ! in_array( $style, self::FREE_GRID_STYLES, true ) ) {
			$layout['gridStyle'] = 'standard';
		}

		$layout            = array_merge( $layout, self::toggles_neutral() );
		$layout['justify'] = $justify;

		$shared            = array_merge( $shared, self::toggles_neutral() );
		$shared['justify'] = $justify;
		$layout['shared']  = $shared;

		return $layout;
	}
}
