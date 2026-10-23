<?php
/**
 * Scheduling section schema.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\Scheduling;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SchedulingSchema' ) ) {
	/**
	 * Scheduling attribute branch builder.
	 */
	class SchedulingSchema {
		/** Section id. */
		public static function id() {
			return 'scheduling';
		}

		/** Raw default payload. */
		public static function defaults() {
			return SchedulingDefaults::get_scheduling_defaults();
		}

		/** Return the `scheduling` attribute definition. */
		public static function definition() {
			return array(
				'scheduling' => AttributeFactory::object(
					self::defaults(),
					array(
						'enabled'   => AttributeFactory::boolean( false ),
						'startDate' => AttributeFactory::string( '' ),
						'endDate'   => AttributeFactory::string( '' ),
					)
				),
			);
		}
	}
}
