<?php
/**
 * Read-only logo popup renderer.
 *
 * Layout mirrors the Logo Details modal design: a header, a two-column body
 * (image + file meta + rating on the left, title, categories, detail rows and
 * description on the right) and a footer holding the Visit Link button.
 *
 * Every section is conditional. A section whose data is missing is not rendered
 * at all — no empty label, no empty card, no stray separator — and the whole
 * popup collapses to '' when there is nothing to show.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering;

use ShapedPlugin\LogoCarouselFree\Blocks\Support\RatingStars;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoInfoPopupRenderer' ) ) {
	/**
	 * Render a read-only logo details popup.
	 */
	class LogoInfoPopupRenderer {
		/**
		 * Render one popup instance.
		 *
		 * @param array<string, mixed>  $item Logo item.
		 * @param string                $popup_id Popup instance ID.
		 * @param string                $unique_id Block unique ID.
		 * @param array<string, string> $neighbours Optional sibling popup IDs
		 *                                          (`prev` / `next`) that the
		 *                                          header arrows step to. Omitted
		 *                                          — or empty — renders no arrows,
		 *                                          which is what a single-logo
		 *                                          block wants.
		 * @return string
		 */
		public function render( array $item, $popup_id, $unique_id, array $neighbours = array() ) {
			$title       = $this->display_text( isset( $item['title'] ) ? $item['title'] : '' );
			$alt_text    = $this->display_text( isset( $item['altText'] ) && '' !== $item['altText'] ? $item['altText'] : $title );
			$tagline     = $this->display_text( isset( $item['tagline'] ) ? $item['tagline'] : '' );
			$caption     = $this->display_text( isset( $item['caption'] ) ? $item['caption'] : '' );
			$description = $this->display_text( isset( $item['description'] ) ? $item['description'] : '' );
			$tooltip     = $this->display_text( isset( $item['tooltipText'] ) ? $item['tooltipText'] : '' );
			$website     = isset( $item['url'] ) ? trim( (string) $item['url'] ) : '';
			$rating      = isset( $item['rating'] ) ? $item['rating'] : null;
			$additional  = isset( $item['additionalInfo'] ) && is_array( $item['additionalInfo'] ) ? $item['additionalInfo'] : array();
			$image_url   = isset( $item['imageUrl'] ) ? (string) $item['imageUrl'] : '';

			$is_media_missing = ! empty( $item['isMediaMissing'] );
			$media_status     = isset( $item['mediaStatus'] ) ? (string) $item['mediaStatus'] : '';
			$needs_attention  = ! empty( $item['needsAttention'] );
			$is_placeholder   = ! empty( $item['isPlaceholder'] );
			$original_title   = $this->display_text( isset( $item['originalTitle'] ) ? $item['originalTitle'] : $title );

			$rows = $this->render_detail_rows(
				array(
					array(
						'label' => __( 'Caption', 'logo-carousel-free' ),
						'value' => $caption,
					),
					array(
						'label' => __( 'Short Tagline', 'logo-carousel-free' ),
						'value' => $tagline,
					),
					array(
						'label' => __( 'Custom Tooltip Text', 'logo-carousel-free' ),
						'value' => $tooltip,
					),
					array(
						'label' => __( 'Image Alt Text', 'logo-carousel-free' ),
						'value' => $alt_text,
					),
					array(
						'label' => __( 'URL', 'logo-carousel-free' ),
						'value' => $website,
						'link'  => true,
					),
				)
			);

			$content = $this->render_title( $title )
				. $this->render_tags( $this->categories_list( $item ) )
				. $rows
				. $this->render_description( $description );

			$preview = $this->render_image(
				$image_url,
				$alt_text,
				$original_title,
				array(
					'missing'         => $is_media_missing || 'missing' === $media_status || $is_placeholder,
					'needs_attention' => $needs_attention,
				)
			)
				. $this->render_meta_list( $additional )
				. $this->render_rating_card( $rating );

			if ( '' === $preview && '' === $content ) {
				return '';
			}

			$grid_classes = 'sp-lcpro-logo-info-popup__grid';
			if ( '' === $preview ) {
				$grid_classes .= ' sp-lcpro-logo-info-popup__grid--no-preview';
			}

			return sprintf(
				'<div class="remodal sp-lcpro-logo-info-popup sp-lcpro-logo-info-popup--%1$s" data-remodal-id="%2$s"><div class="sp-lcpro-logo-info-popup__dialog"><div class="sp-lcpro-logo-info-popup__header"><p class="sp-lcpro-logo-info-popup__eyebrow">%3$s</p><button type="button" data-remodal-action="close" class="remodal-close sp-lcpro-logo-info-popup__close" aria-label="%4$s">&times;</button></div><div class="%5$s">%6$s%7$s</div>%8$s</div>%9$s</div>',
				$this->escape_attr( $this->sanitize_token( $unique_id ) ),
				$this->escape_attr( $popup_id ),
				$this->escape_html( __( 'Logo Details', 'logo-carousel-free' ) ),
				$this->escape_attr( __( 'Close popup', 'logo-carousel-free' ) ),
				$this->escape_attr( $grid_classes ),
				'' !== $preview ? '<div class="sp-lcpro-logo-info-popup__preview">' . $preview . '</div>' : '',
				'' !== $content ? '<div class="sp-lcpro-logo-info-popup__content">' . $content . '</div>' : '',
				$this->render_footer( $website ),
				$this->render_nav( $neighbours )
			);
		}

		/**
		 * Render the prev/next arrows that step between sibling popups.
		 *
		 * They sit on the overlay, outside the dialog and vertically centred on
		 * it — hence siblings of `__dialog`, not header buttons: the dialog
		 * scrolls its own overflow and would clip anything placed inside it.
		 *
		 * Purely additive: with no sibling IDs nothing is emitted, so a block
		 * showing a single logo renders exactly the markup it did before.
		 *
		 * @param array<string, string> $neighbours Sibling popup IDs.
		 * @return string
		 */
		private function render_nav( array $neighbours ) {
			$prev = isset( $neighbours['prev'] ) ? trim( (string) $neighbours['prev'] ) : '';
			$next = isset( $neighbours['next'] ) ? trim( (string) $neighbours['next'] ) : '';

			if ( '' === $prev && '' === $next ) {
				return '';
			}

			$buttons = '';

			if ( '' !== $prev ) {
				$buttons .= $this->render_nav_button( 'prev', $prev, __( 'Previous logo', 'logo-carousel-free' ) );
			}

			if ( '' !== $next ) {
				$buttons .= $this->render_nav_button( 'next', $next, __( 'Next logo', 'logo-carousel-free' ) );
			}

			return $buttons;
		}

		/**
		 * Render one arrow button.
		 *
		 * The target popup ID travels on the button; the frontend runtime closes
		 * this popup and opens that one. Without JS the button simply does
		 * nothing — it never navigates away or submits.
		 *
		 * @param string $direction 'prev' or 'next'.
		 * @param string $target_id Sibling popup ID.
		 * @param string $label Accessible label.
		 * @return string
		 */
		private function render_nav_button( $direction, $target_id, $label ) {
			$path = 'prev' === $direction
				? 'M11.5 2L3 12l8.5 10'
				: 'M2.5 2L11 12l-8.5 10';

			return sprintf(
				'<button type="button" class="sp-lcpro-logo-info-popup__nav sp-lcpro-logo-info-popup__nav--%1$s" data-sp-lcpro-popup-target="%2$s" aria-label="%3$s"><svg width="14" height="24" viewBox="0 0 14 24" fill="none" aria-hidden="true" focusable="false"><path d="%4$s" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>',
				$this->escape_attr( $direction ),
				$this->escape_attr( $target_id ),
				$this->escape_attr( $label ),
				$this->escape_attr( $path )
			);
		}

		/**
		 * Render the logo title heading.
		 *
		 * @param string $title Display title.
		 * @return string
		 */
		private function render_title( $title ) {
			if ( '' === $title ) {
				return '';
			}

			return '<h3 class="sp-lcpro-logo-info-popup__title">' . $this->escape_html( $title ) . '</h3>';
		}

		/**
		 * Render the category chips.
		 *
		 * @param array<int, string> $categories Display-formatted categories.
		 * @return string
		 */
		private function render_tags( array $categories ) {
			if ( empty( $categories ) ) {
				return '';
			}

			$chips = '';
			foreach ( $categories as $category ) {
				$chips .= '<span class="sp-lcpro-logo-info-popup__tag">' . $this->escape_html( $category ) . '</span>';
			}

			return '<div class="sp-lcpro-logo-info-popup__tags">' . $chips . '</div>';
		}

		/**
		 * Render the label/value detail rows.
		 *
		 * @param array<int, array<string, mixed>> $fields Field definitions.
		 * @return string
		 */
		private function render_detail_rows( array $fields ) {
			$output = '';

			foreach ( $fields as $field ) {
				if ( empty( $field['value'] ) ) {
					continue;
				}

				$output .= $this->render_detail_row(
					(string) $field['label'],
					$field['value'],
					! empty( $field['link'] )
				);
			}

			if ( '' === $output ) {
				return '';
			}

			return '<div class="sp-lcpro-logo-info-popup__details-stack">' . $output . '</div>';
		}

		/**
		 * Render one read-only detail row.
		 *
		 * @param string $label Field label.
		 * @param mixed  $value Field value.
		 * @param bool   $is_link Whether to render the value as a link.
		 * @return string
		 */
		private function render_detail_row( $label, $value, $is_link = false ) {
			$display_value = $this->display_text( $value );
			if ( '' === $display_value ) {
				return '';
			}

			$value_markup = $is_link
				? sprintf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
					$this->escape_url( (string) $value ),
					$this->escape_html( $display_value )
				)
				: '<span>' . $this->escape_html( $display_value ) . '</span>';

			return sprintf(
				'<div class="sp-lcpro-logo-info-popup__detail"><span class="sp-lcpro-logo-info-popup__detail-label">%1$s</span><div class="sp-lcpro-logo-info-popup__detail-value">%2$s</div></div>',
				$this->escape_html( $label ),
				$value_markup
			);
		}

		/**
		 * Render the description block.
		 *
		 * @param string $description Display description.
		 * @return string
		 */
		private function render_description( $description ) {
			if ( '' === $description ) {
				return '';
			}

			return sprintf(
				'<div class="sp-lcpro-logo-info-popup__description"><h4 class="sp-lcpro-logo-info-popup__description-title">%1$s</h4><p class="sp-lcpro-logo-info-popup__description-text">%2$s</p></div>',
				$this->escape_html( __( 'Logo / Company Description', 'logo-carousel-free' ) ),
				$this->escape_html( $description )
			);
		}

		/**
		 * Render the logo image frame.
		 *
		 * @param string               $image_url Image URL.
		 * @param string               $alt_text Alt text.
		 * @param string               $original_title Title used in the missing-media alt.
		 * @param array<string, bool>  $flags Missing-media flags.
		 * @return string
		 */
		private function render_image( $image_url, $alt_text, $original_title, array $flags ) {
			if ( '' === $image_url ) {
				return '';
			}

			$frame_classes = array( 'sp-lcpro-logo-info-popup__image-frame' );
			$image_classes = array();

			if ( ! empty( $flags['missing'] ) ) {
				$frame_classes[] = 'sp-lcpro-logo-info-popup__image-frame--missing';
				$image_classes[] = 'sp-lcpro-logo-source__image--missing';
				$image_classes[] = 'sp-lcpro-logo-source__image--placeholder';
				$alt_text        = sprintf(
					/* translators: %s: logo title */
					__( 'Missing logo: %s', 'logo-carousel-free' ),
					$original_title
				);
			}

			if ( ! empty( $flags['needs_attention'] ) ) {
				$frame_classes[] = 'sp-lcpro-logo-info-popup__image-frame--needs-attention';
			}

			return sprintf(
				'<div class="%1$s"><img src="%2$s" alt="%3$s"%4$s /></div>',
				$this->escape_attr( implode( ' ', $frame_classes ) ),
				$this->escape_url( $image_url ),
				$this->escape_attr( $alt_text ),
				! empty( $image_classes ) ? ' class="' . $this->escape_attr( implode( ' ', $image_classes ) ) . '" data-sp-lcpro-placeholder="true"' : ''
			);
		}

		/**
		 * Render the file meta list (type, size, dimensions).
		 *
		 * Values are printed as stored — a MIME type is not a sentence, so it is
		 * not run through display_text's capitalization.
		 *
		 * @param array<string, mixed> $info Media info.
		 * @return string
		 */
		private function render_meta_list( array $info ) {
			$mime_type  = isset( $info['mimeType'] ) ? trim( (string) $info['mimeType'] ) : '';
			$file_size  = isset( $info['fileSize'] ) ? trim( (string) $info['fileSize'] ) : '';
			$dimensions = ( ! empty( $info['width'] ) && ! empty( $info['height'] ) )
				? sprintf( '%1$s × %2$s px', (string) $info['width'], (string) $info['height'] )
				: '';

			$rows = '';
			foreach (
				array(
					array( __( 'File type', 'logo-carousel-free' ), $mime_type ),
					array( __( 'File size', 'logo-carousel-free' ), $file_size ),
					array( __( 'Dimensions', 'logo-carousel-free' ), $dimensions ),
				) as $row
			) {
				if ( '' === $row[1] ) {
					continue;
				}

				$rows .= sprintf(
					'<div class="sp-lcpro-logo-info-popup__meta-row"><span class="sp-lcpro-logo-info-popup__meta-label">%1$s</span><span class="sp-lcpro-logo-info-popup__meta-value">%2$s</span></div>',
					$this->escape_html( $row[0] ),
					$this->escape_html( $row[1] )
				);
			}

			if ( '' === $rows ) {
				return '';
			}

			return '<div class="sp-lcpro-logo-info-popup__meta">' . $rows . '</div>';
		}

		/**
		 * Render the rating card.
		 *
		 * @param mixed $rating Rating value.
		 * @return string
		 */
		private function render_rating_card( $rating ) {
			// Half-step states come from the shared `RatingStars` model, the
			// same one the card and the tooltip paint from — the popup is
			// opened FROM a card, so the two disagreeing is a visible bug.
			$resolved = RatingStars::resolve( $rating );

			if ( null === $resolved ) {
				return '';
			}

			$stars = '';
			foreach ( $resolved['states'] as $state ) {
				$stars .= sprintf(
					// `is-active` is kept for backward compatibility with any CSS
					// still targeting it; the state class drives the paint.
					'<span class="sp-lcpro-logo-info-popup__star sp-lcpro-logo-info-popup__star--%1$s%2$s" aria-hidden="true">★</span>',
					$state,
					'empty' === $state ? '' : ' is-active'
				);
			}

			return sprintf(
				'<div class="sp-lcpro-logo-info-popup__rating-card"><div class="sp-lcpro-logo-info-popup__rating-heading">%1$s</div><div class="sp-lcpro-logo-info-popup__rating"><div class="sp-lcpro-logo-info-popup__rating-stars" data-rating="%4$s" role="img" aria-label="%5$s">%2$s</div><span class="sp-lcpro-logo-info-popup__rating-value">%3$s</span></div></div>',
				$this->escape_html( __( 'Average Rating', 'logo-carousel-free' ) ),
				$stars,
				$this->escape_html( $resolved['text'] . ' / 5' ),
				$this->escape_attr( $resolved['display'] ),
				$this->escape_attr( $resolved['label'] )
			);
		}

		/**
		 * Render the footer holding the Visit Link button.
		 *
		 * The footer exists only for a logo that actually has a link, so a logo
		 * without one shows no empty bar.
		 *
		 * @param string $website_link Logo link.
		 * @return string
		 */
		private function render_footer( $website_link ) {
			$url = $this->escape_url( $website_link );

			if ( '' === $url ) {
				return '';
			}

			return sprintf(
				'<div class="sp-lcpro-logo-info-popup__footer"><a class="sp-lcpro-logo-info-popup__visit" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></div>',
				$url,
				$this->escape_html( __( 'Visit Link', 'logo-carousel-free' ) )
			);
		}

		/**
		 * Build the display-formatted category list.
		 *
		 * Reads the multi-category `categories` array (legacy single `category`
		 * string as fallback).
		 *
		 * @param array<string, mixed> $item Logo item.
		 * @return array<int, string>
		 */
		private function categories_list( array $item ) {
			$categories = array();

			if ( isset( $item['categories'] ) && is_array( $item['categories'] ) ) {
				$categories = $item['categories'];
			} elseif ( isset( $item['category'] ) && is_string( $item['category'] ) && '' !== $item['category'] ) {
				$categories = array( $item['category'] );
			}

			$formatted = array();
			foreach ( $categories as $category ) {
				$value = $this->display_text( $category );
				if ( '' !== $value ) {
					$formatted[] = $value;
				}
			}

			return $formatted;
		}

		/**
		 * Display-only text formatting.
		 *
		 * @param mixed $value Raw value.
		 * @return string
		 */
		private function display_text( $value ) {
			if ( null === $value || '' === $value ) {
				return '';
			}

			$text = trim( (string) $value );
			if ( '' === $text ) {
				return '';
			}

			if ( preg_match( '/^https?:\/\//i', $text ) || preg_match( '/^www\./i', $text ) ) {
				return $text;
			}

			if ( strtolower( $text ) === $text ) {
				return function_exists( 'mb_substr' ) && function_exists( 'mb_strtoupper' )
					? mb_strtoupper( mb_substr( $text, 0, 1 ) ) . mb_substr( $text, 1 )
					: ucfirst( $text );
			}

			return $text;
		}

		/**
		 * Sanitize a token used in class names.
		 *
		 * @param mixed $value Raw value.
		 * @return string
		 */
		private function sanitize_token( $value ) {
			if ( ! is_scalar( $value ) ) {
				return '';
			}

			$value = (string) $value;

			if ( function_exists( 'sanitize_html_class' ) ) {
				return sanitize_html_class( $value );
			}

			return preg_replace( '/[^A-Za-z0-9_-]/', '', $value );
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

			if ( function_exists( '\\esc_url' ) ) {
				return \esc_url( $value );
			}

			return filter_var( $value, FILTER_VALIDATE_URL ) ? htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ) : '';
		}
	}
}
