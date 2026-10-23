<?php
/**
 * Logo showcase markup renderer.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\BlockConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\SwiperConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase\NavigationConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase\PaginationConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoShowcase\AttributeSerializer;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoShowcase\LogoCardConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoShowcase\LogoShowcaseItemRenderer;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoShowcase\LogoShowcaseRootRenderer;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssUtils;
use ShapedPlugin\LogoCarouselFree\Blocks\Rendering\LogoInfoPopupRenderer;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoShowcaseRenderer' ) ) {
	/**
	 * Render logo showcase item markup.
	 */
	class LogoShowcaseRenderer {
		/**
		 * Render logo source body markup.
		 *
		 * @param array<int, array<string, mixed>> $items Logo items.
		 * @param BlockConfig|null                 $config Normalized block config.
		 * @return string
		 */
		public function render( array $items, ?BlockConfig $config = null ) {
			return $this->render_from_data(
				array(
					'items' => $items,
				),
				$config
			);
		}

		/**
		 * Render logo showcase markup from normalized source payload.
		 *
		 * @param array<string, mixed> $render_data Normalized render payload.
		 * @param BlockConfig|null     $config Normalized block config.
		 * @return string
		 */
		public function render_from_data( array $render_data, ?BlockConfig $config = null ) {
			$items           = isset( $render_data['items'] ) && is_array( $render_data['items'] ) ? $render_data['items'] : array();
			$images_settings = isset( $render_data['imagesSettings'] ) && is_array( $render_data['imagesSettings'] ) ? $render_data['imagesSettings'] : ( $config ? $config->get_images_settings() : array() );
			$images_flags    = isset( $render_data['imagesFlags'] ) && is_array( $render_data['imagesFlags'] ) ? $render_data['imagesFlags'] : ( $config ? $config->get_images_flags() : array() );
			$unique_id       = $config ? $config->get_unique_id() : '';
			$uses_swiper     = $config ? $config->uses_swiper() : true;
			$swiper_config   = $config ? $config->get_swiper_config() : array();
			if ( $uses_swiper && empty( $swiper_config ) ) {
				$swiper_config = ( new SwiperConfig() )->normalize( $config ? $config->get_attributes() : array() );
			}
			$classes                = $this->build_carousel_classes( $images_settings, $swiper_config, $uses_swiper );
			$pagination_root_class  = '';
			$pagination_hide_mobile = false;
			$pagination_style       = 'style-1';
			if ( $uses_swiper ) {
				$pagination_config      = ( new PaginationConfig() )->normalize( $config ? $config->get_attributes() : array() );
				$pagination_root_class  = $pagination_config['rootClass'];
				$pagination_hide_mobile = ! empty( $pagination_config['hideOnMobile'] );
				$pagination_style       = isset( $pagination_config['paginationStyle'] ) ? (string) $pagination_config['paginationStyle'] : 'style-1';
				$classes[]              = $pagination_root_class;
			}
			$card_config     = ( new LogoCardConfig() )->normalize( $config ? $config->get_attributes() : array() );
			$attributes      = $this->build_carousel_attributes(
				$images_settings,
				$images_flags,
				$swiper_config,
				$uses_swiper,
				$config ? $config->get_attributes() : array()
			);
			$ui              = isset( $swiper_config['ui'] ) && is_array( $swiper_config['ui'] ) ? $swiper_config['ui'] : array();
			$behavior        = isset( $swiper_config['behavior'] ) && is_array( $swiper_config['behavior'] ) ? $swiper_config['behavior'] : array();
			$show_navigation = ! empty( $ui['showNavigation'] );
			$show_pagination = ! empty( $ui['showPagination'] );
			$is_vertical     = ! empty( $behavior['isVertical'] );

			if ( empty( $items ) ) {
				return ( new LogoShowcaseRootRenderer() )->render_empty_state( $classes, $attributes );
			}

			$markup         = '';
			$popups         = '';
			$total_slides   = count( $items );
			$slide_index    = 0;

			// Popup IDs for every item up front, so each popup's header arrows can
			// point at its siblings. Wraps at both ends (last → first), matching
			// the carousel's own looping.
			$popup_ids = array();
			foreach ( array_values( $items ) as $popup_item ) {
				$popup_ids[] = $this->build_popup_id( $unique_id, $popup_item );
			}

			foreach ( $items as $item ) {
				$item_index    = $slide_index;
				$item_popup_id = $this->build_popup_id( $unique_id, $item );
				$markup       .= ( new LogoShowcaseItemRenderer() )->render(
					$item,
					$images_settings,
					$item_popup_id,
					$this->get_slide_loading_config( $slide_index, $total_slides, $swiper_config, $images_settings ),
					$card_config
				);
				++$slide_index;

				if ( 'popup' === ( isset( $images_settings['clickAction'] ) ? (string) $images_settings['clickAction'] : 'none' ) ) {
					$popups .= ( new LogoInfoPopupRenderer() )->render(
						$item,
						$item_popup_id,
						$unique_id,
						$this->build_popup_neighbours( $popup_ids, $item_index )
					);
				}
			}

			$use_scrollbar = 'style-5' === $pagination_style;
			$chrome_classes = $use_scrollbar
				? 'sp-lcpro-logo-carousel__scrollbar swiper-scrollbar'
				: 'sp-lcpro-logo-carousel__pagination swiper-pagination';
			if ( $pagination_hide_mobile ) {
				$chrome_classes .= ' sp-lcpro-pagination--hide-mobile';
			}
			// Only style-5 mounts a drag thumb. Shipping `.swiper-scrollbar-drag`
			// inside bullet pagination paints Swiper's gray bar before JS runs,
			// then bullets replace it — a flash of scrollbar on every reload.
			$chrome_inner = $use_scrollbar
				? '<div class="swiper-scrollbar-drag"></div>'
				: '';
			$pagination_chrome          = '<div class="' . $this->escape_attr( $chrome_classes ) . '">' . $chrome_inner . '</div>';
			$pagination_markup          = $show_pagination && ! $is_vertical ? $pagination_chrome : '';
			$external_pagination_markup = $show_pagination && $is_vertical ? $pagination_chrome : '';
			$navigation_markup          = '';
			$frame_classes              = '';
			$frame_styles               = '';
			if ( $show_navigation ) {
				$navigation        = ( new NavigationConfig() )->normalize( $config ? $config->get_attributes() : array(), $behavior );
				$navigation_markup = $this->build_navigation_markup( $navigation );
				$frame_classes     = implode( ' ', $navigation['classes'] );
				$frame_styles      = ( new AttributeSerializer() )->serialize_style_declarations( $navigation['styles'] );
			}

			if ( $show_navigation && $show_pagination ) {
				$frame_classes = trim( $frame_classes . ' sp-lcpro-nav-frame--has-pagination' );
			}

			if ( '' !== $external_pagination_markup ) {
				$frame_classes = trim( $frame_classes . ' ' . $pagination_root_class . ' sp-lcpro-pagination-frame--vertical' );
			}

			$item_list_class = $uses_swiper
				? 'sp-lcpro-logo-source__items swiper-wrapper'
				: 'sp-lcpro-logo-source__items';

			return ( new LogoShowcaseRootRenderer() )->render(
				$classes,
				$attributes,
				$markup,
				$pagination_markup,
				$navigation_markup,
				$popups,
				$frame_classes,
				$frame_styles,
				$external_pagination_markup,
				$item_list_class
			);
		}

		/**
		 * Build the base carousel classes.
		 *
		 * @param array<string, mixed> $images_settings Normalized images settings.
		 * @param array<string, mixed> $swiper_config Swiper config payload.
		 * @param bool                 $uses_swiper Whether to seed the Swiper root class. Defaults true.
		 * @return array<int, string>
		 */
		private function build_carousel_classes( array $images_settings, array $swiper_config = array(), $uses_swiper = true ) {
			$classes = array( 'sp-lcpro-logo-carousel' );
			if ( $uses_swiper ) {
				$classes[] = 'swiper';
			}

			$aspect_ratio = isset( $images_settings['aspectRatio'] ) ? (string) $images_settings['aspectRatio'] : 'original';
			$resolution   = isset( $images_settings['resolution'] ) ? (string) $images_settings['resolution'] : 'large';
			$click_action = isset( $images_settings['clickAction'] ) ? (string) $images_settings['clickAction'] : 'none';

			$classes[] = 'sp-lcpro-logo-carousel--aspect-' . CssUtils::sanitize_wrapper_token( $aspect_ratio );
			$classes[] = 'sp-lcpro-logo-carousel--resolution-' . CssUtils::sanitize_wrapper_token( $resolution );
			$classes[] = 'sp-lcpro-logo-carousel--click-' . CssUtils::sanitize_wrapper_token( $click_action );

			if ( 'custom' === $aspect_ratio ) {
				$classes[] = 'sp-lcpro-logo-carousel--aspect-custom-ratio';
			}

			$behavior = isset( $swiper_config['behavior'] ) && is_array( $swiper_config['behavior'] ) ? $swiper_config['behavior'] : array();
			if ( ! empty( $behavior['carouselStyle'] ) ) {
				$classes[] = 'sp-lcpro-logo-carousel--style-' . CssUtils::sanitize_wrapper_token( (string) $behavior['carouselStyle'] );
			}

			if ( ! empty( $behavior['languageDirection'] ) ) {
				$classes[] = 'sp-lcpro-logo-carousel--dir-' . CssUtils::sanitize_wrapper_token( (string) $behavior['languageDirection'] );
			}

			$presentation = isset( $swiper_config['presentation'] ) && is_array( $swiper_config['presentation'] ) ? $swiper_config['presentation'] : array();
			if ( ! empty( $presentation['classes'] ) && is_array( $presentation['classes'] ) ) {
				foreach ( $presentation['classes'] as $class ) {
					$classes[] = CssUtils::sanitize_wrapper_token( (string) $class );
				}
			}

			return array_values( array_filter( $classes ) );
		}

		/**
		 * Build data attributes for the carousel root.
		 *
		 * @param array<string, mixed> $images_settings Normalized images settings.
		 * @param array<string, mixed> $images_flags Derived images flags.
		 * @param array<string, mixed> $swiper_config Swiper config payload.
		 * @param bool                 $uses_swiper Whether to emit the Swiper runtime data attribute. Defaults true.
		 * @param array<string, mixed> $block_attributes Raw block attributes.
		 * @return string
		 */
		private function build_carousel_attributes( array $images_settings, array $images_flags, array $swiper_config, $uses_swiper = true, array $block_attributes = array() ) {
			$serializer   = new AttributeSerializer();
			$behavior     = isset( $swiper_config['behavior'] ) && is_array( $swiper_config['behavior'] ) ? $swiper_config['behavior'] : array();
			$presentation = isset( $swiper_config['presentation'] ) && is_array( $swiper_config['presentation'] ) ? $swiper_config['presentation'] : array();
			$edge_fade_presentation = isset( $presentation['edgeFade'] ) && is_array( $presentation['edgeFade'] ) ? $presentation['edgeFade'] : array();
			$styles       = array();
			if ( ! empty( $presentation['styles'] ) && is_array( $presentation['styles'] ) ) {
				$styles = array_merge( $styles, $presentation['styles'] );
			}

			// The Swiper runtime data attribute stays first (byte parity for
			// Swiper blocks); non-Swiper blocks omit it entirely.
			$attributes = array();
			if ( $uses_swiper ) {
				$attributes['data-sp-lcpro-carousel'] = $serializer->encode_data( $swiper_config );
			}

			$attributes += array(
				'data-sp-lcpro-images-settings'           => $serializer->encode_data( $images_settings ),
				'data-sp-lcpro-images-flags'              => $serializer->encode_data( $images_flags ),
				'data-sp-lcpro-logo-images-style'         => $serializer->encode_data(
					isset( $block_attributes['logoImagesStyle'] ) && is_array( $block_attributes['logoImagesStyle'] )
						? $block_attributes['logoImagesStyle']
						: ( isset( $block_attributes['logoImageStyle'] ) && is_array( $block_attributes['logoImageStyle'] ) ? $block_attributes['logoImageStyle'] : array() )
				),
				'data-sp-lcpro-carousel-style'            => isset( $behavior['carouselStyle'] ) ? (string) $behavior['carouselStyle'] : 'standard',
				'data-sp-lcpro-carousel-direction'        => isset( $behavior['carouselDirection'] ) ? (string) $behavior['carouselDirection'] : 'right-to-left',
				'data-sp-lcpro-alignment'                 => isset( $presentation['alignment']['value'] ) ? (string) $presentation['alignment']['value'] : 'center',
				'data-sp-lcpro-edge-fade'                 => ! empty( $edge_fade_presentation['enabled'] ) ? 'true' : 'false',
				'data-sp-lcpro-edge-fade-direction'       => isset( $edge_fade_presentation['direction'] ) ? (string) $edge_fade_presentation['direction'] : 'horizontal',
				'data-sp-lcpro-edge-fade-color-mode'      => isset( $edge_fade_presentation['colorMode'] ) ? (string) $edge_fade_presentation['colorMode'] : 'auto',
				'data-sp-lcpro-carousel-starts-on-screen' => ! empty( $presentation['carouselStartsOnScreen'] ) ? 'true' : 'false',
				'data-sp-lcpro-aspect-ratio'              => isset( $images_settings['aspectRatio'] ) ? (string) $images_settings['aspectRatio'] : 'original',
				'data-sp-lcpro-resolution'                => isset( $images_settings['resolution'] ) ? (string) $images_settings['resolution'] : 'large',
				'data-sp-lcpro-lazy-load'                 => ! empty( $images_settings['lazyLoad'] ) ? 'true' : 'false',
				'data-sp-lcpro-random-order'              => ! empty( $images_settings['randomOrder'] ) ? 'true' : 'false',
				'data-sp-lcpro-click-action'              => isset( $images_settings['clickAction'] ) ? (string) $images_settings['clickAction'] : 'none',
				'data-sp-lcpro-has-images'                => ! empty( $images_flags['has_images'] ) ? 'true' : 'false',
			);

			if ( ! empty( $behavior['languageDirection'] ) ) {
				$attributes['dir'] = (string) $behavior['languageDirection'];
			}

			if ( ! empty( $styles ) ) {
				$attributes['style'] = $serializer->serialize_style_declarations( $styles );
			}

			return $serializer->serialize_attributes( $attributes );
		}

		/**
		 * Build the navigation arrow group markup (prev/next with resolved icon).
		 *
		 * Buttons live outside the Swiper viewport (in the frame) and are bound by
		 * the runtime via explicit prevEl/nextEl — no `swiper-button-*` classes so
		 * Swiper's default positioning CSS does not interfere.
		 *
		 * @param array<string, mixed> $navigation Normalized navigation payload.
		 * @return string
		 */
		private function build_navigation_markup( array $navigation ) {
			$icon       = isset( $navigation['icon'] ) ? (string) $navigation['icon'] : '';
			$prev_label = function_exists( '\\esc_attr__' ) ? \esc_attr__( 'Previous logos', 'logo-carousel-free' ) : $this->escape_attr( 'Previous logos' );
			$next_label = function_exists( '\\esc_attr__' ) ? \esc_attr__( 'Next logos', 'logo-carousel-free' ) : $this->escape_attr( 'Next logos' );

			return sprintf(
				'<div class="sp-lcpro-logo-carousel__nav-group"><button class="sp-lcpro-logo-carousel__nav sp-lcpro-logo-carousel__nav--prev" type="button" aria-label="%1$s"><span class="sp-lcpro-logo-carousel__nav-icon">%3$s</span></button><button class="sp-lcpro-logo-carousel__nav sp-lcpro-logo-carousel__nav--next" type="button" aria-label="%2$s"><span class="sp-lcpro-logo-carousel__nav-icon">%3$s</span></button></div>',
				$prev_label,
				$next_label,
				$icon
			);
		}

		/**
		 * Sibling popup IDs for the arrows in one popup's header.
		 *
		 * Wraps around, so the first popup's "previous" is the last one. A block
		 * with a single logo gets no siblings and therefore no arrows.
		 *
		 * @param array<int, string> $popup_ids Popup IDs in slide order.
		 * @param int                $index Current item index.
		 * @return array<string, string>
		 */
		private function build_popup_neighbours( array $popup_ids, $index ) {
			$total = count( $popup_ids );

			if ( $total < 2 || ! isset( $popup_ids[ $index ] ) ) {
				return array();
			}

			return array(
				'prev' => $popup_ids[ ( $index - 1 + $total ) % $total ],
				'next' => $popup_ids[ ( $index + 1 ) % $total ],
			);
		}

		/**
		 * Build a stable popup ID for a logo item.
		 *
		 * @param string               $unique_id Block unique ID.
		 * @param array<string, mixed> $item Logo item.
		 * @return string
		 */
		private function build_popup_id( $unique_id, array $item ) {
			$popup_key = isset( $item['id'] ) && '' !== (string) $item['id'] ? (string) $item['id'] : ( isset( $item['order'] ) ? (string) $item['order'] : 'logo' );
			$unique_id = CssUtils::sanitize_wrapper_token( $unique_id );
			$popup_key = CssUtils::sanitize_wrapper_token( $popup_key );

			return 'sp-lcpro-logo-info-' . $unique_id . '-' . $popup_key;
		}

		/**
		 * Calculate slides per view for lazy loading optimization.
		 *
		 * Determines how many slides are visible in the initial viewport
		 * to apply eager loading for Core Web Vitals optimization.
		 *
		 * @param array<string, mixed> $swiper_config Swiper configuration.
		 * @return int Number of slides visible in initial viewport.
		 */
		private function get_slides_per_view( array $swiper_config ) {
			$swiper = isset( $swiper_config['swiper'] ) && is_array( $swiper_config['swiper'] ) ? $swiper_config['swiper'] : array();

			$visible_counts = array(
				$this->normalize_visible_slide_count(
					isset( $swiper['slidesPerView'] ) ? $swiper['slidesPerView'] : null,
					isset( $swiper['slidesPerGroup'] ) ? $swiper['slidesPerGroup'] : null
				),
			);

			if ( ! empty( $swiper['breakpoints'] ) && is_array( $swiper['breakpoints'] ) ) {
				foreach ( $swiper['breakpoints'] as $breakpoint ) {
					if ( ! is_array( $breakpoint ) ) {
						continue;
					}

					$visible_counts[] = $this->normalize_visible_slide_count(
						isset( $breakpoint['slidesPerView'] ) ? $breakpoint['slidesPerView'] : null,
						isset( $breakpoint['slidesPerGroup'] ) ? $breakpoint['slidesPerGroup'] : null
					);
				}
			}

			return max( 1, max( $visible_counts ) );
		}

		/**
		 * Normalize a slides-per-view value into an eager-loading slot count.
		 *
		 * Server-side rendering cannot know the active client breakpoint, so the
		 * caller takes the widest visible-slot count across all breakpoints. This
		 * never lazy-loads an above-fold slide on large viewports, at the cost of
		 * some over-eager loading on smaller ones.
		 *
		 * @param mixed $slides_per_view Slides-per-view value.
		 * @param mixed $slides_per_group Slides-per-group fallback.
		 * @return int
		 */
		private function normalize_visible_slide_count( $slides_per_view, $slides_per_group = null ) {
			if ( 'auto' === $slides_per_view ) {
				if ( is_numeric( $slides_per_group ) ) {
					return max( 1, (int) ceil( (float) $slides_per_group ) );
				}

				return 3;
			}

			if ( is_numeric( $slides_per_view ) ) {
				return max( 1, (int) ceil( (float) $slides_per_view ) );
			}

			return 1;
		}

		/** Determine the eager/lazy loading config for a slide. */
		private function get_slide_loading_config( $slide_index, $total_slides, array $swiper_config, array $images_settings ) {
			$config = array(
				'loading'          => 'eager',
				'is_first_visible' => true,
				'fetchpriority'    => 0 === $slide_index ? 'high' : '',
			);

			if ( empty( $images_settings['lazyLoad'] ) ) {
				return $config;
			}

			if ( $total_slides <= 1 ) {
				return $config;
			}

			$slides_per_view = $this->get_slides_per_view( $swiper_config );
			$swiper          = isset( $swiper_config['swiper'] ) && is_array( $swiper_config['swiper'] ) ? $swiper_config['swiper'] : array();
			$is_loop_mode    = ! empty( $swiper['loop'] );

			$eager_count = $is_loop_mode ? ( $slides_per_view * 2 ) : $slides_per_view;
			$eager_count = min( max( 1, $total_slides ), $eager_count );

			$is_first_visible = $slide_index < $eager_count;

			$config['is_first_visible'] = $is_first_visible;
			$config['loading']          = $is_first_visible ? 'eager' : 'lazy';

			return $config;
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
	}
}
