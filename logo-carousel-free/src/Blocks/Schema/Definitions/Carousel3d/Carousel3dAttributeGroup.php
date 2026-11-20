<?php
/**
 * 3D Carousel attribute group.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions\Carousel3d;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\Carousel3dDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\Carousel3dAttributeGroup', false ) ) {
	/**
	 * The `carousel3d` branch: style, side items, sliding effect, autoplay
	 * direction, grab cursor and one settings profile per style.
	 *
	 * Everything else the 3D block runs on (autoplay, speed, loop, keyboard,
	 * mousewheel, edge fade, arrows, dots, ajax toggles) stays on the shared
	 * carousel `layout` branch, so the shared Swiper config keeps owning it.
	 */
	class Carousel3dAttributeGroup {
		/**
		 * Return the attribute definition.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function definition() {
			$profiles = array();

			foreach ( Carousel3dDefaults::STYLES as $style ) {
				$profiles[ $style ] = AttributeFactory::object(
					Carousel3dDefaults::get_style_defaults( $style ),
					self::profile_properties( $style )
				);
			}

			return array(
				'carousel3d' => AttributeFactory::object(
					Carousel3dDefaults::get_defaults(),
					array_merge(
						array(
							'style'             => AttributeFactory::string( 'coverflow', Carousel3dDefaults::STYLES ),
							'sideItems'         => self::responsive_number( Carousel3dDefaults::side_items()['default'] ),
							'slidingEffect'     => AttributeFactory::string( 'none', Carousel3dDefaults::EFFECTS ),
							'autoplayDirection' => AttributeFactory::string( 'right-to-left', Carousel3dDefaults::DIRECTIONS ),
							'grabCursor'        => AttributeFactory::boolean( false ),
						),
						$profiles
					)
				),
			);
		}

		/**
		 * Property map for one style profile.
		 *
		 * @param string $style Style key.
		 * @return array<string, array<string, mixed>>
		 */
		private static function profile_properties( $style ) {
			$fields     = Carousel3dDefaults::fields();
			$reflects   = Carousel3dDefaults::reflection_defaults();
			$properties = array();

			foreach ( array_merge( $fields[ $style ], Carousel3dDefaults::reflection_fields() ) as $field => $spec ) {
				$properties[ $field ] = self::responsive_number( $spec['default'] );
			}

			$properties['showReflection'] = AttributeFactory::boolean( ! empty( $reflects[ $style ] ) );

			return $properties;
		}

		/**
		 * Responsive number definition.
		 *
		 * Typed like `AttributeFactory::responsive_count()` but keeps decimals
		 * (Active Logo Scale steps by 0.05).
		 *
		 * @param array<int, int|float> $values Desktop, Tablet, Mobile defaults.
		 * @return array<string, mixed>
		 */
		private static function responsive_number( array $values ) {
			return array(
				'type'    => array( 'integer', 'number', 'object' ),
				'default' => Carousel3dDefaults::responsive( $values ),
			);
		}
	}
}
