<?php
/**
 * Pagination dots config normalization.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\SectionConfigInterface;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PaginationConfig' ) ) {
	/**
	 * Normalize the pagination dots section.
	 */
	class PaginationConfig implements SectionConfigInterface {
		const ALLOWED_STYLES = array( 'style-1', 'style-2', 'style-3', 'style-4', 'style-5', 'style-6' );

		/**
		 * Build the normalized pagination payload.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @return array<string, mixed>
		 */
		public function normalize( array $attributes ) {
			$pagination = isset( $attributes['pagination'] ) && is_array( $attributes['pagination'] )
				? $attributes['pagination']
				: array();

			$style = isset( $pagination['paginationStyle'] ) && in_array( $pagination['paginationStyle'], self::ALLOWED_STYLES, true )
				? (string) $pagination['paginationStyle']
				: 'style-1';

			$hide_on_mobile = $this->normalize_boolean(
				isset( $pagination['hideOnMobile'] ) ? $pagination['hideOnMobile'] : null,
				true
			);

			return array(
				'paginationStyle' => $style,
				'rootClass'       => 'sp-lcpro-pagination--' . $style,
				'hideOnMobile'    => $hide_on_mobile,
			);
		}

		/**
		 * Normalize a boolean-like value.
		 *
		 * @param mixed $value Raw value.
		 * @param bool  $fallback Default value.
		 * @return bool
		 */
		private function normalize_boolean( $value, $fallback ) {
			if ( is_bool( $value ) ) {
				return $value;
			}

			if ( null === $value || '' === $value ) {
				return (bool) $fallback;
			}

			$normalized = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			if ( null === $normalized ) {
				return (bool) $fallback;
			}

			return (bool) $normalized;
		}
	}
}
