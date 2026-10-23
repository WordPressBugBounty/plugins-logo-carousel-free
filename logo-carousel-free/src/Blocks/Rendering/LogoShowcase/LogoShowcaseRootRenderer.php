<?php
/**
 * Render logo showcase root markup.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoShowcase;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoShowcaseRootRenderer' ) ) {
	/**
	 * Render the outer root container.
	 */
	class LogoShowcaseRootRenderer {
		/**
		 * Render empty state.
		 *
		 * @param array<int, string> $classes Carousel classes.
		 * @param string             $attributes Root attributes.
		 * @return string
		 */
		public function render_empty_state( array $classes, $attributes = '' ) {
			return sprintf(
				'<div class="%1$s"%2$s><div class="sp-lcpro-logo-source-empty">%3$s</div></div>',
				$this->escape_attr( implode( ' ', $classes ) ),
				$attributes,
				function_exists( '\\esc_html__' ) ? \esc_html__( 'Choose logos to build your carousel.', 'logo-carousel-free' ) : $this->escape_html( 'Choose logos to build your carousel.' )
			);
		}

		/**
		 * Render populated root wrapper.
		 *
		 * @param array<int, string> $classes Carousel classes.
		 * @param string             $attributes Root attributes.
		 * @param string             $markup Item markup.
		 * @param string             $pagination_markup Pagination markup.
		 * @param string             $navigation_markup Navigation markup.
		 * @param string             $popups Popup markup.
		 * @param string             $frame_classes Extra classes for the nav frame wrapper.
		 * @param string             $frame_styles Inline style declarations for the frame wrapper.
		 * @param string             $external_pagination_markup Pagination rendered outside the clipped viewport (vertical mode).
		 * @param string             $item_list_class Class for the item `<ul>`. Defaults to the Swiper wrapper; non-Swiper blocks pass their own.
		 * @return string
		 */
		public function render( array $classes, $attributes, $markup, $pagination_markup, $navigation_markup, $popups, $frame_classes = '', $frame_styles = '', $external_pagination_markup = '', $item_list_class = 'sp-lcpro-logo-source__items swiper-wrapper' ) {
			$viewport = sprintf(
				'<div class="%1$s"%2$s><ul class="%6$s">%3$s</ul>%4$s%5$s</div>',
				$this->escape_attr( implode( ' ', $classes ) ),
				$attributes,
				$markup,
				$pagination_markup,
				$popups,
				$this->escape_attr( $item_list_class )
			);

			// No external chrome (arrows or out-of-viewport dots) → the clipped
			// viewport is the carousel root (unchanged markup).
			if ( '' === (string) $navigation_markup && '' === (string) $external_pagination_markup ) {
				return $viewport;
			}

			// External chrome present → wrap the clipped viewport in an outer frame.
			// Arrows sit in the frame gutter; vertical dots sit in a band below the
			// viewport — both outside the slide track so they never overlap logos.
			$frame_class = 'sp-lcpro-logo-carousel__frame';
			if ( '' !== (string) $frame_classes ) {
				$frame_class .= ' ' . $frame_classes;
			}

			return sprintf(
				'<div class="%1$s"%2$s>%3$s%4$s%5$s</div>',
				$this->escape_attr( $frame_class ),
				'' !== (string) $frame_styles ? ' style="' . $this->escape_attr( $frame_styles ) . '"' : '',
				$viewport,
				$external_pagination_markup,
				$navigation_markup
			);
		}

		/**
		 * Escape a string for use in HTML attributes.
		 *
		 * @param string $value Raw value.
		 * @return string
		 */
		private function escape_attr( $value ) {
			if ( function_exists( '\\esc_attr' ) ) {
				return \esc_attr( $value );
			}

			return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
		}

		/**
		 * Escape text for HTML output.
		 *
		 * @param string $value Raw text.
		 * @return string
		 */
		private function escape_html( $value ) {
			if ( function_exists( '\\esc_html' ) ) {
				return \esc_html( $value );
			}

			return htmlspecialchars( (string) $value, ENT_NOQUOTES, 'UTF-8' );
		}
	}
}
