<?php
/**
 * Motion Effects section defaults.
 *
 * Owns the default payload and enum allow-lists for the `motionEffects` branch:
 * a block-level entrance animation (an animate.style effect played once when the
 * block first scrolls into view), plus scroll parallax and sticky positioning.
 *
 * The entrance effect registry is the single source of truth for effect keys.
 * The editor mirror (`animationRegistry.js`) and the CSS keyframe emitter both
 * agree with `get_entrance_effect_keys()`; group titles are display-only and are
 * never selectable values.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\MotionEffects;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MotionEffectsDefaults' ) ) {
	/**
	 * Defaults for the Motion Effects section.
	 */
	class MotionEffectsDefaults {
		/**
		 * Build the full default payload for the branch.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_motion_effects_defaults() {
			return array(
				'entrance' => self::get_entrance_defaults(),
				'scroll'   => self::get_scroll_defaults(),
				'sticky'   => self::get_sticky_defaults(),
			);
		}

		/**
		 * Entrance sub-branch defaults. `effect` of 'none' is the off state.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_entrance_defaults() {
			return array(
				'effect' => 'none',
				'speed'  => 1000,
				'delay'  => 0,
			);
		}

		/**
		 * Scroll (parallax) sub-branch defaults. Frontend runtime only.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_scroll_defaults() {
			return array(
				'enabled'       => false,
				'direction'     => 'up',
				'speed'         => 4,
				'viewportStart' => 0,
				'viewportEnd'   => 100,
			);
		}

		/**
		 * Sticky sub-branch defaults. `position` of 'none' is the off state.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_sticky_defaults() {
			return array(
				'position' => 'none',
				'offset'   => 0,
			);
		}

		/**
		 * Scroll direction allow-list.
		 *
		 * @return array<int, string>
		 */
		public static function get_scroll_direction_options() {
			return array( 'up', 'down' );
		}

		/**
		 * Sticky position allow-list.
		 *
		 * @return array<int, string>
		 */
		public static function get_sticky_position_options() {
			return array( 'none', 'top', 'bottom' );
		}

		/**
		 * Entrance effect allow-list including the 'none' off state.
		 *
		 * Used as the schema enum for `entrance.effect`.
		 *
		 * @return array<int, string>
		 */
		public static function get_entrance_effect_options() {
			return array_merge( array( 'none' ), self::get_entrance_effect_keys() );
		}

		/**
		 * Flat list of every selectable entrance effect key (no 'none').
		 *
		 * This is the canonical allow-list the CSS keyframe emitter validates
		 * against and the parity anchor the schema test compares to the union of
		 * the grouped registry.
		 *
		 * @return array<int, string>
		 */
		public static function get_entrance_effect_keys() {
			$keys = array();

			foreach ( self::get_entrance_effects() as $group ) {
				foreach ( $group['effects'] as $effect ) {
					$keys[] = $effect['key'];
				}
			}

			return $keys;
		}

		/**
		 * Grouped entrance effect registry (from the Figma spec).
		 *
		 * Each group `label` is a non-selectable heading; each effect carries the
		 * display `label` and the stored `key`. Keys follow animate.style naming;
		 * `*Big` slide variants and the two-way `flip` are self-authored keyframes.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_entrance_effects() {
			return array(
				array(
					'label'   => 'Fading',
					'effects' => array(
						array(
							'label' => 'Fade In',
							'key'   => 'fadeIn',
						),
						array(
							'label' => 'Fade In Down',
							'key'   => 'fadeInDown',
						),
						array(
							'label' => 'Fade In Left',
							'key'   => 'fadeInLeft',
						),
						array(
							'label' => 'Fade In Right',
							'key'   => 'fadeInRight',
						),
						array(
							'label' => 'Fade In Up',
							'key'   => 'fadeInUp',
						),
					),
				),
				array(
					'label'   => 'Zooming',
					'effects' => array(
						array(
							'label' => 'Zoom In',
							'key'   => 'zoomIn',
						),
						array(
							'label' => 'Zoom In Down',
							'key'   => 'zoomInDown',
						),
						array(
							'label' => 'Zoom In Left',
							'key'   => 'zoomInLeft',
						),
						array(
							'label' => 'Zoom In Right',
							'key'   => 'zoomInRight',
						),
						array(
							'label' => 'Zoom In Up',
							'key'   => 'zoomInUp',
						),
					),
				),
				array(
					'label'   => 'Bouncing',
					'effects' => array(
						array(
							'label' => 'Bounce In',
							'key'   => 'bounceIn',
						),
						array(
							'label' => 'Bounce In Down',
							'key'   => 'bounceInDown',
						),
						array(
							'label' => 'Bounce In Left',
							'key'   => 'bounceInLeft',
						),
						array(
							'label' => 'Bounce In Up',
							'key'   => 'bounceInUp',
						),
					),
				),
				array(
					'label'   => 'Sliding',
					'effects' => array(
						array(
							'label' => 'Slide In Left',
							'key'   => 'slideInLeft',
						),
						array(
							'label' => 'Slide In Left Big',
							'key'   => 'slideInLeftBig',
						),
						array(
							'label' => 'Slide In Right',
							'key'   => 'slideInRight',
						),
						array(
							'label' => 'Slide In Right Big',
							'key'   => 'slideInRightBig',
						),
						array(
							'label' => 'Slide In Down',
							'key'   => 'slideInDown',
						),
						array(
							'label' => 'Slide In Down Big',
							'key'   => 'slideInDownBig',
						),
						array(
							'label' => 'Slide In Up',
							'key'   => 'slideInUp',
						),
						array(
							'label' => 'Slide In Up Big',
							'key'   => 'slideInUpBig',
						),
					),
				),
				array(
					'label'   => 'Rotating',
					'effects' => array(
						array(
							'label' => 'Rotate In',
							'key'   => 'rotateIn',
						),
						array(
							'label' => 'Rotate In Down Left',
							'key'   => 'rotateInDownLeft',
						),
						array(
							'label' => 'Rotate In Down Right',
							'key'   => 'rotateInDownRight',
						),
						array(
							'label' => 'Rotate In Up Left',
							'key'   => 'rotateInUpLeft',
						),
						array(
							'label' => 'Rotate In Up Right',
							'key'   => 'rotateInUpRight',
						),
					),
				),
				array(
					'label'   => 'Flipping',
					'effects' => array(
						array(
							'label' => 'Flip',
							'key'   => 'flip',
						),
						array(
							'label' => 'Flip X',
							'key'   => 'flipInX',
						),
						array(
							'label' => 'Flip Y',
							'key'   => 'flipInY',
						),
					),
				),
				array(
					'label'   => 'Rolling',
					'effects' => array(
						array(
							'label' => 'Roll In',
							'key'   => 'rollIn',
						),
					),
				),
				array(
					'label'   => 'Other',
					'effects' => array(
						array(
							'label' => 'Flash',
							'key'   => 'flash',
						),
						array(
							'label' => 'Pulse',
							'key'   => 'pulse',
						),
						array(
							'label' => 'Rubber Band',
							'key'   => 'rubberBand',
						),
						array(
							'label' => 'Shake',
							'key'   => 'shake',
						),
						array(
							'label' => 'Head Shake',
							'key'   => 'headShake',
						),
						array(
							'label' => 'Swing',
							'key'   => 'swing',
						),
						array(
							'label' => 'Tada',
							'key'   => 'tada',
						),
						array(
							'label' => 'Wobble',
							'key'   => 'wobble',
						),
						array(
							'label' => 'Jello',
							'key'   => 'jello',
						),
						array(
							'label' => 'Light Speed In',
							'key'   => 'lightSpeedIn',
						),
					),
				),
			);
		}
	}
}
