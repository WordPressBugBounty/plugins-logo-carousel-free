<?php
/**
 * Logo card config normalization.
 *
 * Reads the card fields (background, border, alignment) from the
 * `displayElements` branch — shared with Pro — with their defaults filled in.
 * The item renderer uses it to decide whether a logo sits in a card.
 *
 * @package Logo_Carousel_Free
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Sections\DisplayElements\DisplayElementsDefaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoCardConfig' ) ) {
	/**
	 * Normalize the logo card fields.
	 */
	class LogoCardConfig {
		/**
		 * Build the normalized card payload.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @return array{cardBackground: array<string, mixed>, border: array<string, string>, borderWidth: array<string, mixed>, alignment: string}
		 */
		public function normalize( array $attributes ) {
			$card = isset( $attributes['displayElements'] ) && is_array( $attributes['displayElements'] )
				? $attributes['displayElements']
				: array();

			$alignment = isset( $card['alignment'] ) && is_string( $card['alignment'] ) ? $card['alignment'] : '';

			return array(
				'cardBackground' => array_replace_recursive(
					DisplayElementsDefaults::get_background_defaults(),
					isset( $card['cardBackground'] ) && is_array( $card['cardBackground'] ) ? $card['cardBackground'] : array()
				),
				'border'         => array_replace(
					DisplayElementsDefaults::get_card_border_defaults(),
					isset( $card['border'] ) && is_array( $card['border'] ) ? $card['border'] : array()
				),
				'borderWidth'    => array_replace_recursive(
					DisplayElementsDefaults::responsive_box_defaults( 1 ),
					isset( $card['borderWidth'] ) && is_array( $card['borderWidth'] ) ? $card['borderWidth'] : array()
				),
				'alignment'      => in_array( $alignment, DisplayElementsDefaults::get_alignment_options(), true ) ? $alignment : 'left',
			);
		}
	}
}
