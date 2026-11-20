<?php
/**
 * 3D Carousel attribute defaults.
 *
 * Single source of truth for the `carousel3d` branch: the schema builds its
 * defaults from here and `Carousel3dConfig` clamps against the same ranges.
 * Mirrored in JS by `blocks/blocks/shared/carousel3d/carousel3dConfig.js`.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\Carousel3dDefaults', false ) ) {
	/**
	 * Defaults and ranges for the 3D Carousel block.
	 */
	class Carousel3dDefaults {
		/**
		 * Carousel styles.
		 *
		 * @var array<int, string>
		 */
		const STYLES = array( 'coverflow', 'perspective', 'ring' );

		/**
		 * Sliding effects (same keys as the Carousel block).
		 *
		 * @var array<int, string>
		 */
		const EFFECTS = array( 'none', 'slide', 'ease-in-out-slide', 'spring-bounce', 'snappy', 'fade', 'slide-fade' );

		/**
		 * Autoplay directions.
		 *
		 * @var array<int, string>
		 */
		const DIRECTIONS = array( 'right-to-left', 'left-to-right' );

		/**
		 * Device keys, in the order the responsive defaults are listed.
		 *
		 * @var array<int, string>
		 */
		const DEVICES = array( 'Desktop', 'Tablet', 'Mobile' );

		/**
		 * Side Items spec (Coverflow + Perspective).
		 *
		 * @return array<string, mixed>
		 */
		public static function side_items() {
			return array(
				'default' => array( 2, 2, 1 ),
				'min'     => 1,
				'max'     => 4,
			);
		}

		/**
		 * Per-style slider specs: responsive default + clamp range.
		 *
		 * @return array<string, array<string, array<string, mixed>>>
		 */
		public static function fields() {
			return array(
				'coverflow'   => array(
					'activeScale'      => self::spec( array( 1, 1, 1 ), 1, 2 ),
					'centerDistance'   => self::spec( array( 178, 178, 178 ), 0, 800 ),
					'slidesSpacing'    => self::spec( array( 217, 217, 217 ), 0, 400 ),
					'slidesDepth'      => self::spec( array( 40, 40, 40 ), 0, 200 ),
					'sidesPerspective' => self::spec( array( 1000, 1000, 1000 ), 200, 3000 ),
					'sidesRotation'    => self::spec( array( 12, 12, 12 ), 0, 90 ),
				),
				'perspective' => array(
					'centerDistance'   => self::spec( array( 43, 32, 16 ), 0, 800 ),
					'slidesSpacing'    => self::spec( array( 43, 32, 16 ), 0, 400 ),
					'slidesDepth'      => self::spec( array( 33, 33, 33 ), 0, 200 ),
					'sidesPerspective' => self::spec( array( 800, 800, 800 ), 200, 3000 ),
					'sidesRotation'    => self::spec( array( 25, 25, 25 ), 0, 90 ),
				),
				'ring'        => array(
					'xRadius'     => self::spec( array( 420, 300, 160 ), 0, 1000 ),
					'yRadius'     => self::spec( array( 300, 220, 140 ), 0, 1000 ),
					'yOffset'     => self::spec( array( 90, 90, 90 ), -200, 200 ),
					'xRotation'   => self::spec( array( 0, 0, 0 ), -60, 60 ),
					'backOpacity' => self::spec( array( 25, 25, 25 ), 0, 100 ),
				),
			);
		}

		/**
		 * Reflection slider specs (stored per style).
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function reflection_fields() {
			return array(
				'reflectionDistance' => self::spec( array( 4, 4, 4 ), 0, 60 ),
				'reflectionHeight'   => self::spec( array( 40, 40, 40 ), 0, 100 ),
				'reflectionOpacity'  => self::spec( array( 30, 30, 30 ), 0, 100 ),
			);
		}

		/**
		 * Show Reflection default per style.
		 *
		 * @return array<string, bool>
		 */
		public static function reflection_defaults() {
			return array(
				'coverflow'   => false,
				'perspective' => false,
				'ring'        => true,
			);
		}

		/**
		 * Full attribute default for the `carousel3d` branch.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_defaults() {
			$defaults = array(
				'style'             => 'coverflow',
				'sideItems'         => self::responsive( self::side_items()['default'] ),
				'slidingEffect'     => 'none',
				'autoplayDirection' => 'right-to-left',
				'grabCursor'        => false,
			);

			foreach ( self::STYLES as $style ) {
				$defaults[ $style ] = self::get_style_defaults( $style );
			}

			return $defaults;
		}

		/**
		 * Attribute default for one style profile.
		 *
		 * @param string $style Style key.
		 * @return array<string, mixed>
		 */
		public static function get_style_defaults( $style ) {
			$fields   = self::fields();
			$profile  = array();
			$specs    = isset( $fields[ $style ] ) ? $fields[ $style ] : array();
			$reflects = self::reflection_defaults();

			foreach ( array_merge( $specs, self::reflection_fields() ) as $field => $spec ) {
				$profile[ $field ] = self::responsive( $spec['default'] );
			}

			$profile['showReflection'] = ! empty( $reflects[ $style ] );

			return $profile;
		}

		/**
		 * Responsive value in the shape SPRangeControl writes.
		 *
		 * @param array<int, int|float> $values Desktop, Tablet, Mobile.
		 * @return array<string, array<string, int|float>>
		 */
		public static function responsive( array $values ) {
			return array(
				'device' => array(
					'Desktop' => $values[0],
					'Tablet'  => $values[1],
					'Mobile'  => $values[2],
				),
			);
		}

		/**
		 * Build one field spec.
		 *
		 * @param array<int, int|float> $values Desktop, Tablet, Mobile defaults.
		 * @param int|float             $min Lower clamp.
		 * @param int|float             $max Upper clamp.
		 * @return array<string, mixed>
		 */
		private static function spec( array $values, $min, $max ) {
			return array(
				'default' => $values,
				'min'     => $min,
				'max'     => $max,
			);
		}
	}
}
