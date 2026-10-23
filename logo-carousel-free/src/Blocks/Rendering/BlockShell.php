<?php
/**
 * Shared shell markup for demo block rendering.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Rendering;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\BlockConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Support\UniqueId;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssUtils;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BlockShell' ) ) {
	/**
	 * Wrap block content in the canonical block-owned shell.
	 */
	class BlockShell {
		/**
		 * Render the block shell.
		 *
		 * @param BlockConfig $config Normalized config.
		 * @param string      $body_markup Inner markup.
		 * @param string      $modifier Optional modifier class.
		 * @param string      $dynamic_css Scoped dynamic CSS to inline.
		 * @param string      $section_data Optional pre-escaped attribute markup appended to the section tag (leading space required).
		 * @param string      $pre_body_markup Optional pre-built markup placed inside the section, above the body.
		 * @return string
		 */
		public function render( BlockConfig $config, $body_markup, $modifier = '', $dynamic_css = '', $section_data = '', $pre_body_markup = '' ) {
			$classes    = array( 'sp-lcpro-block-app', 'sp-lcpro-block-app--' . CssUtils::sanitize_wrapper_token( $config->get_slug() ) );
			$unique_id  = $config->get_unique_id();
			$attributes = $config->get_attributes();
			$id_attr    = '';
			$style_tag  = '';
			$header_markup = '';

			if ( '' !== $modifier ) {
				$classes[] = CssUtils::sanitize_wrapper_token( $modifier );
			}

			if ( '' !== $unique_id ) {
				$classes[] = UniqueId::build_class_name( $unique_id );
				$id_attr   = ' id="' . esc_attr( $unique_id ) . '"';
			}

			$css_class = '';
			if ( isset( $attributes['advanced']['cssClass'] ) && is_string( $attributes['advanced']['cssClass'] ) ) {
				$css_class = trim( $attributes['advanced']['cssClass'] );
			}

			if ( '' !== $css_class ) {
				$extra_classes = preg_split( '/\s+/', $css_class );
				if ( is_array( $extra_classes ) ) {
					foreach ( $extra_classes as $extra_class ) {
						$extra_class = CssUtils::sanitize_wrapper_token( $extra_class );
						if ( '' !== $extra_class ) {
							$classes[] = $extra_class;
						}
					}
				}
			}

			// Gutenberg Align width (supports.align: wide|full). Editor gets these via
			// useBlockProps; custom render_callback must emit them on the outermost root.
			$align_class = $this->resolve_align_class( $attributes );

			// Additional CSS ID wraps the section; the section keeps the internal uniqueId.
			$css_id = '';
			if ( isset( $attributes['advanced']['cssId'] ) && is_string( $attributes['advanced']['cssId'] ) ) {
				$css_id = sanitize_html_class( trim( $attributes['advanced']['cssId'] ) );
			}

			// Align classes belong on the outermost element so theme .alignwide/.alignfull layout works.
			if ( '' !== $align_class && '' === $css_id ) {
				$classes[] = $align_class;
			}

			$dynamic_css = trim( wp_strip_all_tags( (string) $dynamic_css ) );
			if ( '' !== $dynamic_css ) {
				$style_tag = '<style>' . $dynamic_css . '</style>';
			}

			if ( '' !== $config->get_heading() || '' !== $config->get_caption() ) {
				$header_markup = sprintf(
					'<header class="sp-lcpro-block-app__header"><h3 class="sp-lcpro-block-app__heading">%1$s</h3><p class="sp-lcpro-block-app__caption">%2$s</p></header>',
					esc_html( $config->get_heading() ),
					esc_html( $config->get_caption() )
				);
			}

			$section = sprintf(
				'<section%5$s class="%1$s" data-sp-lcpro-block="%2$s" data-sp-lcpro-unique-id="%6$s"%7$s>%3$s%8$s<div class="sp-lcpro-block-app__body">%4$s</div></section>',
				esc_attr( implode( ' ', $classes ) ),
				esc_attr( $config->get_slug() ),
				$header_markup,
				$body_markup,
				$id_attr,
				esc_attr( $unique_id ),
				(string) $section_data,
				(string) $pre_body_markup
			);

			if ( '' !== $css_id ) {
				$outer_classes = array( 'sp-lcpro-block-outer' );
				if ( '' !== $align_class ) {
					$outer_classes[] = $align_class;
				}
				$section = '<div id="' . esc_attr( $css_id ) . '" class="' . esc_attr( implode( ' ', $outer_classes ) ) . '">' . $section . '</div>';
			}

			return $style_tag . $section;
		}

		/**
		 * Map the Gutenberg `align` attribute to a theme layout class.
		 *
		 * @param array<string, mixed> $attributes Block attributes.
		 * @return string `alignwide`, `alignfull`, or empty when unset/unsupported.
		 */
		private function resolve_align_class( array $attributes ) {
			if ( ! isset( $attributes['align'] ) || ! is_string( $attributes['align'] ) ) {
				return '';
			}

			$align = $attributes['align'];
			if ( 'wide' !== $align && 'full' !== $align ) {
				return '';
			}

			return 'align' . $align;
		}
	}
}
