<?php
/**
 * Contract for section config normalizers.
 *
 * A section config takes the raw block attributes and returns a render-ready,
 * normalized payload for one feature area (navigation, pagination, layout,
 * logo images). Each section owns its own branch of the attribute schema and
 * never fetches data or builds HTML.
 *
 * Implementers are composed (HAS-A) by `LogoCarouselConfigSectionsBuilder`, not
 * extended — this contract is the family's shared section seam, so a future
 * block (grid) can reuse the same normalizers by type, not by name.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections;

defined( 'ABSPATH' ) || exit;

if ( ! interface_exists( 'SectionConfigInterface' ) ) {
	/**
	 * Normalize one section's attribute branch into a render-ready payload.
	 */
	interface SectionConfigInterface {
		/**
		 * Build the normalized payload for this section.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @return array<string, mixed>
		 */
		public function normalize( array $attributes );
	}
}
