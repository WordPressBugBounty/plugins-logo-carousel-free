<?php
/**
 * Logo info popup CSS concern.
 *
 * Mirrors the Logo Details modal design. Palette sampled from it: accent
 * #2c7c5d (stars, links, Visit Link), hairlines #e5e7eb, headings #1e2939,
 * body #364153, muted labels #6a7282, chips #f3f4f6.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns;

use ShapedPlugin\LogoCarouselFree\Blocks\Support\UniqueId;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoInfoPopupCss' ) ) {
	/**
	 * Shared read-only popup styles for block frontend output.
	 */
	class LogoInfoPopupCss {
		/**
		 * Base popup rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function base_rules( array $selectors, array $attributes ) {
			$unique_id = UniqueId::from_attributes( $attributes );
			if ( '' === $unique_id ) {
				return array();
			}

			$popup = '.sp-lcpro-logo-info-popup.sp-lcpro-logo-info-popup--' . UniqueId::sanitize_token( $unique_id );

			return array(
				// Remodal chrome — unchanged, it drives open/close behaviour.
				array(
					'selector' => '.remodal-wrapper',
					'styles'   => array(
						'position'        => 'fixed',
						'inset'           => '0',
						'z-index'         => '99999',
						'padding'         => '24px',
						'display'         => 'none',
						'align-items'     => 'center',
						'justify-content' => 'center',
					),
				),
				array(
					'selector' => '.remodal-wrapper.remodal-is-opening, .remodal-wrapper.remodal-is-opened',
					'styles'   => array(
						'display' => 'flex !important',
					),
				),
				array(
					'selector' => '.remodal-overlay',
					'styles'   => array(
						'position'   => 'fixed',
						'inset'      => '0',
						'background' => 'rgba(15, 23, 42, 0.38)',
						'display'    => 'none',
					),
				),
				array(
					'selector' => '.remodal-overlay.remodal-is-opening, .remodal-overlay.remodal-is-opened',
					'styles'   => array(
						'display' => 'block',
					),
				),
				array(
					'selector' => $popup,
					'styles'   => array(
						'position' => 'relative',
						'z-index'  => '1',
						'display'  => 'none',
						'outline'  => 'none',
					),
				),
				array(
					'selector' => $popup . '.remodal-is-opening, ' . $popup . '.remodal-is-opened',
					'styles'   => array(
						'display' => 'block',
					),
				),

				// Dialog shell.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__dialog',
					'styles'   => array(
						'width'         => 'min( 940px, 92vw )',
						'max-height'    => 'calc( 100vh - 64px )',
						'overflow'      => 'auto',
						'padding'       => '0',
						'border-radius' => '12px',
						'border'        => '1px solid #e5e7eb',
						'background'    => '#fff',
						'box-shadow'    => '0 24px 64px rgba(15, 23, 42, 0.18)',
						'font-family'   => 'var(--sp-lcpro-font-family)',
						'text-align'    => 'left',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__header',
					'styles'   => array(
						'display'         => 'flex',
						'align-items'     => 'center',
						'justify-content' => 'space-between',
						'gap'             => '16px',
						'padding'         => '20px 24px',
						'border-bottom'   => '1px solid #e5e7eb',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__eyebrow',
					'styles'   => array(
						'margin'         => '0',
						'font-size'      => '20px',
						'font-weight'    => '700',
						'line-height'    => '1.3',
						'color'          => '#1e2939',
						'letter-spacing' => 'normal',
						'text-transform' => 'none',
					),
				),
				// Prev/next arrows: plain white chevrons on the overlay, centred
				// vertically on the dialog and sitting ~33px outside its edge
				// (measured from the design). Anchored to the popup root, which
				// wraps the dialog exactly, so they never enter its scroll box.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__nav',
					'styles'   => array(
						'position'        => 'absolute',
						'top'             => '50%',
						'transform'       => 'translateY(-50%)',
						'z-index'         => '2',
						'display'         => 'inline-flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'width'           => '40px',
						'height'          => '40px',
						'padding'         => '0',
						'border'          => '0',
						'background'      => 'transparent',
						'box-shadow'      => 'none',
						'color'           => '#fff',
						'line-height'     => '1',
						'cursor'          => 'pointer',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__nav--prev',
					'styles'   => array(
						'left' => '-53px',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__nav--next',
					'styles'   => array(
						'right' => '-53px',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__nav:hover',
					'styles'   => array(
						'background' => 'transparent',
						'color'      => '#fff',
						'opacity'    => '0.7',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__nav svg',
					'styles'   => array(
						'display' => 'block',
					),
				),
				array(
					'selector' => $popup . ' .remodal-close',
					'styles'   => array(
						'position'        => 'static',
						'flex'            => '0 0 auto',
						'width'           => '34px',
						'height'          => '34px',
						'display'         => 'inline-flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'padding'         => '0',
						'border-radius'   => '6px',
						'border'          => '1px solid #e5e7eb',
						'background'      => '#fff',
						'color'           => '#6a7282',
						'font-size'       => '18px',
						'line-height'     => '1',
						'text-decoration' => 'none',
						'cursor'          => 'pointer',
					),
				),
				array(
					'selector' => $popup . ' .remodal-close:hover',
					'styles'   => array(
						'background' => '#f3f4f6',
						'color'      => '#1e2939',
					),
				),

				// Two-column body.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__grid',
					'styles'   => array(
						'display'               => 'grid',
						'grid-template-columns' => '300px minmax(0, 1fr)',
						'gap'                   => '0',
						// Stretch, not start: the divider is the preview column's
						// right border, so a content-height column would stop it
						// short of the footer. Both columns lay their children out
						// from the top regardless, so nothing else shifts.
						'align-items'           => 'stretch',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__grid--no-preview',
					'styles'   => array(
						'grid-template-columns' => 'minmax(0, 1fr)',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__preview',
					'styles'   => array(
						'display'        => 'flex',
						'flex-direction' => 'column',
						'gap'            => '16px',
						'padding'        => '24px',
						'border-right'   => '1px solid #e5e7eb',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__content',
					'styles'   => array(
						'display'        => 'flex',
						'flex-direction' => 'column',
						'padding'        => '24px',
						'min-width'      => '0',
					),
				),

				// Image.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__image-frame',
					'styles'   => array(
						'display'         => 'flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'min-height'      => '200px',
						'padding'         => '12px',
						'border'          => '1px solid #e5e7eb',
						'border-radius'   => '8px',
						'background'      => '#fff',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__image-frame img',
					'styles'   => array(
						'display'    => 'block',
						'width'      => '100%',
						'max-width'  => '100%',
						'height'     => 'auto',
						'object-fit' => 'contain',
					),
				),

				// File meta list.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__meta',
					'styles'   => array(
						'display'        => 'flex',
						'flex-direction' => 'column',
						'gap'            => '10px',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__meta-row',
					'styles'   => array(
						'display'         => 'flex',
						'align-items'     => 'baseline',
						'justify-content' => 'space-between',
						'gap'             => '12px',
						'font-size'       => '13px',
						'line-height'     => '1.4',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__meta-label',
					'styles'   => array(
						'color' => '#6a7282',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__meta-value',
					'styles'   => array(
						'color'       => '#364153',
						'font-weight' => '500',
						'text-align'  => 'right',
						'word-break'  => 'break-word',
					),
				),

				// Rating card.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__rating-card',
					'styles'   => array(
						'display'        => 'flex',
						'flex-direction' => 'column',
						'gap'            => '8px',
						'padding'        => '12px 14px',
						'border'         => '1px solid #e5e7eb',
						'border-radius'  => '8px',
						'background'     => '#fff',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__rating-heading',
					'styles'   => array(
						'font-size'      => '13px',
						'font-weight'    => '600',
						'color'          => '#1e2939',
						'text-transform' => 'none',
						'letter-spacing' => 'normal',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__rating',
					'styles'   => array(
						'display'     => 'flex',
						'flex-wrap'   => 'wrap',
						'align-items' => 'center',
						'gap'         => '8px',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__rating-stars',
					'styles'   => array(
						'display'     => 'inline-flex',
						'gap'         => '3px',
						'font-size'   => '15px',
						'line-height' => '1',
						'color'       => '#d9d9d9',
					),
				),
				// Star palette matches the Pro Display Elements card so the popup
				// does not recolour the rating it was opened from.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__star--full',
					'styles'   => array(
						'color' => '#f0b849',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__star--empty',
					'styles'   => array(
						'color' => '#d9d9d9',
					),
				),
				// Half star: gold left half, grey right half via clipped gradient text.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__star--half',
					'styles'   => array(
						'background'             => 'linear-gradient(90deg, #f0b849 50%, #d9d9d9 50%)',
						'background-clip'        => 'text',
						'-webkit-background-clip' => 'text',
						'color'                  => 'transparent',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__rating-value',
					'styles'   => array(
						'font-size'   => '13px',
						'font-weight' => '500',
						'color'       => '#364153',
					),
				),

				// Title + category chips.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__title',
					'styles'   => array(
						'margin'      => '0',
						'font-size'   => '17px',
						'font-weight' => '700',
						'line-height' => '1.3',
						'color'       => '#1e2939',
						'word-break'  => 'break-word',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__tags',
					'styles'   => array(
						'display'    => 'flex',
						'flex-wrap'  => 'wrap',
						'gap'        => '6px',
						'margin-top' => '10px',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__tag',
					'styles'   => array(
						'display'       => 'inline-flex',
						'align-items'   => 'center',
						'padding'       => '4px 10px',
						'border-radius' => '4px',
						'background'    => '#f3f4f6',
						'color'         => '#1e2939',
						'font-size'     => '12px',
						'font-weight'   => '500',
						'line-height'   => '1.4',
					),
				),

				// Detail rows.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__details-stack',
					'styles'   => array(
						'display'        => 'flex',
						'flex-direction' => 'column',
						'margin-top'     => '20px',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__detail',
					'styles'   => array(
						'display'               => 'grid',
						'grid-template-columns' => '166px minmax(0, 1fr)',
						'gap'                   => '16px',
						'align-items'           => 'start',
						'padding'               => '12px 0',
						'border-top'            => '0',
						'border-bottom'         => '1px solid #e5e7eb',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__detail-label',
					'styles'   => array(
						'font-size'      => '13px',
						'font-weight'    => '400',
						'line-height'    => '1.5',
						'color'          => '#6a7282',
						'text-transform' => 'none',
						'letter-spacing' => 'normal',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__detail-value',
					'styles'   => array(
						'font-size'   => '14px',
						'font-weight' => '500',
						'line-height' => '1.5',
						'color'       => '#364153',
						'word-break'  => 'break-word',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__detail-value a',
					'styles'   => array(
						'color'           => '#2c7c5d',
						'text-decoration' => 'none',
						'word-break'      => 'break-word',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__detail-value a:hover',
					'styles'   => array(
						'text-decoration' => 'underline',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__detail-value img',
					'styles'   => array(
						'max-width' => '100%',
					),
				),

				// Description.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__description',
					'styles'   => array(
						'margin-top' => '16px',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__description-title',
					'styles'   => array(
						'margin'      => '0 0 8px',
						'font-size'   => '14px',
						'font-weight' => '700',
						'line-height' => '1.4',
						'color'       => '#1e2939',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__description-text',
					'styles'   => array(
						'margin'      => '0',
						'font-size'   => '14px',
						'line-height' => '1.6',
						'color'       => '#364153',
					),
				),

				// Footer + Visit Link.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__footer',
					'styles'   => array(
						'display'         => 'flex',
						'justify-content' => 'flex-end',
						'padding'         => '16px 24px',
						'border-top'      => '1px solid #e5e7eb',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__visit',
					'styles'   => array(
						'display'         => 'inline-flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'padding'         => '9px 18px',
						'border-radius'   => '6px',
						'background'      => '#2c7c5d',
						'color'           => '#fff',
						'font-size'       => '14px',
						'font-weight'     => '600',
						'line-height'     => '1.4',
						'text-decoration' => 'none',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__visit:hover',
					'styles'   => array(
						'background' => '#24664c',
						'color'      => '#fff',
					),
				),
			);
		}

		/**
		 * Tablet popup rules.
		 *
		 * The two columns stack, so the divider moves from the preview's right
		 * edge to its bottom, and each detail row becomes label-over-value.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function tablet_rules( array $selectors, array $attributes ) {
			$unique_id = UniqueId::from_attributes( $attributes );
			if ( '' === $unique_id ) {
				return array();
			}

			$popup = '.sp-lcpro-logo-info-popup.sp-lcpro-logo-info-popup--' . UniqueId::sanitize_token( $unique_id );

			return array(
				array(
					'selector' => $popup,
					'styles'   => array(
						'padding' => '16px',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__grid',
					'styles'   => array(
						'grid-template-columns' => 'minmax(0, 1fr)',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__preview',
					'styles'   => array(
						'border-right'  => '0',
						'border-bottom' => '1px solid #e5e7eb',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__detail',
					'styles'   => array(
						'grid-template-columns' => 'minmax(0, 1fr)',
						'gap'                   => '4px',
					),
				),
				// The dialog fills the viewport width here, so there is no overlay
				// left to sit on: the arrows move inside and take a light chip so
				// they stay legible over both the white card and a logo.
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__nav',
					'styles'   => array(
						'width'         => '34px',
						'height'        => '34px',
						'border-radius' => '999px',
						'background'    => 'rgba(255, 255, 255, 0.92)',
						'box-shadow'    => '0 1px 4px rgba(15, 23, 42, 0.18)',
						'color'         => '#1e2939',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__nav--prev',
					'styles'   => array(
						'left' => '6px',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__nav--next',
					'styles'   => array(
						'right' => '6px',
					),
				),
				array(
					'selector' => $popup . ' .sp-lcpro-logo-info-popup__nav:hover',
					'styles'   => array(
						'background' => '#fff',
						'color'      => '#1e2939',
						'opacity'    => '1',
					),
				),
			);
		}

		/**
		 * Mobile popup rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_rules( array $selectors, array $attributes ) {
			return self::tablet_rules( $selectors, $attributes );
		}
	}
}
