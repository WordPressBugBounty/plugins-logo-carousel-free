<?php
/**
 * Performance Guardrails Configuration
 *
 * Server-side configuration for performance limits and optimization.
 * Mirrors the client-side PERFORMANCE_LIMITS constants.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PerformanceConfig' ) ) {
	/**
	 * Performance configuration constants and utilities.
	 */
	class PerformanceConfig {

		/**
		 * Performance limit constants.
		 */
		const WARNING_THRESHOLD = 100;
		const SOFT_LIMIT        = 250;
		const HARD_LIMIT        = 500;

		/**
		 * Get performance limit settings.
		 *
		 * @return array<string, int> Performance limits.
		 */
		public static function get_limits() {
			$defaults = array(
				'warning_threshold' => self::WARNING_THRESHOLD,
				'soft_limit'        => self::SOFT_LIMIT,
				'hard_limit'        => self::HARD_LIMIT,
			);

			/**
			 * Filter performance limits for enterprise customization.
			 *
			 * @param array $limits Default performance limits.
			 */
			return apply_filters( 'sp_lcpro_performance_limits', $defaults );
		}

		/**
		 * Check if item count exceeds warning threshold.
		 *
		 * @param int $count Current item count.
		 * @return array<string, mixed> Warning status.
		 */
		public static function check_warning( $count ) {
			$limits = self::get_limits();

			$warning = array(
				'has_warning'     => false,
				'level'           => null,
				'type'            => 'none',
				'message'         => '',
				'can_add_more'    => true,
				'remaining_slots' => $limits['hard_limit'] - $count,
			);

			if ( $count >= $limits['hard_limit'] ) {
				$warning['has_warning']     = true;
				$warning['level']           = 'error';
				$warning['type']            = 'hard_limit';
				$warning['can_add_more']    = false;
				$warning['remaining_slots'] = 0;
				$warning['message']         = sprintf(
					/* translators: %d: maximum number of items */
					__( 'Maximum limit of %d items reached. Remove some items before adding more.', 'logo-carousel-free' ),
					$limits['hard_limit']
				);
			} elseif ( $count >= $limits['soft_limit'] ) {
				$warning['has_warning'] = true;
				$warning['level']       = 'warning';
				$warning['type']        = 'soft_limit';
				$warning['message']     = sprintf(
					/* translators: %d: current item count */
					__( 'You have %d items. Large collections can slow editing and rendering.', 'logo-carousel-free' ),
					$count
				);
			} elseif ( $count >= $limits['warning_threshold'] ) {
				$warning['has_warning'] = true;
				$warning['level']       = 'info';
				$warning['type']        = 'warning';
				$warning['message']     = sprintf(
					/* translators: %d: current item count */
					__( 'You have %d items. The editor now shows them in smaller batches to stay responsive.', 'logo-carousel-free' ),
					$count
				);
			}

			return $warning;
		}

		/**
		 * Validate if adding items would exceed limits.
		 *
		 * @param int $current_count Current item count.
		 * @param int $items_to_add  Number of items to add.
		 * @return array<string, mixed> Validation result.
		 */
		public static function validate_addition( $current_count, $items_to_add ) {
			$limits          = self::get_limits();
			$projected_count = $current_count + $items_to_add;

			if ( $projected_count > $limits['hard_limit'] ) {
				$slots_remaining = $limits['hard_limit'] - $current_count;

				return array(
					'can_add'       => false,
					'allowed_items' => max( 0, $slots_remaining ),
					'message'       => sprintf(
						/* translators: 1: slots remaining, 2: requested items */
						__( 'Only %1$d more items can be added. %2$d selected.', 'logo-carousel-free' ),
						max( 0, $slots_remaining ),
						$items_to_add
					),
				);
			}

			return array(
				'can_add'       => true,
				'allowed_items' => $items_to_add,
				'message'       => '',
			);
		}
	}
}
