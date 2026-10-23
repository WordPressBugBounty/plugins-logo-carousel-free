<?php
/**
 * Shared server-side dynamic CSS composer for the carousel family blocks.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles;

use ShapedPlugin\LogoCarouselFree\Blocks\Support\UniqueId;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns\LayoutCss;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns\LogoCardCss;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns\LogoInfoPopupCss;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns\LogoImagesCss;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns\LogoSourceCss;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns\NavigationCss;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns\PaginationCss;
use ShapedPlugin\LogoCarouselFree\Blocks\Sections\GridLayout\GridLayoutCss;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns\SectionCss;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns\TypographyCss;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns\VisibilityCss;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CarouselFamilyDynamicCss' ) ) {
	/**
	 * Compose the CSS for the demo carousel family.
	 */
	class CarouselFamilyDynamicCss {
		/**
		 * Generate a CSS string for the requested block type.
		 *
		 * @param array<string, mixed> $attributes Block attributes.
		 * @param array<string, mixed> $options Block options.
		 * @return string
		 */
		public function generate( array $attributes, array $options = array() ) {
			$unique_id = UniqueId::from_attributes( $attributes );
			if ( '' === $unique_id ) {
				return '';
			}

			$block_type = isset( $options['blockType'] ) ? (string) $options['blockType'] : 'carousel';
			$features   = isset( $options['features'] ) && is_array( $options['features'] ) ? $options['features'] : array( 'section', 'layout', 'typography' );
			$selectors  = Selectors::create( $unique_id, $block_type );
			$styles     = $this->get_block_style_options( $block_type );

			$base_rules             = array();
			$desktop_rules          = array();
			$tablet_rules           = array();
			$mobile_rules           = array();
			$tablet_only_rules      = array();
			$mobile_portrait_rules  = array();
			$mobile_landscape_rules = array();

			// Base-level chrome (Advanced Settings → Section): always emitted for
			// every block, not gated behind a feature token.
			$base_rules   = array_merge( $base_rules, SectionCss::base_rules( $selectors, $attributes ) );
			$tablet_rules = array_merge( $tablet_rules, SectionCss::tablet_rules( $selectors, $attributes ) );
			$mobile_rules = array_merge( $mobile_rules, SectionCss::mobile_rules( $selectors, $attributes ) );

			$desktop_rules          = array_merge( $desktop_rules, VisibilityCss::desktop_rules( $selectors, $attributes ) );
			$tablet_only_rules      = array_merge( $tablet_only_rules, VisibilityCss::tablet_rules( $selectors, $attributes ) );
			$mobile_portrait_rules  = array_merge( $mobile_portrait_rules, VisibilityCss::mobile_portrait_rules( $selectors, $attributes ) );
			$mobile_landscape_rules = array_merge( $mobile_landscape_rules, VisibilityCss::mobile_landscape_rules( $selectors, $attributes ) );

			if ( in_array( 'layout', $features, true ) ) {
				$base_rules   = array_merge( $base_rules, LayoutCss::base_rules( $selectors, $styles['layout'] ) );
				$mobile_rules = array_merge( $mobile_rules, LayoutCss::mobile_rules( $selectors, $styles['layout'] ) );
			}

			if ( in_array( 'typography', $features, true ) ) {
				$base_rules   = array_merge( $base_rules, TypographyCss::base_rules( $selectors, $styles['typography'] ) );
				$mobile_rules = array_merge( $mobile_rules, TypographyCss::mobile_rules( $selectors, $styles['typography'] ) );
			}

			if ( in_array( 'logoSource', $features, true ) ) {
				$base_rules   = array_merge( $base_rules, LogoSourceCss::base_rules( $selectors ) );
				$tablet_rules = array_merge( $tablet_rules, LogoSourceCss::tablet_rules( $selectors ) );
				$mobile_rules = array_merge( $mobile_rules, LogoSourceCss::mobile_rules( $selectors ) );
			}

			if ( in_array( 'logoImages', $features, true ) ) {
				$base_rules   = array_merge( $base_rules, LogoImagesCss::base_rules( $selectors, $attributes ) );
				$tablet_rules = array_merge( $tablet_rules, LogoImagesCss::tablet_rules( $selectors, $attributes ) );
				$mobile_rules = array_merge( $mobile_rules, LogoImagesCss::mobile_rules( $selectors, $attributes ) );
			}

			if ( 'grid' === $block_type ) {
				$base_rules   = array_merge( $base_rules, GridLayoutCss::base_rules( $selectors, $attributes ) );
				$tablet_rules = array_merge( $tablet_rules, GridLayoutCss::tablet_rules( $selectors, $attributes ) );
				$mobile_rules = array_merge( $mobile_rules, GridLayoutCss::mobile_rules( $selectors, $attributes ) );
			}

			if ( in_array( 'navigation', $features, true ) ) {
				$base_rules   = array_merge( $base_rules, NavigationCss::base_rules( $selectors, $attributes ) );
				$tablet_rules = array_merge( $tablet_rules, NavigationCss::tablet_rules( $selectors, $attributes ) );
				$mobile_rules = array_merge( $mobile_rules, NavigationCss::mobile_rules( $selectors, $attributes ) );
			}

			if ( in_array( 'pagination', $features, true ) ) {
				$base_rules   = array_merge( $base_rules, PaginationCss::base_rules( $selectors, $attributes ) );
				$tablet_rules = array_merge( $tablet_rules, PaginationCss::tablet_rules( $selectors, $attributes ) );
				$mobile_rules = array_merge( $mobile_rules, PaginationCss::mobile_rules( $selectors, $attributes ) );
			}

			if ( in_array( 'logoCard', $features, true ) ) {
				$base_rules   = array_merge( $base_rules, LogoCardCss::base_rules( $selectors, $attributes ) );
				$tablet_rules = array_merge( $tablet_rules, LogoCardCss::tablet_rules( $selectors, $attributes ) );
				$mobile_rules = array_merge( $mobile_rules, LogoCardCss::mobile_rules( $selectors, $attributes ) );
			}

			if ( in_array( 'logoInfoPopup', $features, true ) ) {
				$base_rules   = array_merge( $base_rules, LogoInfoPopupCss::base_rules( $selectors, $attributes ) );
				$tablet_rules = array_merge( $tablet_rules, LogoInfoPopupCss::tablet_rules( $selectors, $attributes ) );
				$mobile_rules = array_merge( $mobile_rules, LogoInfoPopupCss::mobile_rules( $selectors, $attributes ) );
			}

			$base_css             = CssUtils::object_to_css_string( CssUtils::merge_css_rules_by_selector( $base_rules ) );
			$desktop_css          = CssUtils::object_to_css_string( CssUtils::merge_css_rules_by_selector( $desktop_rules ) );
			$tablet_css           = CssUtils::object_to_css_string( CssUtils::merge_css_rules_by_selector( $tablet_rules ) );
			$mobile_css           = CssUtils::object_to_css_string( CssUtils::merge_css_rules_by_selector( $mobile_rules ) );
			$tablet_only_css      = CssUtils::object_to_css_string( CssUtils::merge_css_rules_by_selector( $tablet_only_rules ) );
			$mobile_portrait_css  = CssUtils::object_to_css_string( CssUtils::merge_css_rules_by_selector( $mobile_portrait_rules ) );
			$mobile_landscape_css = CssUtils::object_to_css_string( CssUtils::merge_css_rules_by_selector( $mobile_landscape_rules ) );

			return trim(
				implode(
					"\n",
					array_filter(
						array(
							$base_css,
							CssUtils::wrap_in_media_query( $desktop_css, Breakpoints::DESKTOP ),
							CssUtils::wrap_in_media_query( $tablet_css, Breakpoints::TABLET ),
							CssUtils::wrap_in_media_query( $mobile_css, Breakpoints::MOBILE ),
							CssUtils::wrap_in_media_query( $tablet_only_css, Breakpoints::TABLET_ONLY ),
							CssUtils::wrap_in_media_query( $mobile_portrait_css, Breakpoints::MOBILE_PORTRAIT ),
							CssUtils::wrap_in_media_query( $mobile_landscape_css, Breakpoints::MOBILE_LANDSCAPE ),
						)
					)
				)
			);
		}

		/**
		 * Return the per-block style options.
		 *
		 * @param string $block_type Block type.
		 * @return array<string, array<string, mixed>>
		 */
		private function get_block_style_options( $block_type ) {
			return array(
				'layout'     => array(
					'gap'          => '20px',
					'borderRadius' => '12px',
					'border'       => '1px solid #c7d2fe',
					'background'   => 'linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%)',
					'boxShadow'    => '0 18px 40px rgba(15, 23, 42, 0.06)',
					'headerGap'    => '8px',
				),
				'typography' => array(
					'headingSize'       => '18px',
					'headingSizeMobile' => '17px',
					'captionSize'       => '14px',
					'captionSizeMobile' => '13px',
					'captionColor'      => '#64748b',
				),
			);
		}
	}
}
