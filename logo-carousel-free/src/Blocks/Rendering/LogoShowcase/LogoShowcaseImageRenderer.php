<?php
/**
 * Render logo showcase image and placeholder markup.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoShowcase;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoShowcaseImageRenderer' ) ) {
	/**
	 * Render logo image states.
	 */
	class LogoShowcaseImageRenderer {
		/**
		 * Render the image markup for an item.
		 *
		 * @param array<string, mixed> $item Logo item.
		 * @param string               $loading Loading strategy.
		 * @param string               $fetchpriority Fetch priority hint for the LCP image ('high' or '').
		 * @return array<string, mixed>
		 */
		public function render( array $item, $loading, $fetchpriority = '' ) {
			$title            = isset( $item['title'] ) ? (string) $item['title'] : '';
			$fallback_label   = function_exists( '__' ) ? __( 'Logo', 'logo-carousel-free' ) : 'Logo';
			$alt_text         = isset( $item['altText'] ) && '' !== $item['altText'] ? (string) $item['altText'] : ( '' !== $title ? $title : $fallback_label );
			$image_url        = isset( $item['imageUrl'] ) ? (string) $item['imageUrl'] : '';
			$image_srcset     = isset( $item['imageSrcset'] ) ? (string) $item['imageSrcset'] : '';
			$image_width      = isset( $item['imageWidth'] ) ? (int) $item['imageWidth'] : 0;
			$image_height     = isset( $item['imageHeight'] ) ? (int) $item['imageHeight'] : 0;
			$is_media_missing = ! empty( $item['isMediaMissing'] );
			$media_status     = isset( $item['mediaStatus'] ) ? (string) $item['mediaStatus'] : '';
			$needs_attention  = ! empty( $item['needsAttention'] );
			$is_placeholder   = ! empty( $item['isPlaceholder'] );
			$original_title   = isset( $item['originalTitle'] ) ? (string) $item['originalTitle'] : $title;

			// Defense in depth: an item can reach this renderer with a genuinely
			// empty URL even when no upstream missing-media flag was set (e.g. a
			// Source-layer regression, or hand-edited/legacy saved content) —
			// treat that identically to a known-missing attachment so a broken
			// `<img src="">` is never emitted.
			$is_missing = $is_media_missing || 'missing' === $media_status || $is_placeholder || '' === $image_url;

			$image_classes = array( 'sp-lcpro-logo-source__image' );

			if ( $is_missing ) {
				$image_classes[] = 'sp-lcpro-logo-source__image--missing';
				$image_classes[] = 'sp-lcpro-logo-source__image--placeholder';

				$missing_title = '' !== $original_title ? $original_title : $fallback_label;

				if ( function_exists( '__' ) ) {
					$alt_text = sprintf(
						/* translators: %s: logo title */
						__( 'Missing logo: %s', 'logo-carousel-free' ),
						$this->escape_attr( $missing_title )
					);
				} else {
					$alt_text = 'Missing logo: ' . $this->escape_attr( $missing_title );
				}
			}

			if ( $needs_attention ) {
				$image_classes[] = 'sp-lcpro-logo-source__image--needs-attention';
			}

			$image_class_attr = implode( ' ', $image_classes );

			if ( $is_missing ) {
				$image_markup = $this->render_missing_media_placeholder( $original_title, $image_class_attr, $needs_attention );
			} else {
				$dimension_attributes = '';
				if ( $image_width > 0 ) {
					$dimension_attributes .= ' width="' . $this->escape_attr( (string) $image_width ) . '"';
				}
				if ( $image_height > 0 ) {
					$dimension_attributes .= ' height="' . $this->escape_attr( (string) $image_height ) . '"';
				}

				// Responsive candidates: crisp on HiDPI, lighter on small screens.
				// "sizes" mirrors the responsive --sp-lcpro-slot-max-width breakpoints.
				if ( '' !== $image_srcset ) {
					$dimension_attributes .= ' srcset="' . $this->escape_attr( $image_srcset ) . '"';
					$dimension_attributes .= ' sizes="' . $this->escape_attr( $this->get_slot_sizes_attr() ) . '"';
				}

				// High-priority fetch for the LCP slide; eager alone does not prioritize.
				if ( 'high' === $fetchpriority ) {
					$dimension_attributes .= ' fetchpriority="high"';
				}

				$image_markup = sprintf(
					'<img class="%1$s" src="%2$s" alt="%3$s" loading="%4$s" decoding="async"%5$s />',
					$this->escape_attr( $image_class_attr ),
					$this->escape_url( $image_url ),
					$this->escape_attr( $alt_text ),
					$this->escape_attr( $loading ),
					$dimension_attributes
				);
			}

			return array(
				'markup'         => $image_markup,
				'altText'        => $alt_text,
				'isMediaMissing' => $is_missing,
				'needsAttention' => $needs_attention,
				'isPlaceholder'  => $is_placeholder,
				'mediaStatus'    => $media_status,
			);
		}

		/**
		 * Build the responsive "sizes" attribute mirroring the slot max-width breakpoints.
		 *
		 * Must stay in sync with the --sp-lcpro-slot-max-width breakpoints defined in
		 * src/Blocks/assets/css/slot-dimensions.css.
		 *
		 * @return string
		 */
		private function get_slot_sizes_attr() {
			return '(min-width: 1440px) 320px, (min-width: 1024px) 280px, (min-width: 768px) 240px, 200px';
		}

		/**
		 * Build shared missing-media placeholder markup for frontend rendering.
		 *
		 * @param string $title Placeholder display title.
		 * @param string $image_class_attr Shared image class attribute.
		 * @param bool   $needs_attention Whether attention styling should be shown.
		 * @return string
		 */
		private function render_missing_media_placeholder( $title, $image_class_attr, $needs_attention ) {
			$display_title       = '' !== (string) $title ? (string) $title : 'Logo';
			$placeholder_classes = array( 'sp-lcpro-missing-media-placeholder', $image_class_attr );

			if ( $needs_attention ) {
				$placeholder_classes[] = 'sp-lcpro-missing-media-attention';
			}

			$placeholder_class_attr = implode( ' ', array_filter( $placeholder_classes ) );
			$warning_overlay        = '';

			if ( $needs_attention ) {
				$warning_overlay = '<div class="sp-lcpro-media-warning-overlay" aria-hidden="true"><span class="sp-lcpro-media-warning-icon">⚠</span></div>';
			}

			return sprintf(
				'<div class="sp-lcpro-missing-media-container sp-lcpro-logo-source__missing-media" style="width:var(--sp-lcpro-slot-max-width, 200px);height:var(--sp-lcpro-slot-height, 52px)"><div class="%1$s" data-sp-lcpro-placeholder="true"><div class="sp-lcpro-missing-media-compact-card" role="img" aria-label="%2$s"><div class="sp-lcpro-missing-media-compact-card__icon" aria-hidden="true"><span class="dashicons dashicons-format-image"></span></div><div class="sp-lcpro-missing-media-compact-card__content"><span class="sp-lcpro-missing-media-compact-card__status">%3$s</span><span class="sp-lcpro-missing-media-compact-card__title" title="%4$s">%5$s</span></div></div>%6$s</div></div>',
				$this->escape_attr( $placeholder_class_attr ),
				/* translators: %s: logo title. */
				$this->escape_attr( sprintf( __( 'Missing logo: %s', 'logo-carousel-free' ), $display_title ) ),
				$this->escape_html( __( 'Image Missing', 'logo-carousel-free' ) ),
				$this->escape_attr( $display_title ),
				$this->escape_html( $this->truncate_text( $display_title, 18 ) ),
				$warning_overlay
			);
		}

		/**
		 * Truncate text for compact placeholder rendering.
		 *
		 * @param string $text Text to truncate.
		 * @param int    $max_length Maximum character length.
		 * @return string
		 */
		private function truncate_text( $text, $max_length = 20 ) {
			$text = (string) $text;

			if ( strlen( $text ) <= $max_length ) {
				return $text;
			}

			return substr( $text, 0, $max_length - 3 ) . '...';
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

			// Same `data:image/` allowance as `ResolvedImageAdapter::sanitize_url()`
			// — `esc_url()` also rejects the `data` scheme by default, which would
			// strip the missing-media placeholder SVG this renderer is meant to
			// display. `esc_attr()` is enough: the value only ever lands inside an
			// HTML attribute, never as markup.
			if ( 0 === strpos( $value, 'data:image/' ) ) {
				return function_exists( '\\esc_attr' ) ? \esc_attr( $value ) : htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
			}

			if ( function_exists( '\\esc_url' ) ) {
				return \esc_url( $value );
			}

			return filter_var( $value, FILTER_VALIDATE_URL ) ? htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ) : '';
		}
	}
}
