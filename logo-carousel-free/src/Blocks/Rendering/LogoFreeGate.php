<?php
/**
 * Free feature gate shared by the Carousel and Grid blocks.
 *
 * Logo hover animations / custom colour / image filter / overlay, the Popup
 * click action and random display order are Pro in every free block. The Logo
 * URL click action and SVG logo uploads are free and pass through untouched. A block saved in Pro
 * keeps every one of those values — this runs on a render-time copy, never on
 * the saved post — and renders them neutrally.
 * Each block's own gate (`CarouselFreeGate`, `GridFreeGate`) adds its layout
 * rules on top.
 *
 * 1:1 mirror of `blocks/blocks/shared/utils/logoFreeGate.js`.
 *
 * @package ShapedPlugin\LogoCarouselFree\Blocks\Rendering
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering;

defined( 'ABSPATH' ) || exit;

/**
 * LogoFreeGate class.
 */
final class LogoFreeGate {

	/**
	 * Click actions the free plugin renders: Disabled and Logo URL.
	 */
	const FREE_CLICK_ACTIONS = array( 'none', 'link' );

	/**
	 * Neutralize the Pro-only logo values on a render copy. Layout is left to
	 * the block's own gate.
	 *
	 * @param array $attributes Attributes (already module-gated).
	 * @return array Gated copy.
	 */
	public static function apply( array $attributes ): array {
		// The Popup click action and random display order are Pro; Logo URL
		// is free.
		$attributes['imagesSettings']                = isset( $attributes['imagesSettings'] ) && is_array( $attributes['imagesSettings'] ) ? $attributes['imagesSettings'] : array();
		$attributes['imagesSettings']['clickAction'] = self::resolve_click_action(
			isset( $attributes['imagesSettings']['clickAction'] ) ? $attributes['imagesSettings']['clickAction'] : 'none'
		);
		$attributes['imagesSettings']['randomOrder'] = false;

		foreach ( array( 'logoImagesStyle', 'logoImageStyle' ) as $style_key ) {
			if ( isset( $attributes[ $style_key ] ) && is_array( $attributes[ $style_key ] ) ) {
				$attributes[ $style_key ] = self::gate_logo_images_style( $attributes[ $style_key ] );
			}
		}

		return $attributes;
	}

	/**
	 * The click action the free plugin renders: Logo URL stays, Popup (Pro)
	 * and anything unknown render as Disabled.
	 *
	 * @param mixed $value Saved `imagesSettings.clickAction`.
	 * @return string `none` or `link`.
	 */
	public static function resolve_click_action( $value ) {
		return in_array( $value, self::FREE_CLICK_ACTIONS, true ) ? (string) $value : 'none';
	}

	/**
	 * Drop hover animation, custom colour, image filter and overlay.
	 *
	 * @param array<string, mixed> $style `logoImagesStyle` branch.
	 * @return array<string, mixed>
	 */
	private static function gate_logo_images_style( array $style ) {
		$style['hoverAnimation'] = 'none';

		foreach ( array( 'normal', 'hover' ) as $state ) {
			if ( ! isset( $style[ $state ] ) || ! is_array( $style[ $state ] ) ) {
				continue;
			}

			$style[ $state ]['customColorEnabled'] = false;
			$style[ $state ]['imageFilter']        = array(
				'blur'       => 0,
				'brightness' => 0,
				'contrast'   => 0,
				'saturation' => 0,
				'hue'        => 0,
			);

			if ( isset( $style[ $state ]['overlay'] ) && is_array( $style[ $state ]['overlay'] ) ) {
				$style[ $state ]['overlay']['opacity'] = 0;
			}
		}

		return $style;
	}
}
