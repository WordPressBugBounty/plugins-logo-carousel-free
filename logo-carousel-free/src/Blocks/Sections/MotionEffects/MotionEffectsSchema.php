<?php
/**
 * Motion Effects section schema.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\MotionEffects;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MotionEffectsSchema' ) ) {
	/**
	 * Motion Effects attribute branch builder.
	 */
	class MotionEffectsSchema {
		/** Section id. */
		public static function id() {
			return 'motionEffects';
		}

		/** Raw default payload. */
		public static function defaults() {
			return MotionEffectsDefaults::get_motion_effects_defaults();
		}

		/** Return the `motionEffects` attribute definition. */
		public static function definition() {
			return array(
				'motionEffects' => AttributeFactory::object(
					self::defaults(),
					array(
						'entrance' => AttributeFactory::object(
							MotionEffectsDefaults::get_entrance_defaults(),
							array(
								'effect' => AttributeFactory::string(
									'none',
									MotionEffectsDefaults::get_entrance_effect_options()
								),
								'speed'  => AttributeFactory::integer( 1000 ),
								'delay'  => AttributeFactory::integer( 0 ),
							)
						),
						'scroll'   => AttributeFactory::object(
							MotionEffectsDefaults::get_scroll_defaults(),
							array(
								'enabled'       => AttributeFactory::boolean( false ),
								'direction'     => AttributeFactory::string(
									'up',
									MotionEffectsDefaults::get_scroll_direction_options()
								),
								'speed'         => AttributeFactory::integer( 4 ),
								'viewportStart' => AttributeFactory::integer( 0 ),
								'viewportEnd'   => AttributeFactory::integer( 100 ),
							)
						),
						'sticky'   => AttributeFactory::object(
							MotionEffectsDefaults::get_sticky_defaults(),
							array(
								'position' => AttributeFactory::string(
									'none',
									MotionEffectsDefaults::get_sticky_position_options()
								),
								'offset'   => AttributeFactory::integer( 0 ),
							)
						),
					)
				),
			);
		}
	}
}
