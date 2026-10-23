<?php
/**
 * Pagination Dots CSS concern (server side).
 *
 * Ownership: bullet SIZE + Settings-tab gap plus the Style-tab paint controls.
 * Base shape still lives in `_pagination.scss`.
 *
 * `gapBetweenDots` is emitted as CSS `gap` on the pagination container (not
 * bullet margin). It is NOT emitted for `style-5` (Swiper scrollbar).
 *
 * Width/Height are absolute pixel sizes on bullets (styles 1–4, 6). Style-5
 * uses Height only for the scrollbar track thickness.
 *
 * PARITY: byte-identical with the editor emitter
 * `blocks/blocks/shared/styles/concerns/paginationDynamicCss.js` — same
 * selectors, value resolution, property order, and device buckets. Enforced by
 * CarouselFamilyDynamicCssTest.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PaginationCss' ) ) {
	/**
	 * Pagination bullet size/gap rules mirrored from the editor CSS concern.
	 */
	class PaginationCss {
		/**
		 * Base (Desktop) rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function base_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Desktop' );
		}

		/**
		 * Tablet rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function tablet_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Tablet' );
		}

		/**
		 * Mobile rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Mobile' );
		}

		/**
		 * Build the pagination bullet rules for a device bucket.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @param string                $device Device key (Desktop|Tablet|Mobile).
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		private static function get_rules( array $selectors, array $attributes, $device ) {
			$pagination = isset( $attributes['pagination'] ) && is_array( $attributes['pagination'] ) ? $attributes['pagination'] : array();
			$style      = isset( $pagination['paginationStyle'] ) ? (string) $pagination['paginationStyle'] : 'style-1';
			$style_group = self::style_group( $style );
			$is_number_style = 'number' === $style_group;
			// Style-3 draws the active dot inside a ring. The ring is an `outline`,
			// so it has to follow the Active background colour too — otherwise it
			// keeps the static design colour and clashes with a recoloured dot.
			$is_ring_style = 'style-3' === $style;
			// Border + Border Radius are number-style only (style-6) — mirrors
			// paginationDynamicCss.js and the control gate in PaginationTab.jsx.
			// The other styles are small round dots / pills whose shape is owned
			// by the static per-style CSS, and the editor hides both controls for
			// them, so a border saved earlier must stop rendering too.
			$supports_border = $is_number_style;
			$is_dynamic_style = 'style-2' === $style;
			$border = isset( $pagination['border'] ) && is_array( $pagination['border'] ) ? $pagination['border'] : array();
			$border_style = isset( $border['style'] ) ? (string) $border['style'] : '';
			$margin_box       = isset( $pagination['margin'] ) && is_array( $pagination['margin'] ) ? $pagination['margin'] : array();
			$container_margin = self::is_box_default( $margin_box, $device, 0 )
				? ''
				: self::box_responsive( $margin_box, $device );
			$margin_top    = '' !== $container_margin ? self::box_side( $margin_box, $device, 'top' ) : '';
			$margin_bottom = '' !== $container_margin ? self::box_side( $margin_box, $device, 'bottom' ) : '';
			$offset_y      = ( '' !== $margin_top || '' !== $margin_bottom )
				? 'calc(' . ( '' !== $margin_top ? $margin_top : '0px' ) . ' + ' . ( '' !== $margin_bottom ? $margin_bottom : '0px' ) . ')'
				: '';

			// Style-5: paint the scrollbar track + drag thumb instead of bullets.
			// Height is the absolute track thickness (Width is unused for scrollbar).
			if ( 'style-5' === $style ) {
				$scrollbar_selector = self::join_selectors(
					isset( $selectors['scrollbar'] ) ? $selectors['scrollbar'] : '',
					isset( $selectors['externalScrollbar'] ) ? $selectors['externalScrollbar'] : ''
				);
				$drag_selector = self::join_selectors(
					isset( $selectors['scrollbarDrag'] ) ? $selectors['scrollbarDrag'] : '',
					isset( $selectors['externalScrollbarDrag'] ) ? $selectors['externalScrollbarDrag'] : ''
				);
				$track_height = self::single_responsive( isset( $pagination['height'] ) && is_array( $pagination['height'] ) ? $pagination['height'] : array(), $device );
				$track_height_value = '' !== $track_height ? $track_height : '5px';

				return array(
					array(
						'selector' => isset( $selectors['wrapper'] ) ? $selectors['wrapper'] : '',
						'styles'   => array(
							'--sp-lcpro-pag-offset-y' => $offset_y,
						),
					),
					array(
						'selector' => $scrollbar_selector,
						'styles'   => array(
							'--sp-lcpro-pg-h'          => $track_height_value,
							'--swiper-scrollbar-size' => $track_height_value,
							'height'                  => $track_height_value,
							'background'              => self::scrollbar_state_color( isset( $pagination['backgroundColor'] ) && is_array( $pagination['backgroundColor'] ) ? $pagination['backgroundColor'] : array(), 'normal', '#e5e5e5' ),
							'margin'                  => $container_margin,
						),
					),
					array(
						'selector' => $drag_selector,
						'styles'   => array(
							'background' => self::scrollbar_state_color( isset( $pagination['backgroundColor'] ) && is_array( $pagination['backgroundColor'] ) ? $pagination['backgroundColor'] : array(), 'active', '#757575' ),
						),
					),
				);
			}

			$bullet_selector = self::join_selectors(
				isset( $selectors['paginationBullet'] ) ? $selectors['paginationBullet'] : '',
				isset( $selectors['externalPaginationBullet'] ) ? $selectors['externalPaginationBullet'] : ''
			);
			$active_selector = self::join_selectors(
				isset( $selectors['paginationBulletActive'] ) ? $selectors['paginationBulletActive'] : '',
				isset( $selectors['externalPaginationBulletActive'] ) ? $selectors['externalPaginationBulletActive'] : ''
			);
			$container_selector = isset( $selectors['swiperPagination'] ) ? $selectors['swiperPagination'] : ( isset( $selectors['paginationContainer'] ) ? $selectors['paginationContainer'] : '' );
			$gap_value = self::single_responsive( isset( $pagination['gapBetweenDots'] ) && is_array( $pagination['gapBetweenDots'] ) ? $pagination['gapBetweenDots'] : array(), $device );
			// Style-2 dynamic bullets use `display:block` + transforms — CSS `gap`
			// does not apply, so fall back to half-margin on each bullet.
			$bullet_margin = $is_dynamic_style
				? self::gap_margin( isset( $pagination['gapBetweenDots'] ) && is_array( $pagination['gapBetweenDots'] ) ? $pagination['gapBetweenDots'] : array(), $device )
				: '0';
			$container_gap = $is_dynamic_style ? '' : $gap_value;
			$bullet_width  = self::single_responsive( isset( $pagination['width'] ) && is_array( $pagination['width'] ) ? $pagination['width'] : array(), $device );
			$bullet_height = self::single_responsive( isset( $pagination['height'] ) && is_array( $pagination['height'] ) ? $pagination['height'] : array(), $device );
			$border_width = $supports_border && '' !== $border_style && 'none' !== $border_style
				? self::box_responsive( isset( $pagination['borderWidth'] ) && is_array( $pagination['borderWidth'] ) ? $pagination['borderWidth'] : array(), $device )
				: '';
			$border_radius = $supports_border && ! self::is_box_default( isset( $pagination['borderRadius'] ) && is_array( $pagination['borderRadius'] ) ? $pagination['borderRadius'] : array(), $device, 3 )
				? self::box_responsive( isset( $pagination['borderRadius'] ) && is_array( $pagination['borderRadius'] ) ? $pagination['borderRadius'] : array(), $device )
				: '';

			$active_background = self::state_color( isset( $pagination['backgroundColor'] ) && is_array( $pagination['backgroundColor'] ) ? $pagination['backgroundColor'] : array(), 'active', '#557182' );

			$bullet_styles = array(
				'--sp-lcpro-pg-w' => $bullet_width,
				'--sp-lcpro-pg-h' => $bullet_height,
				'width'           => $bullet_width,
				'height'          => $bullet_height,
				'margin'          => $bullet_margin,
				'background'      => self::state_color( isset( $pagination['backgroundColor'] ) && is_array( $pagination['backgroundColor'] ) ? $pagination['backgroundColor'] : array(), 'normal', '#9fb3bf' ),
				'color'           => $is_number_style ? self::state_color( isset( $pagination['textColor'] ) && is_array( $pagination['textColor'] ) ? $pagination['textColor'] : array(), 'normal', '#ffffff' ) : '',
				'border-style'    => $supports_border && '' !== $border_style && 'none' !== $border_style ? $border_style : '',
				'border-color'    => $supports_border && '' !== $border_style && 'none' !== $border_style ? ( isset( $border['color'] ) ? (string) $border['color'] : '#9fb3bf' ) : '',
				'border-width'    => $border_width,
				'border-radius'   => $border_radius,
			);

			if ( $is_number_style ) {
				$bullet_styles = array_merge(
					$bullet_styles,
					self::typography_styles( isset( $pagination['typography'] ) && is_array( $pagination['typography'] ) ? $pagination['typography'] : array(), $device )
				);
			}

			return array(
				array(
					'selector' => isset( $selectors['wrapper'] ) ? $selectors['wrapper'] : '',
					'styles'   => array(
						'--sp-lcpro-pag-offset-y' => $offset_y,
					),
				),
				array(
					'selector' => $bullet_selector,
					'styles'   => $bullet_styles,
				),
				array(
					'selector' => $active_selector,
					'styles'   => array(
						'background'    => $active_background,
						// Style-3 only — `_pagination.scss` owns width/style/offset.
						'outline-color' => $is_ring_style ? $active_background : '',
						'color'         => $is_number_style ? self::state_color( isset( $pagination['textColor'] ) && is_array( $pagination['textColor'] ) ? $pagination['textColor'] : array(), 'active', '#ffffff' ) : '',
						'border-color'  => $supports_border && '' !== $border_style && 'none' !== $border_style ? ( isset( $border['activeColor'] ) ? (string) $border['activeColor'] : ( isset( $border['color'] ) ? (string) $border['color'] : '#557182' ) ) : '',
					),
				),
				array(
					'selector' => $container_selector,
					'styles'   => array(
						'gap' => $container_gap,
					),
				),
				array(
					'selector' => $container_selector . ':not(.swiper-pagination-vertical)',
					'styles'   => array(
						'margin' => $container_margin,
					),
				),
			);
		}

		/**
		 * Resolve a single-responsive value into a CSS value for a device.
		 *
		 * @param array<string, mixed> $responsive Single-responsive branch.
		 * @param string               $device Device key.
		 * @return string
		 */
		private static function single_responsive( array $responsive, $device ) {
			if ( ! isset( $responsive['device'][ $device ] ) ) {
				return '';
			}

			$value = $responsive['device'][ $device ];
			if ( '' === $value || null === $value ) {
				return '';
			}

			$unit = isset( $responsive['unit'][ $device ] ) ? (string) $responsive['unit'][ $device ] : 'px';

			return $value . $unit;
		}

		/**
		 * Resolve a responsive four-side control into a CSS shorthand.
		 *
		 * @param array<string, mixed> $responsive Responsive spacing branch.
		 * @param string               $device Device key.
		 * @return string
		 */
		private static function box_responsive( array $responsive, $device ) {
			if ( isset( $responsive['device'][ $device ] ) && is_array( $responsive['device'][ $device ] ) ) {
				$value = $responsive['device'][ $device ];
			} elseif ( isset( $responsive['value'] ) && is_array( $responsive['value'] ) ) {
				$value = $responsive['value'];
			} else {
				return '';
			}

			$unit = 'px';
			if ( isset( $responsive['unit'][ $device ] ) ) {
				$unit = (string) $responsive['unit'][ $device ];
			} elseif ( isset( $responsive['unit'] ) && is_string( $responsive['unit'] ) ) {
				$unit = $responsive['unit'];
			}

			$sides = array();
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				$side_value = isset( $value[ $side ] ) ? $value[ $side ] : 0;
				if ( '' === $side_value || null === $side_value ) {
					$side_value = 0;
				}
				$sides[] = $side_value . $unit;
			}

			return implode( ' ', $sides );
		}

		/**
		 * Resolve a single side of a responsive four-side control.
		 *
		 * @param array<string, mixed> $responsive Responsive spacing branch.
		 * @param string               $device Device key.
		 * @param string               $side Side key (top|right|bottom|left).
		 * @return string
		 */
		private static function box_side( array $responsive, $device, $side ) {
			if ( isset( $responsive['device'][ $device ] ) && is_array( $responsive['device'][ $device ] ) ) {
				$value = $responsive['device'][ $device ];
			} elseif ( isset( $responsive['value'] ) && is_array( $responsive['value'] ) ) {
				$value = $responsive['value'];
			} else {
				return '';
			}

			if ( ! isset( $value[ $side ] ) || '' === $value[ $side ] || null === $value[ $side ] ) {
				return '';
			}

			$unit = 'px';
			if ( isset( $responsive['unit'][ $device ] ) ) {
				$unit = (string) $responsive['unit'][ $device ];
			} elseif ( isset( $responsive['unit'] ) && is_string( $responsive['unit'] ) ) {
				$unit = $responsive['unit'];
			}

			return $value[ $side ] . $unit;
		}

		/**
		 * Detect an untouched responsive four-side default.
		 *
		 * @param array<string, mixed> $responsive Responsive spacing branch.
		 * @param string               $device Device key.
		 * @param int|float            $default_value Default side value.
		 * @return bool
		 */
		private static function is_box_default( array $responsive, $device, $default_value = 0 ) {
			if ( isset( $responsive['device'][ $device ] ) && is_array( $responsive['device'][ $device ] ) ) {
				$value = $responsive['device'][ $device ];
			} elseif ( isset( $responsive['value'] ) && is_array( $responsive['value'] ) ) {
				$value = $responsive['value'];
			} else {
				return false;
			}

			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				$side_value = isset( $value[ $side ] ) ? $value[ $side ] : '';
				if ( '' !== $side_value && null !== $side_value && (float) $side_value !== (float) $default_value ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Resolve a Normal/Active color branch.
		 *
		 * @param array<string, mixed> $colors Color branch.
		 * @param string               $state State key.
		 * @param string               $fallback Fallback color.
		 * @return string
		 */
		private static function state_color( array $colors, $state, $fallback ) {
			return isset( $colors[ $state ] ) && '' !== $colors[ $state ] ? (string) $colors[ $state ] : $fallback;
		}

		/**
		 * Style-5 scrollbar colors. Shared pagination defaults (#9fb3bf / #557182)
		 * are the bullet palette — treat them as unset so the slim gray track/thumb
		 * design shows until the user picks custom Style-tab colors.
		 *
		 * @param array<string, string> $colors Color map.
		 * @param string                $state State key.
		 * @param string                $fallback Design fallback color.
		 * @return string
		 */
		private static function scrollbar_state_color( array $colors, $state, $fallback ) {
			$shared_defaults = array(
				'normal' => '#9fb3bf',
				'active' => '#557182',
			);
			$value = isset( $colors[ $state ] ) ? (string) $colors[ $state ] : '';

			if ( '' === $value || ( isset( $shared_defaults[ $state ] ) && $value === $shared_defaults[ $state ] ) ) {
				return $fallback;
			}

			return $value;
		}

		/**
		 * Group pagination styles by their available Style-tab controls.
		 *
		 * @param string $style Pagination style ID.
		 * @return string
		 */
		private static function style_group( $style ) {
			if ( 'style-6' === $style ) {
				return 'number';
			}

			if ( in_array( $style, array( 'style-4', 'style-5' ), true ) ) {
				return 'bar';
			}

			return 'dot';
		}

		/**
		 * Join non-empty selectors.
		 *
		 * @param string ...$selectors Selectors.
		 * @return string
		 */
		private static function join_selectors( ...$selectors ) {
			return implode( ', ', array_filter( $selectors ) );
		}

		/**
		 * Resolve typography controls for number pagination.
		 *
		 * @param array<string, mixed> $typography Typography branch.
		 * @param string               $device Device key.
		 * @return array<string, string>
		 */
		private static function typography_styles( array $typography, $device ) {
			$family     = isset( $typography['family'] ) && is_array( $typography['family'] ) ? $typography['family'] : array();
			$font_family = isset( $family['family'] ) && 'Default' !== $family['family'] ? (string) $family['family'] : '';
			$transform  = isset( $family['textTransform'] ) ? (string) $family['textTransform'] : ( isset( $family['transform'] ) ? (string) $family['transform'] : '' );
			$decoration = isset( $family['textDecoration'] ) ? (string) $family['textDecoration'] : ( isset( $family['decoration'] ) ? (string) $family['decoration'] : '' );

			return array(
				'font-family'     => $font_family,
				'font-weight'     => isset( $family['fontWeight'] ) ? (string) $family['fontWeight'] : '',
				'font-style'      => isset( $family['style'] ) ? (string) $family['style'] : '',
				'text-transform'  => $transform,
				'text-decoration' => $decoration,
				'font-size'       => self::single_responsive( isset( $typography['fontSize'] ) && is_array( $typography['fontSize'] ) ? $typography['fontSize'] : array(), $device ),
				'line-height'     => self::line_height( isset( $typography['lineHeight'] ) && is_array( $typography['lineHeight'] ) ? $typography['lineHeight'] : array(), $device ),
				'letter-spacing'  => self::spacing_value( isset( $typography['letterSpacing'] ) && is_array( $typography['letterSpacing'] ) ? $typography['letterSpacing'] : array() ),
				'word-spacing'    => self::spacing_value( isset( $typography['wordSpacing'] ) && is_array( $typography['wordSpacing'] ) ? $typography['wordSpacing'] : array() ),
			);
		}

		/**
		 * Resolve line-height from either responsive or scalar typography shape.
		 *
		 * @param array<string, mixed> $line_height Line-height branch.
		 * @param string               $device Device key.
		 * @return string
		 */
		private static function line_height( array $line_height, $device ) {
			$value = null;
			if ( isset( $line_height['device'][ $device ] ) ) {
				$value = $line_height['device'][ $device ];
			} elseif ( isset( $line_height['value'] ) ) {
				$value = $line_height['value'];
			}

			if ( '' === $value || null === $value ) {
				return '';
			}

			return (string) $value;
		}

		/**
		 * Resolve a non-responsive spacing value (letter/word spacing).
		 * Falls back to the legacy per-device shape for previously saved content.
		 *
		 * @param array<string, mixed> $spacing Spacing object.
		 * @return string
		 */
		private static function spacing_value( array $spacing ) {
			if ( isset( $spacing['value'] ) && '' !== $spacing['value'] && null !== $spacing['value'] ) {
				$unit = isset( $spacing['unit'] ) && is_string( $spacing['unit'] ) ? $spacing['unit'] : 'px';
				return $spacing['value'] . $unit;
			}

			if ( isset( $spacing['device']['Desktop'] ) && '' !== $spacing['device']['Desktop'] && null !== $spacing['device']['Desktop'] ) {
				$unit = isset( $spacing['unit']['Desktop'] ) ? (string) $spacing['unit']['Desktop'] : 'px';
				return $spacing['device']['Desktop'] . $unit;
			}

			return '';
		}

		/**
		 * Resolve the gap into a horizontal `margin` shorthand (half each side).
		 *
		 * @param array<string, mixed> $responsive Single-responsive gap branch.
		 * @param string               $device Device key.
		 * @return string
		 */
		private static function gap_margin( array $responsive, $device ) {
			if ( ! isset( $responsive['device'][ $device ] ) ) {
				return '';
			}

			$value = $responsive['device'][ $device ];
			if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
				return '';
			}

			$unit = isset( $responsive['unit'][ $device ] ) ? (string) $responsive['unit'][ $device ] : 'px';

			return '0 ' . ( $value / 2 ) . $unit;
		}
	}
}
