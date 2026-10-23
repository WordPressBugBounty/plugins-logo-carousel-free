<?php
/**
 * Render one logo showcase item.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoShowcase;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoShowcaseItemRenderer' ) ) {
	/**
	 * Render one item row for the logo showcase.
	 */
	class LogoShowcaseItemRenderer {
		/**
		 * Render item markup.
		 *
		 * @param array<string, mixed> $item Logo item.
		 * @param array<string, mixed> $images_settings Normalized settings.
		 * @param string               $popup_id Popup ID for this item.
		 * @param array<string, mixed> $loading_config Loading configuration.
		 * @param array<string, mixed> $card_config Normalized logo card config (`LogoCardConfig`).
		 * @return string
		 */
		public function render( array $item, array $images_settings, $popup_id, array $loading_config, array $card_config = array() ) {
			$title        = isset( $item['title'] ) ? (string) $item['title'] : '';
			$link_url     = isset( $item['url'] ) ? (string) $item['url'] : '';
			$categories   = $this->resolve_categories( $item );
			// Runtime filters on the JSON array; `data-logo-category` keeps the
			// first value for one deprecation release (legacy single-value read).
			$categories_json = $this->encode_categories( $categories );
			$first_category  = isset( $categories[0] ) ? (string) $categories[0] : '';
			$tooltip_text = isset( $item['tooltipText'] ) ? (string) $item['tooltipText'] : '';
			$loading      = isset( $loading_config['loading'] ) ? (string) $loading_config['loading'] : 'eager';
			$fetchpriority = isset( $loading_config['fetchpriority'] ) ? (string) $loading_config['fetchpriority'] : '';
			$click_action = isset( $images_settings['clickAction'] ) ? (string) $images_settings['clickAction'] : 'none';
			$image_state  = ( new LogoShowcaseImageRenderer() )->render( $item, $loading, $fetchpriority );
			$image        = $image_state['markup'];

			if ( 'link' === $click_action && '' !== $link_url ) {
				if ( ! empty( $image_state['isMediaMissing'] ) || ! empty( $image_state['isPlaceholder'] ) ) {
					$image = sprintf(
						'<span class="sp-lcpro-logo-source__link sp-lcpro-logo-source__link--disabled">%1$s</span>',
						$image
					);
				} else {
					$image = sprintf(
						'<a class="sp-lcpro-logo-source__link" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						$this->escape_url( $link_url ),
						$image
					);
				}
			} elseif ( 'popup' === $click_action && '' !== $popup_id ) {
				if ( ! empty( $image_state['isMediaMissing'] ) || ! empty( $image_state['isPlaceholder'] ) ) {
					$image = sprintf(
						'<span class="sp-lcpro-logo-source__popup-trigger sp-lcpro-logo-source__popup-trigger--disabled" aria-label="%1$s">%2$s</span>',
						$this->escape_attr( $title ),
						$image
					);
				} else {
					$image = sprintf(
						'<button type="button" class="sp-lcpro-logo-source__popup-trigger" data-remodal-target="%1$s" aria-label="%2$s">%3$s</button>',
						$this->escape_attr( $popup_id ),
						$this->escape_attr( $title ),
						$image
					);
				}
			}

			$item_classes = array( 'sp-lcpro-logo-source__item', 'swiper-slide' );
			if ( ! empty( $image_state['isMediaMissing'] ) || ! empty( $image_state['isPlaceholder'] ) ) {
				$item_classes[] = 'sp-lcpro-logo-source__item--has-missing-media';
			}
			if ( ! empty( $image_state['needsAttention'] ) ) {
				$item_classes[] = 'sp-lcpro-logo-source__item--needs-attention';
			}

			// A configured card surface (background / border) wraps the image.
			$figure_classes = array( 'sp-lcpro-logo-source__figure' );
			$figure_content = $image;
			if ( $this->has_card_surface( $card_config ) ) {
				$figure_classes[] = 'sp-lcpro-logo-source__figure--has-display-elements';
				$figure_content   = $this->render_card( $image, $card_config );
			}

			return sprintf(
				'<li class="%1$s" data-logo-id="%2$s" data-logo-media-id="%3$s" data-logo-order="%4$s" data-logo-title="%5$s" data-logo-alt="%6$s" data-logo-url="%7$s" data-logo-rating="%8$s" data-logo-category="%9$s" data-logo-categories="%21$s" data-logo-tooltip="%10$s" data-logo-created-at="%11$s" data-logo-updated-at="%12$s" data-logo-missing="%13$s" data-logo-status="%14$s" data-sp-lcpro-loading="%15$s" data-sp-lcpro-first-visible="%16$s"%20$s><figure class="%17$s">%18$s%19$s</figure></li>',
				$this->escape_attr( implode( ' ', $item_classes ) ),
				$this->escape_attr( isset( $item['id'] ) ? (string) $item['id'] : '' ),
				$this->escape_attr( isset( $item['mediaId'] ) ? (string) $item['mediaId'] : '' ),
				$this->escape_attr( isset( $item['order'] ) ? (string) $item['order'] : '' ),
				$this->escape_attr( $title ),
				$this->escape_attr( isset( $image_state['altText'] ) ? (string) $image_state['altText'] : '' ),
				$this->escape_attr( $link_url ),
				$this->escape_attr( isset( $item['rating'] ) ? (string) $item['rating'] : '' ),
				$this->escape_attr( $first_category ),
				$this->escape_attr( $tooltip_text ),
				$this->escape_attr( isset( $item['createdAt'] ) ? (string) $item['createdAt'] : '' ),
				$this->escape_attr( isset( $item['updatedAt'] ) ? (string) $item['updatedAt'] : '' ),
				( ! empty( $image_state['isMediaMissing'] ) || ! empty( $image_state['isPlaceholder'] ) ) ? 'true' : 'false',
				$this->escape_attr( isset( $image_state['mediaStatus'] ) ? (string) $image_state['mediaStatus'] : '' ),
				$this->escape_attr( $loading ),
				! empty( $loading_config['is_first_visible'] ) ? 'true' : 'false',
				$this->escape_attr( implode( ' ', $figure_classes ) ),
				$figure_content,
				'',
				'',
				$this->escape_attr( $categories_json )
			);
		}

		/**
		 * Resolve an item's categories to a clean string array (legacy fallback).
		 *
		 * @param array<string, mixed> $item Normalized (or legacy) item.
		 * @return array<int, string>
		 */
		private function resolve_categories( array $item ) {
			if ( isset( $item['categories'] ) && is_array( $item['categories'] ) ) {
				return array_values(
					array_filter(
						array_map(
							static function ( $value ) {
								return is_string( $value ) ? $value : ( is_numeric( $value ) ? (string) $value : '' );
							},
							$item['categories']
						),
						static function ( $value ) {
							return '' !== $value;
						}
					)
				);
			}

			if ( isset( $item['category'] ) && is_string( $item['category'] ) && '' !== $item['category'] ) {
				return array( $item['category'] );
			}

			return array();
		}

		/**
		 * JSON-encode categories for the `data-logo-categories` attribute.
		 *
		 * @param array<int, string> $categories Categories.
		 * @return string
		 */
		private function encode_categories( array $categories ) {
			$json = function_exists( '\\wp_json_encode' ) ? \wp_json_encode( $categories ) : json_encode( $categories ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode

			if ( ! is_string( $json ) ) {
				$json = '[]';
			}

			return $json;
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
		 * Escape a URL for safe output.
		 *
		 * @param string $value Raw URL.
		 * @return string
		 */
		private function escape_url( $value ) {
			$value = trim( (string) $value );
			if ( '' === $value ) {
				return '';
			}

			if ( function_exists( '\\esc_url' ) ) {
				return \esc_url( $value );
			}

			return filter_var( $value, FILTER_VALIDATE_URL ) ? htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ) : '';
		}

		/**
		 * Whether the card has a visible surface (a background, or a border with width).
		 *
		 * @param array<string, mixed> $card_config Normalized logo card config.
		 * @return bool
		 */
		private function has_card_surface( array $card_config ) {
			$background = isset( $card_config['cardBackground'] ) && is_array( $card_config['cardBackground'] )
				? $card_config['cardBackground']
				: array();
			$normal_bg  = isset( $background['normal'] ) && is_array( $background['normal'] )
				? $background['normal']
				: array();
			$hover_bg   = isset( $background['hover'] ) && is_array( $background['hover'] )
				? $background['hover']
				: array();
			$has_bg     = '' !== trim( isset( $normal_bg['solid'] ) ? (string) $normal_bg['solid'] : '' )
				|| '' !== trim( isset( $hover_bg['solid'] ) ? (string) $hover_bg['solid'] : '' )
				|| ! empty( $normal_bg['gradient'] )
				|| ! empty( $hover_bg['gradient'] )
				|| ! empty( $normal_bg['image'] )
				|| ! empty( $hover_bg['image'] );

			$border    = isset( $card_config['border'] ) && is_array( $card_config['border'] )
				? $card_config['border']
				: array();
			$has_style = isset( $border['style'] ) && 'none' !== (string) $border['style'];

			$widths     = isset( $card_config['borderWidth']['device']['Desktop'] ) && is_array( $card_config['borderWidth']['device']['Desktop'] )
				? $card_config['borderWidth']['device']['Desktop']
				: array();
			$has_widths = ! empty(
				array_filter(
					$widths,
					static function ( $value ) {
						return (float) $value > 0;
					}
				)
			);

			return $has_bg || ( $has_style && $has_widths );
		}

		/**
		 * Wrap the image in the card. The `sp-lcpro-de` class names are shared
		 * with Pro, where the same wrapper also holds the Display Elements text.
		 *
		 * @param string               $image       Rendered image markup.
		 * @param array<string, mixed> $card_config Normalized logo card config.
		 * @return string
		 */
		private function render_card( $image, array $card_config ) {
			$alignment = isset( $card_config['alignment'] ) ? (string) $card_config['alignment'] : 'left';

			return sprintf(
				'<figcaption class="sp-lcpro-logo-source__meta sp-lcpro-de sp-lcpro-de--align-%1$s">%2$s</figcaption>',
				$this->escape_attr( $alignment ),
				$image
			);
		}
	}
}
