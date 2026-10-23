<?php
/**
 * Display Elements section defaults.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\DisplayElements;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DisplayElementsDefaults' ) ) {
	/**
	 * Defaults + allow-lists for the Display Elements section.
	 */
	class DisplayElementsDefaults {
		/**
		 * Build the full default payload (matches the schema branch shape).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_display_elements_defaults() {
			return array(
				'order'                      => self::get_default_order(),
				'enabled'                    => self::get_enabled_defaults(),
				'titleSource'                => 'title',
				'descriptionSource'          => 'description',
				'descriptionWordLimit'       => 'full',
				'descriptionWordCount'       => self::single_responsive_default( 10, 'words' ),
				'descriptionEllipsis'        => '...',
				'ratingIconSize'             => self::single_responsive_default( 16 ),
				'readMoreType'               => 'button',
				'readMoreLabel'              => 'Read More',
				'clickAction'                => 'url',
				'gapBetweenElements'         => self::single_responsive_default( 10 ),
				'alignment'                  => 'left',
				'titleTypography'            => self::title_typography_default(),
				'taglineTypography'          => self::tagline_typography_default(),
				'descriptionTypography'      => self::description_typography_default(),
				'titleColor'                 => self::color_pair_default( '#000000', '#000000' ),
				'taglineColor'               => self::color_pair_default( '#000000', '#000000' ),
				'ratingIconColor'            => self::color_pair_default( '#f0b849', '#f0b849' ),
				'descriptionColor'           => self::color_pair_default( '#757575', '#757575' ),
				'cardBackground'             => self::get_background_defaults(),
				'border'                     => self::get_card_border_defaults(),
				'borderWidth'                => self::responsive_box_defaults( 1 ),
				'borderRadius'               => self::responsive_box_defaults( 4 ),
				'innerPadding'               => self::responsive_box_defaults( 8 ),
				'readMoreButtonTypography'   => self::read_more_typography_default(),
				'readMoreButtonColor'        => self::color_pair_default( '#ffffff', '#ffffff' ),
				'readMoreButtonBackground'   => self::color_pair_default( '#2F2F2F', '#2F2F2F' ),
				'readMoreButtonBorder'       => self::get_border_defaults(),
				'readMoreButtonBorderWidth'  => self::responsive_box_defaults( 0 ),
				'readMoreButtonBorderRadius' => self::responsive_box_defaults( 4 ),
				'readMoreButtonPadding'      => self::responsive_box_values_default( 3, 7, 3, 7 ),
				'readMoreButtonMargin'       => self::responsive_box_defaults( 0 ),
				'readMoreInlineTypography'   => self::read_more_typography_default(),
				'readMoreInlineColor'        => self::color_pair_default( 'hsla(152, 78%, 71%, 1)', 'hsla(152, 78%, 71%, 1)' ),
				'readMoreInlinePadding'      => self::responsive_box_defaults( 0 ),
			);
		}

		/**
		 * Ordered element ids. `logoImage` is locked-on and always first by default.
		 *
		 * @return array<int, string>
		 */
		public static function get_default_order() {
			return array( 'logoImage', 'title', 'tagline', 'description', 'rating', 'readMore' );
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
		 * Toggleable element visibility — all OFF by default (logos-only).
		 *
		 * `logoImage` is intentionally absent: it is locked-on, not toggleable.
		 *
		 * @return array<string, bool>
		 */
		public static function get_enabled_defaults() {
			return array(
				'title'       => false,
				'tagline'     => false,
				'description' => false,
				'rating'      => false,
				'readMore'    => false,
			);
		}

		/**
		 * Allowed title source keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_title_source_options() {
			return array( 'title', 'caption' );
		}

		/**
		 * Allowed description source keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_description_source_options() {
			return array( 'description', 'caption' );
		}

		/**
		 * Allowed description word-limit modes.
		 *
		 * @return array<int, string>
		 */
		public static function get_word_limit_options() {
			return array( 'full', 'limit' );
		}

		/**
		 * Allowed Read More button types.
		 *
		 * @return array<int, string>
		 */
		public static function get_read_more_type_options() {
			return array( 'button', 'inline' );
		}

		/**
		 * Allowed click-action keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_click_action_options() {
			return array( 'none', 'url', 'popup' );
		}

		/**
		 * Allowed card alignment keys.
		 *
		 * @return array<int, string>
		 */
		public static function get_alignment_options() {
			return array( 'left', 'center', 'right' );
		}

		/**
		 * Card background per state (Normal/Hover). Transparent by default.
		 *
		 * `glassBase` is the user-picked base colour rendered UNDER the glass tint
		 * + texture when the glassmorphism style is active, so the card reads as a
		 * coloured glass panel (like the preset swatches) instead of depending on a
		 * Section Background behind it. Default teal matches the swatch preview.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_background_defaults() {
			return array(
				'normal' => array(
					'style'       => 'solid',
					'solid'       => '',
					'gradient'    => '',
					'glassPreset' => '',
					'glassBase'   => '#1f6b6b',
				),
				'hover'  => array(
					'style'       => 'solid',
					'solid'       => '',
					'gradient'    => '',
					'glassPreset' => '',
					'glassBase'   => '#1f6b6b',
				),
			);
		}

		/**
		 * Default card border.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_card_border_defaults() {
			return array(
				'style'      => 'solid',
				'color'      => '#dddddd',
				'hoverColor' => '#dddddd',
			);
		}

		/**
		 * Border (style + per-state color), mirrors the Navigation border shape.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_border_defaults() {
			return array(
				'style'      => 'none',
				'color'      => '#cccccc',
				'hoverColor' => '#cccccc',
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
		 * Title / caption typography preset.
		 *
		 * @return array<string, mixed>
		 */
		public static function title_typography_default() {
			return self::typography_preset_default( 'SF Pro Text', '600', 18, '24px' );
		}

		/**
		 * Tagline typography preset.
		 *
		 * @return array<string, mixed>
		 */
		public static function tagline_typography_default() {
			return self::typography_preset_default( 'SF Pro Text', '500', 16, '22px' );
		}

		/**
		 * Description typography preset.
		 *
		 * @return array<string, mixed>
		 */
		public static function description_typography_default() {
			return self::typography_preset_default( 'Roboto', '400', 14, '150%' );
		}

		/**
		 * Read More typography preset.
		 *
		 * @return array<string, mixed>
		 */
		public static function read_more_typography_default() {
			return self::typography_preset_default( 'Roboto', '400', 14, '150%' );
		}

		/**
		 * Typography preset in the shape the Typography control expects.
		 *
		 * @param string       $family Font family.
		 * @param string       $weight Font weight.
		 * @param int|float    $size Font size in px.
		 * @param string|int   $line_height CSS line-height value.
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
		 * Normal/Hover color pair default.
		 *
		 * @param string $normal Normal color.
		 * @param string $hover Hover color.
		 * @return array<string, string>
		 */
		public static function color_pair_default( $normal = '', $hover = '' ) {
			return array(
				'normal' => $normal,
				'hover'  => $hover,
			);
		}

		/**
		 * Single responsive value default (Desktop/Tablet/Mobile share one number + px).
		 *
		 * @param int|float $value Value applied to every device.
		 * @param string    $unit Shared unit.
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

		/**
		 * Responsive four-side box default with explicit Desktop side values.
		 *
		 * @param int|string $top Top Desktop value.
		 * @param int|string $right Right Desktop value.
		 * @param int|string $bottom Bottom Desktop value.
		 * @param int|string $left Left Desktop value.
		 * @return array<string, mixed>
		 */
		public static function responsive_box_values_default( $top, $right, $bottom, $left ) {
			return array(
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
					'Desktop' => 'px',
					'Tablet'  => 'px',
					'Mobile'  => 'px',
				),
				'allChange' => true,
			);
		}
	}
}
