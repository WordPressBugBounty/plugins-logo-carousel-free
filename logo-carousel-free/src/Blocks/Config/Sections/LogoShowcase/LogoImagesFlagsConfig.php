<?php
/**
 * Derive logo image feature flags.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoImagesFlagsConfig' ) ) {
	class LogoImagesFlagsConfig {
		public function derive( array $attributes, array $images_settings ) {
			return array(
				'has_images'              => $this->has_logo_images( $attributes ),
				'has_custom_aspect_ratio' => 'custom' === $images_settings['aspectRatio'],
				'is_random_order_enabled' => ! empty( $images_settings['randomOrder'] ),
				'is_lazy_load_enabled'    => ! empty( $images_settings['lazyLoad'] ),
				'has_click_action'        => 'none' !== $images_settings['clickAction'],
				'uses_popup_click_action' => 'popup' === $images_settings['clickAction'],
			);
		}
		private function has_logo_images( array $attributes ) {
			$source = isset( $attributes['logoSource'] ) && is_array( $attributes['logoSource'] ) ? $attributes['logoSource'] : array();
			$items  = isset( $source['items'] ) && is_array( $source['items'] ) ? $source['items'] : array();
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$image_url = isset( $item['imageUrl'] ) && ! is_array( $item['imageUrl'] ) && ! is_object( $item['imageUrl'] ) ? trim( (string) $item['imageUrl'] ) : '';
				if ( '' !== $image_url ) {
					return true;
				}
			}
			return false;
		}
	}
}
