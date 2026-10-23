<?php
/**
 * Free Carousel feature gate for render-time attributes.
 *
 * The free Carousel follows the Figma sidebar kit: carousel styles other than
 * Standard and Center, display orientation, partial slide view, variable width, edge
 * fade, Ajax filter/search, slide to scroll, sliding effects, logo hover
 * animations / custom colour / image filter / overlay, logo click actions
 * and random display order are Pro. Every arrow icon preset and SVG logo
 * uploads are free. A block saved in Pro
 * keeps every one of those values — this runs on a render-time copy, never on
 * the saved post — and renders them neutrally.
 *
 * 1:1 mirror of `blocks/blocks/carousel/freeGate.js`. The logo rules are
 * shared with Grid via `LogoFreeGate`.
 *
 * @package ShapedPlugin\LogoCarouselFree\Blocks\Rendering
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering;

defined( 'ABSPATH' ) || exit;

/**
 * CarouselFreeGate class.
 */
final class CarouselFreeGate {

	/**
	 * Carousel styles the free plugin offers.
	 *
	 * @var string[]
	 */
	const FREE_CAROUSEL_STYLES = array( 'standard', 'center' );

	/**
	 * Neutralize every Pro-only Carousel value on a render copy.
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
	 * Neutral shared-layout values.
	 *
	 * @return array<string, mixed>
	 */
	private static function shared_neutral() {
		return array(
			'displayOrientation' => 'horizontal',
			'partialSlideView'   => false,
			'variableWidth'      => false,
			'ajaxLiveFilter'     => false,
			'ajaxSearch'         => false,
		);
	}

	/**
	 * Neutral style-profile values (Standard and Center).
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
	 * Layout is read branch-first (`shared` / the style profile), then flat, so
	 * a neutral value goes into every place a reader may look.
	 *
	 * @param array<string, mixed> $layout Layout branch.
	 * @return array<string, mixed>
	 */
	private static function gate_layout( array $layout ) {
		$shared = isset( $layout['shared'] ) && is_array( $layout['shared'] ) ? $layout['shared'] : array();

		$style = isset( $layout['carouselStyle'] ) ? $layout['carouselStyle'] : 'standard';
		if ( ! in_array( $style, self::FREE_CAROUSEL_STYLES, true ) ) {
			$layout['carouselStyle'] = 'standard';
		}

		$layout = array_merge( $layout, self::shared_neutral(), self::profile_neutral() );

		$layout['edgeFade']            = isset( $layout['edgeFade'] ) && is_array( $layout['edgeFade'] ) ? $layout['edgeFade'] : array();
		$layout['edgeFade']['enabled'] = false;

		$shared                        = array_merge( $shared, self::shared_neutral() );
		$shared['edgeFade']            = isset( $shared['edgeFade'] ) && is_array( $shared['edgeFade'] ) ? $shared['edgeFade'] : array();
		$shared['edgeFade']['enabled'] = false;
		$layout['shared']              = $shared;

		foreach ( array( 'standard', 'center' ) as $profile_key ) {
			$profile                = isset( $layout[ $profile_key ] ) && is_array( $layout[ $profile_key ] ) ? $layout[ $profile_key ] : array();
			$layout[ $profile_key ] = array_merge( $profile, self::profile_neutral() );
		}

		return $layout;
	}
}
