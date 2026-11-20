<?php
/**
 * Free 3D Carousel feature gate for render-time attributes.
 *
 * The free 3D Carousel is the Coverflow stage with Side Items, arrows, dots and
 * the Carousel tab's autoplay / speed / direction / loop / keyboard /
 * mousewheel / grab-cursor controls. Edge Fade, Slides to Scroll, Sliding
 * Effects and Ajax filter/search are Pro, and so are the Perspective and Ring
 * styles and the 3D Settings — those live on the `carousel3d` branch and the
 * free `Carousel3dConfig::normalize()` never reads them. The logo rules are
 * the Carousel's (`LogoFreeGate`). A block saved in Pro keeps every one of
 * those values — this runs on a render-time copy, never on the saved post —
 * and renders them neutrally.
 *
 * 1:1 mirror of `blocks/blocks/carousel3d/freeGate.js`.
 *
 * @package ShapedPlugin\LogoCarouselFree\Blocks\Rendering
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering;

defined( 'ABSPATH' ) || exit;

/**
 * Carousel3dFreeGate class.
 */
final class Carousel3dFreeGate {

	/**
	 * Neutralize every Pro-only 3D Carousel value on a render copy.
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
	 * Shared layout toggles reset to off.
	 *
	 * @return array<string, bool>
	 */
	private static function toggles_neutral() {
		return array(
			'ajaxLiveFilter' => false,
			'ajaxSearch'     => false,
		);
	}

	/**
	 * Per-profile carousel motion reset to the free behavior.
	 *
	 * @return array<string, mixed>
	 */
	private static function profile_neutral() {
		return array(
			'slideToScroll'  => array(
				'device' => array(
					'Desktop' => 1,
					'Tablet'  => 1,
					'Mobile'  => 1,
				),
			),
			'slidingEffects' => 'none',
		);
	}

	/**
	 * Layout is read shared-first / active profile first, then flat, so a
	 * neutral value goes into every place a reader may look.
	 *
	 * @param array<string, mixed> $layout Layout branch.
	 * @return array<string, mixed>
	 */
	private static function gate_layout( array $layout ) {
		$shared = isset( $layout['shared'] ) && is_array( $layout['shared'] ) ? $layout['shared'] : array();

		$layout = array_merge( $layout, self::toggles_neutral(), self::profile_neutral() );

		$layout['edgeFade']            = isset( $layout['edgeFade'] ) && is_array( $layout['edgeFade'] ) ? $layout['edgeFade'] : array();
		$layout['edgeFade']['enabled'] = false;

		$shared                        = array_merge( $shared, self::toggles_neutral() );
		$shared['edgeFade']            = isset( $shared['edgeFade'] ) && is_array( $shared['edgeFade'] ) ? $shared['edgeFade'] : array();
		$shared['edgeFade']['enabled'] = false;
		$layout['shared']              = $shared;

		$standard           = isset( $layout['standard'] ) && is_array( $layout['standard'] ) ? $layout['standard'] : array();
		$layout['standard'] = array_merge( $standard, self::profile_neutral() );

		return $layout;
	}
}
