<?php
/**
 * 3D Carousel config normalization (Free build).
 *
 * Resolves the raw `carousel3d` attribute branch into the payload the 3D
 * effect engine reads (`swiperConfig.carousel3d`). The free 3D Carousel is the
 * Coverflow stage with its default 3D settings (the engine holds them); Side
 * Items, the autoplay direction and the grab cursor are free. The Perspective
 * and Ring styles, the 3D Settings sliders (and reflection) and the sliding
 * effects are Pro: a block saved in Pro keeps those values in its attribute
 * (`Carousel3dAttributeGroup` still declares them), and they are not read here.
 *
 * Mirror of `blocks/blocks/shared/carousel3d/carousel3dConfig.js`
 * (`normalizeCarousel3d`) — both sides must produce the same shape and numbers.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\Carousel3d;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\SectionConfigInterface;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\Carousel3dDefaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\Carousel3dConfig', false ) ) {
	/**
	 * Normalize the 3D Carousel section.
	 */
	class Carousel3dConfig implements SectionConfigInterface {
		/**
		 * The one 3D style the free plugin draws.
		 *
		 * @var string
		 */
		const STYLE = 'coverflow';

		/**
		 * Build the normalized 3D payload.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @return array<string, mixed>
		 */
		public function normalize( array $attributes ) {
			$source = isset( $attributes['carousel3d'] ) && is_array( $attributes['carousel3d'] ) ? $attributes['carousel3d'] : array();

			return array(
				'style'             => self::STYLE,
				'autoplayDirection' => isset( $source['autoplayDirection'] ) && in_array( $source['autoplayDirection'], Carousel3dDefaults::DIRECTIONS, true ) ? (string) $source['autoplayDirection'] : 'right-to-left',
				'grabCursor'        => isset( $source['grabCursor'] ) && is_bool( $source['grabCursor'] ) ? $source['grabCursor'] : false,
				'sideItems'         => $this->side_items( isset( $source['sideItems'] ) ? $source['sideItems'] : null ),
			);
		}

		/**
		 * Pin the shared carousel `layout` to what the 3D stage runs on.
		 *
		 * The 3D block reuses the Carousel `layout` branch for its Swiper
		 * behaviors, but its Layout panel never shows the flat-carousel geometry
		 * fields. Content converted from a Carousel can still carry them (a fade
		 * effect, vertical orientation, the staggered style…), and each of those
		 * would switch on carousel-only markup/CSS that breaks the stage. Written
		 * flat AND into the profile containers, because the profile resolver
		 * reads `shared` / the active profile before the flat key.
		 *
		 * A layout saved without an autoplay value (pattern, conversion) gets the
		 * 3D default — off — instead of the Carousel's.
		 *
		 * Mirrored by `neutralizeCarousel3dLayout()` in carousel3dConfig.js.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @return array<string, mixed>
		 */
		public static function neutralize_layout( array $attributes ) {
			$layout   = isset( $attributes['layout'] ) && is_array( $attributes['layout'] ) ? $attributes['layout'] : array();
			$shared   = array(
				'displayOrientation' => 'horizontal',
				'partialSlideView'   => false,
				'variableWidth'      => false,
				'adaptiveHeight'     => false,
			);
			$standard = array(
				'carouselDirection' => 'left-to-right',
				'slidingEffects'    => 'none',
				'freeScrollMode'    => false,
			);

			$layout['carouselStyle'] = 'standard';
			$layout['shared']        = isset( $layout['shared'] ) && is_array( $layout['shared'] ) ? $layout['shared'] : array();

			if ( ! array_key_exists( 'autoplay', $layout['shared'] ) && ! array_key_exists( 'autoplay', $layout ) ) {
				$shared['autoplay'] = false;
			}

			$layout['shared']   = array_merge( $layout['shared'], $shared );
			$layout['standard'] = array_merge( isset( $layout['standard'] ) && is_array( $layout['standard'] ) ? $layout['standard'] : array(), $standard );
			$layout             = array_merge( $layout, $shared, $standard );

			$attributes['layout'] = $layout;

			return $attributes;
		}

		/**
		 * Resolve Side Items into a Desktop/Tablet/Mobile map.
		 *
		 * @param mixed $raw Raw `carousel3d.sideItems` attribute.
		 * @return array<string, int>
		 */
		private function side_items( $raw ) {
			$spec   = Carousel3dDefaults::side_items();
			$result = array();

			foreach ( Carousel3dDefaults::DEVICES as $index => $device ) {
				$result[ $device ] = $this->device_number( $raw, $device, $spec['default'][ $index ], $spec['min'], $spec['max'] );
			}

			return $result;
		}

		/**
		 * Resolve one device value.
		 *
		 * `{ device: {…} }` is the current shape; a legacy `{ value: N }` or a
		 * bare scalar only ever meant Desktop. A device without its own value
		 * takes that device's default.
		 *
		 * @param mixed  $raw Raw attribute value.
		 * @param string $device Device key.
		 * @param int    $fallback Default for this device.
		 * @param int    $min Lower clamp.
		 * @param int    $max Upper clamp.
		 * @return int
		 */
		private function device_number( $raw, $device, $fallback, $min, $max ) {
			$value = null;

			if ( is_array( $raw ) ) {
				if ( isset( $raw['device'] ) && is_array( $raw['device'] ) && array_key_exists( $device, $raw['device'] ) ) {
					$value = $this->finite_number( $raw['device'][ $device ] );
				}

				if ( null === $value && 'Desktop' === $device && array_key_exists( 'value', $raw ) ) {
					$value = $this->finite_number( $raw['value'] );
				}
			} elseif ( 'Desktop' === $device ) {
				$value = $this->finite_number( $raw );
			}

			$value = null === $value ? $fallback : $value;

			return (int) round( max( $min, min( $max, $value ) ) );
		}

		/**
		 * Parse a finite number, rejecting booleans and empty strings.
		 *
		 * @param mixed $value Raw value.
		 * @return int|float|null
		 */
		private function finite_number( $value ) {
			if ( is_bool( $value ) || null === $value || '' === $value || ! is_numeric( $value ) ) {
				return null;
			}

			$number = $value + 0;

			return is_finite( (float) $number ) ? $number : null;
		}
	}
}
