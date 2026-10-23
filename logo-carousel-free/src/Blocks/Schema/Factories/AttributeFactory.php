<?php
/**
 * Small attribute-definition helpers for block schemas.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AttributeFactory' ) ) {
	/**
	 * Simple field factories for block attribute definitions.
	 */
	class AttributeFactory {
		/**
		 * Boolean attribute definition.
		 *
		 * @param bool $fallback fallback value.
		 * @return array<string, mixed>
		 */
		public static function boolean( $fallback = false ) {
			return array(
				'type'    => 'boolean',
				'default' => (bool) $fallback,
			);
		}

		/**
		 * Number attribute definition.
		 *
		 * @param int|float $fallback fallback value.
		 * @return array<string, mixed>
		 */
		public static function number( $fallback = 0 ) {
			return array(
				'type'    => 'number',
				'default' => $fallback,
			);
		}

		/**
		 * Integer attribute definition.
		 *
		 * @param int $fallback fallback value.
		 * @return array<string, mixed>
		 */
		public static function integer( $fallback = 0 ) {
			return array(
				'type'    => 'integer',
				'default' => (int) $fallback,
			);
		}

		/**
		 * Integer-like attribute that also accepts legacy SPRangeControl shapes.
		 *
		 * Some scalar range fields were historically saved as `{ "value": N }`
		 * when Reset wrote the whole `defaultValue` object. WordPress replaces
		 * an entire parent object attribute with its default when any nested
		 * property fails schema validation, so these fields must accept both
		 * shapes until existing content is re-saved.
		 *
		 * @param int $fallback fallback value.
		 * @return array<string, mixed>
		 */
		public static function integer_like( $fallback = 0 ) {
			return array(
				'type'    => array( 'integer', 'number', 'object' ),
				'default' => (int) $fallback,
			);
		}

		/**
		 * Responsive whole-number attribute (columns, rows — no unit).
		 *
		 * Keeps `integer_like`'s permissive type on purpose: the default is the
		 * per-device object, but content saved before the field became responsive
		 * holds a bare integer (or the legacy `{ value: N }` Reset shape), and
		 * WordPress swaps the WHOLE parent object for its default the moment one
		 * nested value fails validation. Accepting all three shapes is what keeps
		 * an existing block's own number instead of silently resetting it.
		 *
		 * Readers: `Support\ResponsiveCount` (PHP) / `utils/responsiveCount` (JS).
		 *
		 * @param int      $desktop Desktop fallback.
		 * @param int|null $tablet Tablet fallback (defaults to Desktop).
		 * @param int|null $mobile Mobile fallback (defaults to Tablet).
		 * @return array<string, mixed>
		 */
		public static function responsive_count( $desktop = 1, $tablet = null, $mobile = null ) {
			$tablet = null === $tablet ? $desktop : $tablet;
			$mobile = null === $mobile ? $tablet : $mobile;

			return array(
				'type'    => array( 'integer', 'number', 'object' ),
				'default' => array(
					'device' => array(
						'Desktop' => (int) $desktop,
						'Tablet'  => (int) $tablet,
						'Mobile'  => (int) $mobile,
					),
				),
			);
		}

		/**
		 * String attribute definition.
		 *
		 * @param string $fallback fallback value.
		 * @param array  $options String options.
		 * @return array<string, mixed>
		 */
		public static function string( $fallback = '', $options = array() ) {
			$attribute = array(
				'type'    => 'string',
				'default' => $fallback,
			);

			if ( ! empty( $options ) ) {
				$attribute['enum'] = array_values( $options );
			}

			return $attribute;
		}

		/**
		 * Array attribute definition.
		 *
		 * @param array $fallback fallback value.
		 * @param array $items Item schema.
		 * @return array<string, mixed>
		 */
		public static function array( array $fallback = array(), array $items = array() ) {
			$attribute = array(
				'type'    => 'array',
				'default' => $fallback,
			);

			if ( array() !== $items ) {
				$attribute['items'] = $items;
			}

			return $attribute;
		}

		/**
		 * Object attribute definition.
		 *
		 * @param array $fallback fallback value.
		 * @param array $properties Property schema map.
		 * @return array<string, mixed>
		 */
		public static function object( array $fallback = array(), array $properties = array() ) {
			$attribute = array(
				'type'    => 'object',
				'default' => $fallback,
			);

			if ( array() !== $properties ) {
				$attribute['properties'] = $properties;
			}

			return $attribute;
		}

		/**
		 * Color state object with normal/hover/active keys.
		 *
		 * @param string $normal fallback normal color.
		 * @param string $hover fallback hover color.
		 * @param string $active fallback active color.
		 * @return array<string, mixed>
		 */
		public static function colors( $normal = '', $hover = '', $active = '' ) {
			return self::object(
				array(
					'normal' => $normal,
					'hover'  => $hover,
					'active' => $active,
				)
			);
		}

		/**
		 * Simple normal/hover color object.
		 *
		 * @param string $normal fallback normal color.
		 * @param string $hover fallback hover color.
		 * @return array<string, mixed>
		 */
		public static function color_pair( $normal = '', $hover = '' ) {
			return self::object(
				array(
					'normal' => $normal,
					'hover'  => $hover,
				)
			);
		}

		/**
		 * Typography object definition.
		 *
		 * @param string $font_weight fallback font weight.
		 * @param string $style fallback font style.
		 * @param string $transform fallback text transform.
		 * @param string $decoration fallback text decoration.
		 * @param string $family fallback font family.
		 * @return array<string, mixed>
		 */
		public static function typography( $font_weight = '', $style = '', $transform = '', $decoration = '', $family = '' ) {
			return self::object(
				array(
					'family'     => $family,
					'fontWeight' => $font_weight,
					'style'      => $style,
					'transform'  => $transform,
					'decoration' => $decoration,
				)
			);
		}

		/**
		 * Single responsive value definition.
		 *
		 * @param mixed  $desktop Desktop value.
		 * @param mixed  $tablet Tablet value.
		 * @param mixed  $mobile Mobile value.
		 * @param string $unit Shared unit.
		 * @return array<string, mixed>
		 */
		public static function single_responsive( $desktop = '', $tablet = '', $mobile = '', $unit = 'px' ) {
			$fallback = array(
				'device' => array(
					'Desktop' => $desktop,
					'Tablet'  => $tablet,
					'Mobile'  => $mobile,
				),
			);

			if ( '' !== $unit ) {
				$fallback['unit'] = array(
					'Desktop' => $unit,
					'Tablet'  => $unit,
					'Mobile'  => $unit,
				);
			}

			return self::object( $fallback );
		}

		/**
		 * Spacing box with one non-responsive value bucket.
		 *
		 * @param mixed  $top Top value.
		 * @param mixed  $right Right value.
		 * @param mixed  $bottom Bottom value.
		 * @param mixed  $left Left value.
		 * @param string $unit Shared unit.
		 * @param bool   $all_change Whether the UI should link all sides.
		 * @return array<string, mixed>
		 */
		public static function spacing( $top = '', $right = '', $bottom = '', $left = '', $unit = 'px', $all_change = true ) {
			return self::object(
				array(
					'value'     => array(
						'top'    => $top,
						'right'  => $right,
						'bottom' => $bottom,
						'left'   => $left,
					),
					'unit'      => $unit,
					'allChange' => $all_change,
				)
			);
		}

		/**
		 * Responsive spacing box definition.
		 *
		 * @param mixed  $top Top desktop value.
		 * @param mixed  $right Right desktop value.
		 * @param mixed  $bottom Bottom desktop value.
		 * @param mixed  $left Left desktop value.
		 * @param string $unit Shared unit.
		 * @param bool   $all_change Whether the UI should link all sides.
		 * @return array<string, mixed>
		 */
		public static function responsive_spacing( $top = '', $right = '', $bottom = '', $left = '', $unit = 'px', $all_change = true ) {
			return self::object(
				array(
					'device'    => array(
						'Desktop' => array(
							'top'    => $top,
							'right'  => $right,
							'bottom' => $bottom,
							'left'   => $left,
						),
						'Tablet'  => array(
							'top'    => '',
							'right'  => '',
							'bottom' => '',
							'left'   => '',
						),
						'Mobile'  => array(
							'top'    => '',
							'right'  => '',
							'bottom' => '',
							'left'   => '',
						),
					),
					'unit'      => array(
						'Desktop' => $unit,
						'Tablet'  => $unit,
						'Mobile'  => $unit,
					),
					'allChange' => $all_change,
				)
			);
		}

		/**
		 * Border definition.
		 *
		 * @param string $style fallback style.
		 * @param string $color fallback color.
		 * @return array<string, mixed>
		 */
		public static function border( $style = 'none', $color = '#cccccc' ) {
			return self::object(
				array(
					'style' => $style,
					'color' => $color,
				)
			);
		}

		/**
		 * Box shadow definition.
		 *
		 * @param bool   $is_active Whether the shadow is enabled.
		 * @param mixed  $top Top offset.
		 * @param mixed  $right Right offset.
		 * @param mixed  $bottom Bottom offset.
		 * @param mixed  $left Left offset.
		 * @param string $color fallback shadow color.
		 * @param string $unit Shadow mode/unit.
		 * @return array<string, mixed>
		 */
		public static function box_shadow( $is_active = false, $top = '', $right = '', $bottom = '', $left = '', $color = '#4E4F521A', $unit = 'Outset' ) {
			return self::object(
				array(
					'value'          => array(
						'top'    => $top,
						'right'  => $right,
						'bottom' => $bottom,
						'left'   => $left,
					),
					'unit'           => $unit,
					'color'          => $color,
					'isActive'       => (bool) $is_active,
					'selectfallback' => '',
				)
			);
		}
	}
}
