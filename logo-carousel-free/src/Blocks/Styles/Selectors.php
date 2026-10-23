<?php
/**
 * Shared selector factory for the block-family CSS composer.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Selectors' ) ) {
	/**
	 * Build scoped selectors for a block instance.
	 */
	class Selectors {
		/**
		 * Create selectors for the given block instance.
		 *
		 * @param string $unique_id Block instance unique ID.
		 * @param string $block_type Block type.
		 * @return array<string, string>
		 */
		public static function create( $unique_id, $block_type = 'carousel' ) {
			$unique_id = CssUtils::sanitize_wrapper_token( $unique_id );
			$wrapper   = '' !== $unique_id ? '#' . $unique_id : '';

			$selectors = array(
				'wrapper'                        => $wrapper,
				'header'                         => $wrapper . ' .sp-lcpro-block-app__header',
				'heading'                        => $wrapper . ' .sp-lcpro-block-app__heading',
				'caption'                        => $wrapper . ' .sp-lcpro-block-app__caption',
				'body'                           => $wrapper . ' .sp-lcpro-block-app__body',
				'logoSourceEmpty'                => $wrapper . ' .sp-lcpro-logo-source-empty',
				'logoSourceItems'                => $wrapper . ' .sp-lcpro-logo-source__items',
				'logoSourceItem'                 => $wrapper . ' .sp-lcpro-logo-source__item',
				'logoSourceFigure'               => $wrapper . ' .sp-lcpro-logo-source__figure',
				'logoSourceImage'                => $wrapper . ' .sp-lcpro-logo-source__image',
				'logoSourceMeta'                 => $wrapper . ' .sp-lcpro-logo-source__meta',
				// The element whose `:hover` drives the Logo Images hover state —
				// filters, opacity, custom colour and every hover-animation preset.
				// That is the figure (the logo's own card).
				'logoImageHoverHost'             => $wrapper . ' .sp-lcpro-logo-carousel .sp-lcpro-logo-source__figure',
				'logoSourceTitle'                => $wrapper . ' .sp-lcpro-logo-source__title',
				// The outer frame that hosts the arrows — and the element the
				// arrow-size/gap/offset custom properties are set on, so a
				// per-breakpoint arrow size has somewhere to land.
				'navFrame'                       => $wrapper . ' .sp-lcpro-logo-carousel__frame',
				'navGroup'                       => $wrapper . ' .sp-lcpro-logo-carousel__nav-group',
				'nav'                            => $wrapper . ' .sp-lcpro-logo-carousel__nav',
				'navHover'                       => $wrapper . ' .sp-lcpro-logo-carousel__nav:hover, ' . $wrapper . ' .sp-lcpro-logo-carousel__nav:active',
				'paginationContainer'            => $wrapper . ' .sp-lcpro-logo-carousel__pagination',
				'externalPaginationContainer'    => $wrapper . ' + .sp-lcpro-logo-carousel__pagination',
				// Universal dots host — Swiper always adds `.swiper-pagination`
				// (editor + server, horizontal + vertical).
				'swiperPagination'               => $wrapper . ' .swiper-pagination',
				'paginationBullet'               => $wrapper . ' .swiper-pagination-bullet',
				'externalPaginationBullet'       => $wrapper . ' + .sp-lcpro-logo-carousel__pagination .swiper-pagination-bullet',
				'paginationBulletActive'         => $wrapper . ' .swiper-pagination-bullet-active',
				'externalPaginationBulletActive' => $wrapper . ' + .sp-lcpro-logo-carousel__pagination .swiper-pagination-bullet-active',
				// Style-5 scrollbar track + drag thumb (editor + server).
				'scrollbar'                      => $wrapper . ' .sp-lcpro-logo-carousel__scrollbar, ' . $wrapper . ' .swiper-scrollbar',
				'scrollbarDrag'                  => $wrapper . ' .sp-lcpro-logo-carousel__scrollbar .swiper-scrollbar-drag, ' . $wrapper . ' .swiper-scrollbar-drag',
				'externalScrollbar'              => $wrapper . ' + .sp-lcpro-logo-carousel__scrollbar, ' . $wrapper . ' .sp-lcpro-logo-carousel__frame > .sp-lcpro-logo-carousel__scrollbar',
				'externalScrollbarDrag'          => $wrapper . ' + .sp-lcpro-logo-carousel__scrollbar .swiper-scrollbar-drag, ' . $wrapper . ' .sp-lcpro-logo-carousel__frame > .sp-lcpro-logo-carousel__scrollbar .swiper-scrollbar-drag',
				// Logo card surface + its inner wrapper. The `--has-display-elements`
				// / `.sp-lcpro-de` class names are shared with Pro. Qualified with
				// `[data-sp-lcpro-aspect-ratio]` so Inner Padding / border /
				// background out-rank LogoImagesCss figure box rules that carry the
				// same attribute ancestor (otherwise slot padding wins).
				'logoCard'                       => $wrapper . ' .sp-lcpro-logo-carousel[data-sp-lcpro-aspect-ratio] .sp-lcpro-logo-source__figure.sp-lcpro-logo-source__figure--has-display-elements',
				'logoCardHover'                  => $wrapper . ' .sp-lcpro-logo-carousel[data-sp-lcpro-aspect-ratio] .sp-lcpro-logo-source__item:hover .sp-lcpro-logo-source__figure.sp-lcpro-logo-source__figure--has-display-elements',
				'logoCardContent'                => $wrapper . ' .sp-lcpro-de',
			);

			if ( function_exists( 'apply_filters' ) ) {
				return apply_filters( 'sp_logo_carousel_pro_block_selectors', $selectors, $unique_id, $block_type );
			}

			return $selectors;
		}
	}
}
