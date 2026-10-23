<?php
/**
 * Scheduling section defaults.
 *
 * Controls frontend-only block visibility between optional start/end
 * date-time bounds. The editor preview is never gated by this section.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\Scheduling;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SchedulingDefaults' ) ) {
	/**
	 * Defaults for the Scheduling section.
	 */
	class SchedulingDefaults {
		/**
		 * Build the full default payload.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_scheduling_defaults() {
			return array(
				'enabled'   => false,
				'startDate' => '',
				'endDate'   => '',
			);
		}
	}
}
