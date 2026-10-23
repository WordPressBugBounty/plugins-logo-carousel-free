<?php
/**
 * Logo card CSS concern.
 *
 * Paints the card drawn around each logo: background, border, radius and
 * inner padding, read from the `displayElements` card fields (the attribute
 * branch is shared with Pro, so a block keeps its card when it moves between
 * the two). The free plugin has no Display Elements content, so this is the
 * card surface only — no text, rating or Read More rules.
 *
 * 1:1 mirror of `blocks/blocks/shared/styles/concerns/logoCardDynamicCss.js`.
 *
 * @package Logo_Carousel_Free
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns;

use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssValueHelper;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoCardCss' ) ) {
	/**
	 * Logo card surface rules.
	 */
	class LogoCardCss {
		/**
		 * Surface hover transition — background-color interpolates solid paints;
		 * background covers gradients; border included for a full card ease.
		 */
		const HOVER_TRANSITION_SURFACE = 'all 0.4s ease-in-out';

		/**
		 * Base (Desktop) rules — full paint.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function base_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Desktop', true );
		}

		/**
		 * Tablet rules — responsive values only.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function tablet_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Tablet', false );
		}

		/**
		 * Mobile rules — responsive values only.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Mobile', false );
		}

		/**
		 * Build the rule set for one device.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @param string                $device Device key.
		 * @param bool                  $is_base Whether this is the Desktop/base pass (emits paint).
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		private static function get_rules( array $selectors, array $attributes, $device, $is_base ) {
			$card = isset( $attributes['displayElements'] ) && is_array( $attributes['displayElements'] )
				? $attributes['displayElements']
				: array();

			$rules = array();

			if ( $is_base ) {
				$card_layout = array(
					'align-items'      => 'center',
					'justify-content'  => 'flex-start',
					'background-color' => 'transparent',
					'gap'              => '12px',
					'height'           => 'auto',
					'box-sizing'       => 'border-box',
				);
				// Reset LogoSource figure chrome so the card owns its surface.
				$card_layout['border']        = '0';
				$card_layout['border-radius'] = '0';
				$rules[] = array(
					'selector' => self::sel( $selectors, 'logoCard' ),
					'styles'   => $card_layout,
				);
			}

			$card_surface = array();
			if ( $is_base ) {
				$card_surface               = self::transitionable_background(
					CssValueHelper::background_declarations( self::state( $card, 'cardBackground', 'normal' ) )
				);
				$card_surface['transition'] = self::HOVER_TRANSITION_SURFACE;
				$border                     = self::arr( $card, 'border' );
				if ( self::has_border( $border ) ) {
					$card_surface['border-style'] = (string) $border['style'];
					$card_surface['border-color'] = isset( $border['color'] ) ? (string) $border['color'] : '';
				}
			}
			if ( ! self::is_box_default( self::arr( $card, 'borderWidth' ), $device, 0 ) ) {
				$card_surface['border-width'] = self::box_responsive( self::arr( $card, 'borderWidth' ), $device );
			}
			if ( ! self::is_box_default( self::arr( $card, 'borderRadius' ), $device, 0 ) ) {
				$card_surface['border-radius'] = self::box_responsive( self::arr( $card, 'borderRadius' ), $device );
				// A rounded card has to clip what it holds. The image and the
				// hover overlay (`__figure::after`, `inset: 0`) are square boxes
				// filling the card, so without this they paint straight over the
				// rounded corners and spill outside it. Only asserted alongside a
				// radius — a square card keeps whatever overflow its layout wants.
				$card_surface['overflow'] = 'hidden';
			}
			$card_surface['padding'] = self::box_responsive( self::arr( $card, 'innerPadding' ), $device );
			if ( ! empty( $card_surface ) ) {
				$rules[] = array(
					'selector' => self::sel( $selectors, 'logoCard' ),
					'styles'   => $card_surface,
				);
			}

			if ( $is_base ) {
				$rules[] = array(
					'selector' => self::sel( $selectors, 'logoCardContent' ),
					'styles'   => array(
						'display' => 'flex',
						'width'   => '100%',
						'flex'    => '1 1 auto',
					),
				);

				$card_hover = self::transitionable_background(
					CssValueHelper::background_declarations( self::state( $card, 'cardBackground', 'hover' ) )
				);
				$border = self::arr( $card, 'border' );
				if ( self::has_border( $border ) && isset( $border['hoverColor'] ) ) {
					$card_hover['border-color'] = (string) $border['hoverColor'];
				}
				if ( ! empty( $card_hover ) ) {
					$rules[] = array(
						'selector' => self::sel( $selectors, 'logoCardHover' ),
						'styles'   => $card_hover,
					);
				}
			}

			return $rules;
		}

		/**
		 * Safe array accessor for a card field.
		 *
		 * @param array<string, mixed> $card Branch.
		 * @param string               $key Field key.
		 * @return array<string, mixed>
		 */
		private static function arr( array $card, $key ) {
			return isset( $card[ $key ] ) && is_array( $card[ $key ] ) ? $card[ $key ] : array();
		}

		/**
		 * Remap a solid `background` shorthand to `background-color` so CSS can
		 * interpolate the hover change. Gradients / images are left alone.
		 *
		 * @param array<string, string> $decls Background declaration map.
		 * @return array<string, string>
		 */
		private static function transitionable_background( array $decls ) {
			if ( empty( $decls['background'] ) || ! is_string( $decls['background'] ) ) {
				return $decls;
			}
			$bg = $decls['background'];
			if ( preg_match( '/gradient\s*\(|url\s*\(/i', $bg ) ) {
				return $decls;
			}
			unset( $decls['background'] );
			$decls['background-color'] = $bg;
			return $decls;
		}

		/**
		 * Read a per-state slice ({ normal:{...}, hover:{...} }).
		 *
		 * @param array<string, mixed> $card Branch.
		 * @param string               $key Field key.
		 * @param string               $state State.
		 * @return array<string, mixed>
		 */
		private static function state( array $card, $key, $state ) {
			$value = self::arr( $card, $key );
			return isset( $value[ $state ] ) && is_array( $value[ $state ] ) ? $value[ $state ] : array();
		}

		/**
		 * Resolve a selector from the map.
		 *
		 * @param array<string, string> $selectors Map.
		 * @param string                $key Key.
		 * @return string
		 */
		private static function sel( array $selectors, $key ) {
			return isset( $selectors[ $key ] ) ? (string) $selectors[ $key ] : '';
		}

		/**
		 * Whether a border slice has a visible style.
		 *
		 * @param array<string, mixed> $border Border slice.
		 * @return bool
		 */
		private static function has_border( array $border ) {
			$style = isset( $border['style'] ) ? (string) $border['style'] : 'none';
			return '' !== $style && 'none' !== $style;
		}

		/**
		 * Resolve a four-side responsive box into CSS shorthand.
		 *
		 * @param array<string, mixed> $responsive Responsive box.
		 * @param string               $device Device.
		 * @return string
		 */
		private static function box_responsive( array $responsive, $device ) {
			$sides  = isset( $responsive['device'][ $device ] ) && is_array( $responsive['device'][ $device ] ) ? $responsive['device'][ $device ] : array();
			$unit   = isset( $responsive['unit'][ $device ] ) ? (string) $responsive['unit'][ $device ] : 'px';
			$values = array();
			$found  = false;
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				$raw = isset( $sides[ $side ] ) ? $sides[ $side ] : '';
				if ( '' === $raw || null === $raw ) {
					$values[] = '0' . $unit;
				} else {
					$values[] = $raw . $unit;
					$found    = true;
				}
			}

			return $found ? implode( ' ', $values ) : '';
		}

		/**
		 * Whether every side of a box equals the default for the device.
		 *
		 * @param array<string, mixed> $responsive Responsive box.
		 * @param string               $device Device.
		 * @param int|float            $default_value Default applied to every side.
		 * @return bool
		 */
		private static function is_box_default( array $responsive, $device, $default_value ) {
			$sides = isset( $responsive['device'][ $device ] ) && is_array( $responsive['device'][ $device ] ) ? $responsive['device'][ $device ] : array();
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				$raw = isset( $sides[ $side ] ) ? $sides[ $side ] : '';
				if ( '' === $raw || null === $raw ) {
					continue;
				}
				if ( (float) $raw !== (float) $default_value ) {
					return false;
				}
			}

			return true;
		}
	}
}
