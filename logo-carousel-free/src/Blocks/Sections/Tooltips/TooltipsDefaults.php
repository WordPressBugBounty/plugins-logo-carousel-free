<?php
/**
 * Tooltips section defaults.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\Tooltips;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'TooltipsDefaults' ) ) {
	/**
	 * Defaults + allow-lists for the Tooltips section.
	 */
	class TooltipsDefaults {
		/**
		 * Build the full default payload (matches the schema branch shape).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_tooltips_defaults() {
			return array(
				'enabled'               => false,
				'order'                 => self::get_default_order(),
				'elements'              => self::get_enabled_defaults(),
				'visibility'            => 'hover',
				'position'              => 'auto',
				'tooltipEffect'         => 'fade',
				'maxWidth'              => self::single_responsive_default( 280 ),
				'speechArrow'           => true,
				'tooltipDistance'       => self::single_responsive_default( 10 ),
				'contentAlignment'      => 'center',
				'descriptionWordCount'  => self::single_responsive_default( 20, 'words' ),
				'descriptionEllipsis'   => '...',
				'ratingIconSize'        => self::single_responsive_default( 16 ),
				'background'            => self::get_background_defaults(),
				'titleTypography'       => self::title_typography_default(),
				'taglineTypography'     => self::tagline_typography_default(),
				'descriptionTypography' => self::description_typography_default(),
				'customTextTypography'  => self::custom_text_typography_default(),
				'titleColor'            => '#ffffff',
				'taglineColor'          => '#e5e7eb',
				'descriptionColor'      => '#d1d5db',
				'customTextColor'       => '#d1d5db',
				'border'                => self::get_border_defaults(),
				'borderWidth'           => self::responsive_box_defaults( 0 ),
				'borderRadius'          => self::responsive_box_defaults( 8 ),
				'boxShadow'             => self::get_box_shadow_defaults(),
			);
		}

		/**
		 * Box Shadow default (single state). Mirrors the local `BoxShadow`
		 * component shape: `isActive` toggle, `selectDefault` preset var (or the
		 * literal `custom`), custom `value` offsets (X / Y / Blur / Spread mapped to
		 * top / right / bottom / left), `unit` (`Outset` | `Inset`) and `color`.
		 * Off by default so old saved blocks stay shadowless.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_box_shadow_defaults() {
			return array(
				'isActive'      => false,
				'selectDefault' => 'var(--sp-lcpro-shadow-medium-4dp)',
				'color'         => '#4E4F521A',
				'unit'          => 'Outset',
				'value'         => array(
					'top'    => 0,
					'right'  => 4,
					'bottom' => 6,
					'left'   => 0,
				),
			);
		}

		/**
		 * Ordered element ids. The tooltip is text-only (no logo image).
		 *
		 * @return array<int, string>
		 */
		public static function get_default_order() {
			return array( 'title', 'tagline', 'description', 'customText', 'rating' );
		}

		/**
		 * Allowed element ids (the `order` array is sanitized against this).
		 *
		 * @return array<int, string>
		 */
		public static function get_element_ids() {
			return self::get_default_order();
		}

		/**
		 * Toggleable element visibility — only Title ON by default.
		 *
		 * `logoImage` is intentionally absent: it is locked-on, not toggleable.
		 *
		 * @return array<string, bool>
		 */
		public static function get_enabled_defaults() {
			return array(
				'title'       => true,
				'tagline'     => false,
				'description' => false,
				'customText'  => false,
				'rating'      => false,
			);
		}

		/**
		 * Allowed visibility (trigger) modes.
		 *
		 * @return array<int, string>
		 */
		public static function get_visibility_options() {
			return array( 'hover', 'click' );
		}

		/**
		 * Allowed tooltip position keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_position_options() {
			return array( 'auto', 'top', 'bottom', 'left', 'right' );
		}

		/**
		 * Allowed tooltip effect keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_effect_options() {
			return array( 'none', 'fade', 'grow', 'bounce', 'slide-in', 'slide-up', 'flip', 'blur' );
		}

		/**
		 * Allowed content alignment keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_alignment_options() {
			return array( 'left', 'center', 'right' );
		}

		/**
		 * Tooltip background (single state). Dark solid by default.
		 *
		 * `glassBase` is the user-picked base colour rendered under the glass tint
		 * + texture when the glassmorphism style is active (mirrors Display
		 * Elements' card background glass model).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_background_defaults() {
			return array(
				'style'       => 'solid',
				'solid'       => '#2f2f2f',
				'gradient'    => '',
				'glassPreset' => '',
				'glassBase'   => '#1f6b6b',
			);
		}

		/**
		 * Border (style + single colour). No per-state hover colour — the tooltip
		 * is a single-state surface.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_border_defaults() {
			return array(
				'style' => 'none',
				'color' => '#333333',
			);
		}

		/**
		 * Typography object in the shape the Typography control expects (nested
		 * `family` group + responsive size / spacing objects). Values are empty so
		 * nothing is forced until the user edits.
		 *
		 * @return array<string, mixed>
		 */
		public static function typography_default() {
			return array(
				'family'        => array(
					'family'         => '',
					'fontWeight'     => '',
					'style'          => '',
					'textTransform'  => '',
					'textDecoration' => '',
				),
				'fontSize'      => self::single_responsive_default( '' ),
				'lineHeight'    => array( 'value' => '' ),
				'letterSpacing' => self::spacing_value_default( '' ),
				'wordSpacing'   => self::spacing_value_default( '' ),
			);
		}

		/**
		 * Title typography preset. Matches the Display Elements title preset.
		 *
		 * @return array<string, mixed>
		 */
		public static function title_typography_default() {
			return self::typography_preset_default( 'SF Pro Text', '600', 18, '24px' );
		}

		/**
		 * Tagline typography preset. Matches the Display Elements tagline preset.
		 *
		 * @return array<string, mixed>
		 */
		public static function tagline_typography_default() {
			return self::typography_preset_default( 'SF Pro Text', '500', 16, '22px' );
		}

		/**
		 * Description typography preset. Matches the Display Elements description preset.
		 *
		 * @return array<string, mixed>
		 */
		public static function description_typography_default() {
			return self::typography_preset_default( 'Roboto', '400', 14, '150%' );
		}

		/**
		 * Custom Tooltip Text typography preset. Matches the Display Elements
		 * Read More preset (Roboto 400 / 14 / 150%).
		 *
		 * @return array<string, mixed>
		 */
		public static function custom_text_typography_default() {
			return self::typography_preset_default( 'Roboto', '400', 14, '150%' );
		}

		/**
		 * Typography preset in the shape the Typography control expects.
		 *
		 * @param string     $family Font family.
		 * @param string     $weight Font weight.
		 * @param int|float  $size Font size in px.
		 * @param string|int $line_height CSS line-height value.
		 * @return array<string, mixed>
		 */
		public static function typography_preset_default( $family, $weight, $size, $line_height ) {
			$default = self::typography_default();

			$default['family']['family']     = $family;
			$default['family']['fontWeight'] = $weight;
			$default['fontSize']             = self::single_responsive_default( $size );
			$default['lineHeight']           = array( 'value' => (string) $line_height );

			return $default;
		}

		/**
		 * Single responsive value default (Desktop/Tablet/Mobile share one value + unit).
		 *
		 * @param int|float|string $value Value applied to every device.
		 * @param string           $unit Shared unit.
		 * @return array<string, mixed>
		 */
		public static function single_responsive_default( $value, $unit = 'px' ) {
			return array(
				'device' => array(
					'Desktop' => $value,
					'Tablet'  => $value,
					'Mobile'  => $value,
				),
				'unit'   => array(
					'Desktop' => $unit,
					'Tablet'  => $unit,
					'Mobile'  => $unit,
				),
			);
		}

		/**
		 * Non-responsive spacing default (single value shared across all devices).
		 *
		 * @param int|float|string $value Value.
		 * @param string           $unit Unit.
		 * @return array<string, mixed>
		 */
		public static function spacing_value_default( $value = '', $unit = 'px' ) {
			return array(
				'value' => $value,
				'unit'  => $unit,
			);
		}

		/**
		 * Responsive four-side box default (mirrors AttributeFactory::responsive_spacing).
		 *
		 * @param int|string $desktop Desktop value applied to all four sides.
		 * @return array<string, mixed>
		 */
		public static function responsive_box_defaults( $desktop ) {
			return array(
				'device'    => array(
					'Desktop' => array(
						'top'    => $desktop,
						'right'  => $desktop,
						'bottom' => $desktop,
						'left'   => $desktop,
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
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
				),
				'allChange' => true,
			);
		}
	}
}
