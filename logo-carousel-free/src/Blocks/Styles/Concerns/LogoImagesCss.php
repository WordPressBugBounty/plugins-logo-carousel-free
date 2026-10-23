<?php

/**
 * Logo images CSS concern for the block-family server-side composer.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles\Concerns;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase\LogoImagesSettingsConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\LogoImagesDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssUtils;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssValueHelper;

defined('ABSPATH') || exit;

if (! class_exists('LogoImagesCss')) {
	/**
	 * Shared logo images rules mirrored from the editor CSS concern.
	 */
	class LogoImagesCss
	{
		/**
		 * Base logo image rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function base_rules(array $selectors, array $attributes = array())
		{
			return array_merge(
				self::get_aspect_ratio_rules($selectors, $attributes, 'Desktop'),
				self::get_style_preview_rules($selectors, $attributes)
			);
		}

		/**
		 * Tablet logo image rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function tablet_rules(array $selectors, array $attributes = array())
		{
			return self::get_custom_size_rules($selectors, $attributes, 'Tablet');
		}

		/**
		 * Mobile logo image rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		public static function mobile_rules(array $selectors, array $attributes = array())
		{
			return self::get_custom_size_rules($selectors, $attributes, 'Mobile');
		}

		/**
		 * Return the normalized logo images style branch.
		 *
		 * @param array<string, mixed> $attributes Block attributes.
		 * @return array<string, mixed>
		 */
		private static function get_logo_images_style(array $attributes)
		{
			if (isset($attributes['logoImagesStyle']) && is_array($attributes['logoImagesStyle'])) {
				return $attributes['logoImagesStyle'];
			}

			if (isset($attributes['logoImageStyle']) && is_array($attributes['logoImageStyle'])) {
				return $attributes['logoImageStyle'];
			}

			return array();
		}

		/**
		 * Convert a grouped spacing branch into a CSS shorthand for Desktop.
		 *
		 * @param array<string, mixed> $spacing Spacing branch.
		 * @return string
		 */
		private static function get_spacing_value(array $spacing)
		{
			$value = CssValueHelper::responsive_spacing($spacing, 'Desktop');

			return is_string($value) ? $value : '';
		}

		/**
		 * Resolve margin sides so horizontal inset can shrink width and stay inside the item.
		 *
		 * @param array<string, mixed> $spacing Spacing branch.
		 * @return array{shorthand:string,left:string,right:string,has_horizontal:bool}
		 */
		private static function get_spacing_sides(array $spacing)
		{
			$shorthand = self::get_spacing_value($spacing);
			if ('' === $shorthand) {
				return array(
					'shorthand'      => '',
					'left'           => '0',
					'right'          => '0',
					'has_horizontal' => false,
				);
			}

			$parts = preg_split('/\s+/', trim($shorthand));
			$left  = isset($parts[3]) ? $parts[3] : '0';
			$right = isset($parts[1]) ? $parts[1] : '0';

			return array(
				'shorthand'      => $shorthand,
				'left'           => $left,
				'right'          => $right,
				'has_horizontal' => (! in_array($left, array('0', '0px', '0%'), true)) || (! in_array($right, array('0', '0px', '0%'), true)),
			);
		}

		/**
		 * Keep a full-width logo box inside its item when left/right margin is set.
		 *
		 * @param array{left:string,right:string,has_horizontal:bool} $sides Margin sides.
		 * @param string                                               $mode  force-width|max-only.
		 * @return array<string, string>
		 */
		private static function get_horizontal_inset_width_styles(array $sides, $mode = 'force-width')
		{
			if (empty($sides['has_horizontal'])) {
				return array();
			}

			$inset_width = 'calc(100% - ' . $sides['left'] . ' - ' . $sides['right'] . ')';

			if ('max-only' === $mode) {
				return array(
					'max-width'  => $inset_width,
					'box-sizing' => 'border-box',
				);
			}

			return array(
				'width'      => $inset_width,
				'max-width'  => $inset_width,
				'box-sizing' => 'border-box',
			);
		}

		/**
		 * Build a CSS filter string from the preview branch.
		 *
		 * @param array<string, mixed> $filter Filter branch.
		 * @return string
		 */
		private static function get_filter_value(array $filter)
		{
			$parts      = array();
			$blur       = isset($filter['blur']) && is_numeric($filter['blur']) ? (int) $filter['blur'] : 0;
			$brightness = isset($filter['brightness']) && is_numeric($filter['brightness']) ? (int) $filter['brightness'] : 0;
			$contrast   = isset($filter['contrast']) && is_numeric($filter['contrast']) ? (int) $filter['contrast'] : 0;
			$saturation = isset($filter['saturation']) && is_numeric($filter['saturation']) ? (int) $filter['saturation'] : 0;
			$hue        = isset($filter['hue']) && is_numeric($filter['hue']) ? (int) $filter['hue'] : 0;

			if ($blur) {
				$parts[] = 'blur(' . $blur . 'px)';
			}

			if ($brightness) {
				$parts[] = 'brightness(' . (100 + $brightness) . '%)';
			}

			if ($contrast) {
				$parts[] = 'contrast(' . (100 + $contrast) . '%)';
			}

			if ($saturation) {
				$parts[] = 'saturate(' . (100 + $saturation) . '%)';
			}

			if ($hue) {
				$parts[] = 'hue-rotate(' . $hue . 'deg)';
			}

			return empty($parts) ? 'none' : implode(' ', $parts);
		}

		/**
		 * Join non-empty CSS filter fragments.
		 *
		 * @param string ...$filters Filter fragments.
		 * @return string
		 */
		private static function merge_filters()
		{
			$filters = func_get_args();
			$filters = array_values(
				array_filter(
					$filters,
					static function ($filter) {
						return is_string($filter) && '' !== $filter && 'none' !== $filter;
					}
				)
			);

			return implode(' ', $filters);
		}

		/**
		 * Clamp a channel to the byte range.
		 *
		 * @param float $value Channel value.
		 * @return float
		 */
		private static function clamp_byte($value)
		{
			return max(0, min(255, (float) $value));
		}

		/**
		 * Parse a CSS color into RGBA channels.
		 *
		 * Supports the values the block UI emits (`#hex`, `rgb()`, `rgba()`).
		 *
		 * @param string $value CSS color.
		 * @return array<string, float>|null
		 */
		private static function parse_custom_color($value)
		{
			$value = trim((string) $value);
			if ('' === $value) {
				return null;
			}

			if ('#' === substr($value, 0, 1)) {
				return self::parse_hex_custom_color($value);
			}

			if (preg_match('/^rgba?\(\s*([+-]?(?:\d+|\d*\.\d+))\s*,\s*([+-]?(?:\d+|\d*\.\d+))\s*,\s*([+-]?(?:\d+|\d*\.\d+))(?:\s*,\s*([+-]?(?:\d+|\d*\.\d+))\s*)?\)$/i', $value, $matches)) {
				return array(
					'r' => self::clamp_byte($matches[1]),
					'g' => self::clamp_byte($matches[2]),
					'b' => self::clamp_byte($matches[3]),
					'a' => isset($matches[4]) ? max(0, min(1, (float) $matches[4])) : 1.0,
				);
			}

			return null;
		}

		/**
		 * Parse a `#rgb`, `#rgba`, `#rrggbb`, or `#rrggbbaa` color.
		 *
		 * @param string $value Hex color.
		 * @return array<string, float>|null
		 */
		private static function parse_hex_custom_color($value)
		{
			$hex = ltrim(trim($value), '#');
			if (in_array(strlen($hex), array(3, 4), true)) {
				$expanded = '';
				foreach (str_split($hex) as $char) {
					$expanded .= $char . $char;
				}
				$hex = $expanded;
			}

			if (! in_array(strlen($hex), array(6, 8), true) || ! ctype_xdigit($hex)) {
				return null;
			}

			return array(
				'r' => (float) hexdec(substr($hex, 0, 2)),
				'g' => (float) hexdec(substr($hex, 2, 2)),
				'b' => (float) hexdec(substr($hex, 4, 2)),
				'a' => 8 === strlen($hex) ? hexdec(substr($hex, 6, 2)) / 255 : 1.0,
			);
		}

		/**
		 * Convert a custom color value into the legacy CSS filter chain.
		 *
		 * @param string $value Sanitized CSS color.
		 * @return string
		 */
		private static function get_custom_color_filter($value)
		{
			$rgba = self::parse_custom_color($value);
			if (! is_array($rgba)) {
				return '';
			}

			$solver = new LogoImagesCssColorSolver(
				$rgba['r'],
				$rgba['g'],
				$rgba['b'],
				$rgba['a']
			);

			return $solver->solve();
		}

		/**
		 * Selector for images that support custom recoloring.
		 *
		 * Limit Custom Color to PNG/SVG logos; other raster formats do not need it.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @return string
		 */
		private static function tintable_image_selector(array $selectors)
		{
			$base = $selectors['wrapper'] . ' .sp-lcpro-logo-carousel .sp-lcpro-logo-source__image';
			return $base . '.sp-lcpro-logo-source__image--tintable';
		}

		/**
		 * Hover selector for images that support custom recoloring.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @return string
		 */
		private static function hover_tintable_image_selector(array $selectors)
		{
			$base = self::hover_host($selectors) . ':hover .sp-lcpro-logo-source__image';
			return $base . '.sp-lcpro-logo-source__image--tintable';
		}

		/**
		 * The element whose `:hover` drives the logo image hover state.
		 *
		 * `Selectors` resolves this per block — the figure everywhere, the row in
		 * Table, where the figure is `display: contents` and cannot be hovered.
		 * Falls back to the figure so a filtered or older selector map still
		 * produces the pre-Table behavior instead of an unscoped selector.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @return string
		 */
		private static function hover_host(array $selectors)
		{
			if (isset($selectors['logoImageHoverHost']) && '' !== $selectors['logoImageHoverHost']) {
				return (string) $selectors['logoImageHoverHost'];
			}

			return $selectors['wrapper'] . ' .sp-lcpro-logo-carousel .sp-lcpro-logo-source__figure';
		}

		/**
		 * Hover animation presets.
		 *
		 * Two kinds, because two kinds of effect are being asked for:
		 *
		 * - `transform` presets settle on a new resting state and ease there, so
		 *   they read as a state change and hold while the pointer stays put.
		 * - `animation` presets are motion — a bounce that does not move is just
		 *   a nudge — so they run shared keyframes from
		 *   `assets/css/hover-animations.css`. Those with `infinite` loop while
		 *   hovered; the rest play once and settle.
		 *
		 * Keeping every preset in one table is what stops them drifting into
		 * near-duplicates of each other, which is how `float` ended up identical
		 * to `slide-up` and `wobble` to `skew`.
		 *
		 * @return array<string, array<string, string>>
		 */
		private static function hover_animation_presets()
		{
			return array(
				// Eased state changes.
				'scale-up'    => array( 'transform' => 'scale(1.06)' ),
				'fade-scale'  => array(
					'transform' => 'scale(1.06)',
					'opacity'   => '0.85',
				),
				'rotate'      => array( 'transform' => 'rotate(4deg)' ),
				'slide-up'    => array( 'transform' => 'translateY(-6px)' ),
				'skew'        => array( 'transform' => 'skewX(-6deg)' ),

				// Motion, played from shared keyframes.
				'bounce'      => array( 'animation' => 'sp-lcpro-hover-bounce 800ms ease' ),
				'shake'       => array( 'animation' => 'sp-lcpro-hover-shake 600ms ease' ),
				'jelly'       => array( 'animation' => 'sp-lcpro-hover-jelly 700ms ease' ),
				'swing'       => array( 'animation' => 'sp-lcpro-hover-swing 900ms ease' ),
				'wobble'      => array( 'animation' => 'sp-lcpro-hover-wobble 900ms ease' ),
				'spin-360'    => array( 'animation' => 'sp-lcpro-hover-spin 900ms linear' ),
				'flip-x'      => array( 'animation' => 'sp-lcpro-hover-flip-x 700ms ease' ),
				'flip-y'      => array( 'animation' => 'sp-lcpro-hover-flip-y 700ms ease' ),

				// Motion that keeps going while the pointer rests.
				'float'       => array( 'animation' => 'sp-lcpro-hover-float 1800ms ease-in-out infinite' ),
				'pulse'       => array( 'animation' => 'sp-lcpro-hover-pulse 1400ms ease-in-out infinite' ),
				'heartbeat'   => array( 'animation' => 'sp-lcpro-hover-heartbeat 1300ms ease-in-out infinite' ),
			);
		}

		/**
		 * Build hover effect rules from the animation preset.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param string                $animation Hover animation.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		private static function get_hover_effect_rules(array $selectors, $animation)
		{
			$animation = (string) $animation;

			if ('none' === $animation) {
				return array();
			}

			$host           = self::hover_host($selectors);
			$image_selector = $host . ':hover .sp-lcpro-logo-source__image';

			// The ring is painted on the hover host itself, not the image, so it
			// is the one preset that does not touch the image. Nothing else here
			// sets box-shadow: a blanket reset would wipe a card shadow the user
			// configured.
			if ('border-ring' === $animation) {
				return array(
					array(
						'selector' => $host,
						'styles'   => array(
							'transition' => 'box-shadow 220ms ease',
						),
					),
					array(
						'selector' => $host . ':hover',
						'styles'   => array(
							'box-shadow' => 'inset 0 0 0 2px var(--sp-lcpro-carousel-primary-2-400)',
						),
					),
				);
			}

			$presets = self::hover_animation_presets();

			if (! isset($presets[$animation])) {
				return array();
			}

			$preset = $presets[$animation];

			// Filter and opacity keep easing either way — that is how the custom
			// colour hover fades. Transform is only transitioned for the presets
			// that settle on one; a keyframed preset owns transform outright, and
			// transitioning it as well makes the motion fight its own easing.
			$transition = isset($preset['animation'])
				? 'filter 220ms ease, opacity 220ms ease'
				: 'transform 220ms ease, filter 220ms ease, opacity 220ms ease';

			return array(
				array(
					'selector' => $image_selector,
					'styles'   => array(
						'transition' => $transition,
						'transform'  => isset($preset['transform']) ? $preset['transform'] : null,
						'opacity'    => isset($preset['opacity']) ? $preset['opacity'] : null,
						'animation'  => isset($preset['animation']) ? $preset['animation'] : null,
					),
				),
			);
		}

		/**
		 * Build aspect-ratio and click-action rules.
		 *
		 * Resolution-specific widths removed - CSS custom properties now handle all slot dimensions.
		 * The resolution setting only affects which image file is served (backend), not display dimensions (CSS).
		 *
		 * Custom mode uses real CSS width/height (px/%/em, height may be auto), not an aspect-ratio fraction.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @param string                $device Desktop|Tablet|Mobile.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		private static function get_aspect_ratio_rules(array $selectors, array $attributes = array(), $device = 'Desktop')
		{
			$fixed    = $selectors['wrapper'] . ' .sp-lcpro-logo-carousel[data-sp-lcpro-aspect-ratio]:not([data-sp-lcpro-aspect-ratio="original"]):not([data-sp-lcpro-aspect-ratio="custom"])';
			$original = $selectors['wrapper'] . ' .sp-lcpro-logo-carousel[data-sp-lcpro-aspect-ratio="original"]';
			// When Display Elements is active the figure is the card (title/
			// description siblings). Neutralize the logo box on that figure and
			// re-host aspect/size onto the image — keep logos-only figure selectors
			// unchanged so List/Grid specificity ties still hold.
			$figure_de = '.sp-lcpro-logo-source__figure.sp-lcpro-logo-source__figure--has-display-elements';

			$rules = array(
				/*
				 * Original mode = uniform logo height. Both orientations use the same
				 * visual model: logos are compared by height, width stays natural, and
				 * max-width is only a safety cap for unusually wide marks.
				 */
				array(
					'selector' => $original . ' .sp-lcpro-logo-source__figure',
					'styles'   => array(
						'display'         => 'flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'position'        => 'relative',
						'box-sizing'      => 'border-box',
						'width'           => 'auto',
						'height'          => 'auto',
						'max-width'       => 'var(--sp-lcpro-slot-max-width, 200px)',
						'padding'         => 'var(--sp-lcpro-slot-padding-vertical, 8px) var(--sp-lcpro-slot-padding-horizontal, 6px)',
					),
				),
				array(
				// Logos-only: the figure IS the shaped slot, so it clips. A
				// logo card is not a slot — it owns its own clipping (see
				// LogoCardCss, which clips only when the card is rounded), so
				// this concern must not assert `overflow` there.
					'selector' => $original . ' .sp-lcpro-logo-source__figure:not(.sp-lcpro-logo-source__figure--has-display-elements)',
					'styles'   => array(
						'overflow' => 'hidden',
					),
				),
				array(
					'selector' => $original . ' ' . $figure_de,
					'styles'   => array(
						'width'        => '100%',
						'max-width'    => 'none',
						'height'       => 'auto',
						'aspect-ratio' => 'auto',
						// Padding is owned by LogoCardCss Inner Padding —
						// do not zero it here (this selector out-ranks the card).
					),
				),
				array(
					'selector' => $original . ' .sp-lcpro-logo-source__image',
					'styles'   => array(
						'display'         => 'block',
						'width'           => 'auto',
						'height'          => 'var(--sp-lcpro-slot-height, 52px)',
						'max-width'       => '100%',
						'object-fit'      => 'contain',
						'object-position' => 'center',
					),
				),
				array(
					'selector' => $original . ' ' . $figure_de . ' .sp-lcpro-logo-source__image',
					'styles'   => array(
						'max-width'  => 'var(--sp-lcpro-slot-max-width, 200px)',
						'align-self' => 'center',
					),
				),
				/*
				 * Fixed-ratio mode = figure owns a shaped box for logos-only; with
				 * Display Elements the image owns that box so title/description stay free.
				 * Flex + center keeps letterboxed / smaller marks centered in the slot
				 * (display:block left-aligned them whenever the img/wrapper did not fill).
				 */
				array(
					'selector' => $fixed . ' .sp-lcpro-logo-source__item',
					'styles'   => array(
						'align-items'     => 'center',
						'justify-content' => 'center',
					),
				),
				array(
					'selector' => $fixed . ' .sp-lcpro-logo-source__figure',
					'styles'   => array(
						'display'         => 'flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'position'        => 'relative',
						'box-sizing'      => 'border-box',
						'width'           => '100%',
						'max-width'       => 'var(--sp-lcpro-slot-max-width, 200px)',
						'padding'         => 'var(--sp-lcpro-slot-padding-vertical, 8px) var(--sp-lcpro-slot-padding-horizontal, 6px)',
					),
				),
				array(
				// Logos-only: the figure IS the shaped slot, so it clips. A
				// logo card is not a slot — it owns its own clipping (see
				// LogoCardCss, which clips only when the card is rounded), so
				// this concern must not assert `overflow` there.
					'selector' => $fixed . ' .sp-lcpro-logo-source__figure:not(.sp-lcpro-logo-source__figure--has-display-elements)',
					'styles'   => array(
						'overflow' => 'hidden',
					),
				),
				array(
					'selector' => $fixed . ' ' . $figure_de,
					'styles'   => array(
						'width'        => '100%',
						'max-width'    => 'none',
						'height'       => 'auto',
						'aspect-ratio' => 'auto',
						// Padding is owned by LogoCardCss Inner Padding.
					),
				),
				array(
					'selector' => $fixed . ' .sp-lcpro-logo-source__figure:not(.sp-lcpro-logo-source__figure--has-display-elements) > .sp-lcpro-logo-source__link, ' . $fixed . ' .sp-lcpro-logo-source__figure:not(.sp-lcpro-logo-source__figure--has-display-elements) > .sp-lcpro-logo-source__popup-trigger',
					'styles'   => array(
						'display'         => 'flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'width'           => '100%',
						'height'          => '100%',
					),
				),
				array(
					'selector' => $fixed . ' .sp-lcpro-logo-source__image',
					'styles'   => array(
						'display'         => 'block',
						'width'           => '100%',
						'height'          => '100%',
						'object-fit'      => 'contain',
						'object-position' => 'center',
					),
				),
				array(
					'selector' => $fixed . ' ' . $figure_de . ' > .sp-lcpro-logo-source__link, ' . $fixed . ' ' . $figure_de . ' > .sp-lcpro-logo-source__popup-trigger',
					'styles'   => array(
						'display'         => 'flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'align-self'      => 'center',
						'max-width'       => '100%',
						'line-height'     => '0',
					),
				),
				array(
					'selector' => $fixed . ' ' . $figure_de . ' .sp-lcpro-logo-source__image',
					'styles'   => array(
						'display'         => 'block',
						'width'           => '100%',
						'max-width'       => 'var(--sp-lcpro-slot-max-width, 200px)',
						'height'          => 'auto',
						'object-fit'      => 'contain',
						'object-position' => 'center',
						'align-self'      => 'center',
					),
				),
				array(
					'selector' => $selectors['wrapper'] . ' .sp-lcpro-logo-carousel[data-sp-lcpro-click-action="link"] .sp-lcpro-logo-source__link',
					'styles'   => array(
						'display'         => 'block',
						'color'           => 'inherit',
						'cursor'          => 'pointer',
						'text-decoration' => 'none',
					),
				),
				array(
					'selector' => $selectors['wrapper'] . ' .sp-lcpro-logo-carousel[data-sp-lcpro-click-action="popup"] .sp-lcpro-logo-source__popup-trigger',
					'styles'   => array(
						'display'      => 'block',
						'padding'      => '0',
						'border'       => '0',
						'background'   => 'transparent',
						'box-shadow'   => 'none',
						'-webkit-appearance' => 'none',
						'appearance'   => 'none',
						'cursor'       => 'pointer',
					),
				),
				array(
					'selector' => $selectors['wrapper'] . ' .sp-lcpro-logo-carousel[data-sp-lcpro-click-action="none"] .sp-lcpro-logo-source__image',
					'styles'   => array(
						'cursor' => 'default',
					),
				),
			);

			foreach (array('1:1', '4:3', '3:4', '3:2', '2:3', '16:9', '9:16') as $ratio) {
				$css_ratio = str_replace(':', ' / ', $ratio);
				$root      = $selectors['wrapper'] . ' .sp-lcpro-logo-carousel[data-sp-lcpro-aspect-ratio="' . $ratio . '"]';
				$rules[]   = array(
					'selector' => $root . ' .sp-lcpro-logo-source__figure',
					'styles'   => array(
						'aspect-ratio' => $css_ratio,
					),
				);
				$rules[] = array(
					'selector' => $root . ' ' . $figure_de,
					'styles'   => array(
						'aspect-ratio' => 'auto',
					),
				);
				$rules[] = array(
					'selector' => $root . ' ' . $figure_de . ' .sp-lcpro-logo-source__image',
					'styles'   => array(
						'aspect-ratio' => $css_ratio,
					),
				);
			}

			return array_merge($rules, self::get_custom_size_rules($selectors, $attributes, $device));
		}

		/**
		 * Custom mode: logo box sized by CSS width/height (not aspect-ratio).
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @param string                $device Desktop|Tablet|Mobile.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		private static function get_custom_size_rules(array $selectors, array $attributes = array(), $device = 'Desktop')
		{
			$images_settings = isset($attributes['imagesSettings']) && is_array($attributes['imagesSettings']) ? $attributes['imagesSettings'] : array();
			$aspect_ratio    = isset($images_settings['aspectRatio']) ? (string) $images_settings['aspectRatio'] : 'original';

			if ('custom' !== $aspect_ratio) {
				return array();
			}

			$width  = LogoImagesSettingsConfig::resolve_css_size(
				isset($images_settings['customAspectRatioWidth']) ? $images_settings['customAspectRatioWidth'] : null,
				LogoImagesDefaults::single_responsive_default(100, '%'),
				LogoImagesDefaults::get_custom_width_units(),
				$device
			);
			$height = LogoImagesSettingsConfig::resolve_css_size(
				isset($images_settings['customAspectRatioHeight']) ? $images_settings['customAspectRatioHeight'] : null,
				LogoImagesDefaults::single_responsive_default('', 'auto'),
				LogoImagesDefaults::get_custom_height_units(),
				$device
			);

			$custom    = $selectors['wrapper'] . ' .sp-lcpro-logo-carousel[data-sp-lcpro-aspect-ratio="custom"]';
			$figure_de = '.sp-lcpro-logo-source__figure.sp-lcpro-logo-source__figure--has-display-elements';

			return array(
				array(
					'selector' => $custom . ' .sp-lcpro-logo-source__item',
					'styles'   => array(
						'align-items' => 'center',
					),
				),
				array(
					'selector' => $custom . ' .sp-lcpro-logo-source__figure',
					'styles'   => array(
						'display'         => 'flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'position'        => 'relative',
						'box-sizing'      => 'border-box',
						'width'           => $width,
						'height'          => $height,
						'max-width'       => '100%',
						'aspect-ratio'    => 'auto',
						'padding'         => 'var(--sp-lcpro-slot-padding-vertical, 8px) var(--sp-lcpro-slot-padding-horizontal, 6px)',
					),
				),
				array(
				// Logos-only: the figure IS the shaped slot, so it clips. A
				// logo card is not a slot — it owns its own clipping (see
				// LogoCardCss, which clips only when the card is rounded), so
				// this concern must not assert `overflow` there.
					'selector' => $custom . ' .sp-lcpro-logo-source__figure:not(.sp-lcpro-logo-source__figure--has-display-elements)',
					'styles'   => array(
						'overflow' => 'hidden',
					),
				),
				array(
					'selector' => $custom . ' ' . $figure_de,
					'styles'   => array(
						'width'        => '100%',
						'max-width'    => 'none',
						'height'       => 'auto',
						'aspect-ratio' => 'auto',
						// Padding is owned by LogoCardCss Inner Padding.
					),
				),
				array(
					// Click Action url/popup wraps the logo in an anchor or a
					// button, so the image is no longer the figure's child and
					// its percentage caps resolve against that wrapper instead of
					// the custom-sized figure. Left inline and sizeless (and
					// carrying a theme's own button/link padding), the wrapper
					// gave the image a different box and the custom width/height
					// stopped applying.
					//
					// The wrapper is made layout-neutral rather than given a size
					// of its own: it becomes exactly the figure's content box, so
					// the image resolves the same percentages it would as a
					// direct child. The logo therefore renders at the same size
					// under every Click Action — the wrapper only adds a click
					// target.
					'selector' => $custom . ' .sp-lcpro-logo-source__figure:not(.sp-lcpro-logo-source__figure--has-display-elements) > .sp-lcpro-logo-source__link, ' . $custom . ' .sp-lcpro-logo-source__figure:not(.sp-lcpro-logo-source__figure--has-display-elements) > .sp-lcpro-logo-source__popup-trigger',
					'styles'   => array(
						'display'         => 'flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'width'           => '100%',
						'height'          => '100%',
						// A theme's own `a`/`button` chrome would shrink the
						// content box and with it the logo.
						'padding'         => '0',
						'border'          => '0',
						'box-sizing'      => 'border-box',
					),
				),
				array(
					'selector' => $custom . ' .sp-lcpro-logo-source__image',
					'styles'   => array(
						'display'         => 'block',
						'width'           => 'auto',
						'height'          => 'auto',
						'max-width'       => '100%',
						'max-height'      => '100%',
						'object-fit'      => 'contain',
						'object-position' => 'center',
					),
				),
				array(
					// Click Action url/popup wraps the logo, and with a Display
					// Elements card it is the IMAGE that carries the custom
					// width/height (the figure is the whole card, not the logo
					// box). A wrapper left to shrink-wrap therefore becomes the
					// box the image's percentages resolve against, and the logo
					// comes out smaller than the size that was set.
					//
					// So the wrapper takes the custom size itself and the image
					// fills it (see the image rule below). The wrapper is then
					// exactly the box the image would have occupied as a direct
					// child, and the logo renders at the same size under every
					// Click Action — the wrapper only adds a click target. The
					// logos-only branch above solves the same problem the other
					// way round, because there the FIGURE carries the size.
					'selector' => $custom . ' ' . $figure_de . ' > .sp-lcpro-logo-source__link, ' . $custom . ' ' . $figure_de . ' > .sp-lcpro-logo-source__popup-trigger',
					'styles'   => array(
						'display'         => 'flex',
						'align-items'     => 'center',
						'justify-content' => 'center',
						'align-self'      => 'center',
						'width'           => $width,
						'height'          => $height,
						'max-width'       => '100%',
						// A theme's own `a`/`button` chrome would shrink the
						// content box and with it the logo.
						'padding'         => '0',
						'border'          => '0',
						'box-sizing'      => 'border-box',
						'line-height'     => '0',
					),
				),
				array(
					// Inside the wrapper the custom size is already spent on the
					// wrapper, so the image fills it instead of resolving the
					// percentage a second time.
					'selector' => $custom . ' ' . $figure_de . ' > .sp-lcpro-logo-source__link .sp-lcpro-logo-source__image, ' . $custom . ' ' . $figure_de . ' > .sp-lcpro-logo-source__popup-trigger .sp-lcpro-logo-source__image',
					'styles'   => array(
						'width'      => '100%',
						'height'     => '100%',
						'max-width'  => '100%',
						'max-height' => '100%',
					),
				),
				array(
					'selector' => $custom . ' ' . $figure_de . ' .sp-lcpro-logo-source__image',
					'styles'   => array(
						'display'         => 'block',
						'width'           => $width,
						'height'          => $height,
						'max-width'       => '100%',
						// 'aspect-ratio' => 'auto',
						'object-fit'      => 'contain',
						'object-position' => 'center',
						'align-self'      => 'center',
					),
				),
			);
		}

		/**
		 * Build preview styles from the normalized schema branch.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, mixed>}>
		 */
		private static function get_style_preview_rules(array $selectors, array $attributes)
		{
			$logo_images_style = self::get_logo_images_style($attributes);
			$normal_state      = isset($logo_images_style['normal']) && is_array($logo_images_style['normal']) ? $logo_images_style['normal'] : array();
			$hover_state       = isset($logo_images_style['hover']) && is_array($logo_images_style['hover']) ? $logo_images_style['hover'] : array();
			$hover_animation   = isset($logo_images_style['hoverAnimation']) && is_string($logo_images_style['hoverAnimation']) ? $logo_images_style['hoverAnimation'] : 'none';
			$margin_sides      = self::get_spacing_sides(isset($logo_images_style['margin']) && is_array($logo_images_style['margin']) ? $logo_images_style['margin'] : array());
			$margin            = $margin_sides['shorthand'];
			$force_inset_styles = self::get_horizontal_inset_width_styles($margin_sides, 'force-width');
			$max_only_inset_styles = self::get_horizontal_inset_width_styles($margin_sides, 'max-only');
			$carousel_root = $selectors['wrapper'] . ' .sp-lcpro-logo-carousel';
			$logos_only_figure = '.sp-lcpro-logo-source__figure:not(.sp-lcpro-logo-source__figure--has-display-elements)';
			$figure_de = '.sp-lcpro-logo-source__figure.sp-lcpro-logo-source__figure--has-display-elements';
			$logos_only_fixed_figure_selector = $carousel_root . '[data-sp-lcpro-aspect-ratio]:not([data-sp-lcpro-aspect-ratio="original"]):not([data-sp-lcpro-aspect-ratio="custom"]) ' . $logos_only_figure;
			$logos_only_original_figure_selector = $carousel_root . '[data-sp-lcpro-aspect-ratio="original"] ' . $logos_only_figure;
			$logos_only_custom_figure_selector = $carousel_root . '[data-sp-lcpro-aspect-ratio="custom"] ' . $logos_only_figure;
			$display_elements_fixed_image_selector = $carousel_root . '[data-sp-lcpro-aspect-ratio]:not([data-sp-lcpro-aspect-ratio="original"]):not([data-sp-lcpro-aspect-ratio="custom"]) ' . $figure_de . ' .sp-lcpro-logo-source__image';
			$display_elements_original_image_selector = $carousel_root . '[data-sp-lcpro-aspect-ratio="original"] ' . $figure_de . ' .sp-lcpro-logo-source__image';
			$display_elements_custom_image_selector = $carousel_root . '[data-sp-lcpro-aspect-ratio="custom"] ' . $figure_de . ' .sp-lcpro-logo-source__image';
			$normal_filter     = self::get_filter_value(isset($normal_state['imageFilter']) && is_array($normal_state['imageFilter']) ? $normal_state['imageFilter'] : array());
			$hover_filter      = self::get_filter_value(isset($hover_state['imageFilter']) && is_array($hover_state['imageFilter']) ? $hover_state['imageFilter'] : array());
			$normal_opacity    = self::normalize_opacity(isset($normal_state['opacity']) ? $normal_state['opacity'] : null, 100);
			$hover_opacity     = self::normalize_opacity(isset($hover_state['opacity']) ? $hover_state['opacity'] : null, 100);
			$normal_overlay    = isset($normal_state['overlay']) && is_array($normal_state['overlay']) ? $normal_state['overlay'] : array();
			$hover_overlay     = isset($hover_state['overlay']) && is_array($hover_state['overlay']) ? $hover_state['overlay'] : array();
			$normal_custom     = ! empty($normal_state['customColorEnabled'])
				? self::get_custom_color_filter(CssUtils::sanitize_css_color(isset($normal_state['customColor']) ? $normal_state['customColor'] : '', ''))
				: '';
			$hover_custom      = ! empty($hover_state['customColorEnabled'])
				? self::get_custom_color_filter(CssUtils::sanitize_css_color(isset($hover_state['customColor']) ? $hover_state['customColor'] : '', ''))
				: '';
			$tintable_image_selector = self::tintable_image_selector($selectors);
			$hover_tintable_image_selector = self::hover_tintable_image_selector($selectors);
			$custom_base_filter = 'grayscale(1) brightness(0) saturate(100%)';
			$active_hover_custom = $hover_custom ? $hover_custom : $normal_custom;
			$normal_image_filter = self::merge_filters(
				$normal_custom ? $custom_base_filter : '',
				$normal_custom,
				$normal_filter
			);
			$hover_image_filter  = self::merge_filters(
				$active_hover_custom ? $custom_base_filter : '',
				$active_hover_custom,
				$hover_filter
			);

			$rules = array(
				array(
					// Logos-only fixed/custom: margin the figure and shrink width so L/R
					// margin cannot push the logo outside Carousel/Grid items.
					'selector' => $logos_only_fixed_figure_selector . ', ' . $logos_only_custom_figure_selector,
					'styles'   => array_merge(
						array(
							'margin' => $margin,
						),
						$force_inset_styles
					),
				),
				array(
					// Logos-only original: keep natural width, only cap max-width.
					'selector' => $logos_only_original_figure_selector,
					'styles'   => array_merge(
						array(
							'margin' => $margin,
						),
						$max_only_inset_styles
					),
				),
				array(
					// Display Elements fixed/custom: margin the image only.
					'selector' => $display_elements_fixed_image_selector . ', ' . $display_elements_custom_image_selector,
					'styles'   => array_merge(
						array(
							'margin' => $margin,
						),
						$force_inset_styles
					),
				),
				array(
					// Display Elements original: margin the image, keep natural width.
					'selector' => $display_elements_original_image_selector,
					'styles'   => array_merge(
						array(
							'margin' => $margin,
						),
						$max_only_inset_styles
					),
				),
				array(
					// Visual-only base. Box sizing (width/height/max-width/object-fit) is owned
					// by the aspect-ratio mode rules in get_aspect_ratio_rules.
					'selector' => $selectors['wrapper'] . ' .sp-lcpro-logo-carousel .sp-lcpro-logo-source__image',
					'styles'   => array(
						'transition' => 'transform 220ms ease, filter 220ms ease, opacity 220ms ease',
						'filter'     => $normal_filter,
						'opacity'    => $normal_opacity,
					),
				),
				array(
					'selector' => $tintable_image_selector,
					'styles'   => array(
						'filter' => $normal_image_filter,
					),
				),
				array(
					'selector' => $selectors['wrapper'] . ' .sp-lcpro-logo-carousel .sp-lcpro-logo-source__figure::after',
					'styles'   => array(
						'content'        => '""',
						'position'       => 'absolute',
						'inset'          => '0',
						'pointer-events' => 'none',
						'background'     => self::overlay_background_value($normal_overlay),
						'opacity'        => self::normalize_overlay_opacity(isset($normal_overlay['opacity']) ? $normal_overlay['opacity'] : null, 0),
						'transition'     => 'opacity 220ms ease, background 220ms ease',
					),
				),
				array(
					'selector' => self::hover_host($selectors) . ':hover .sp-lcpro-logo-source__image',
					'styles'   => array(
						'filter'  => $hover_filter,
						'opacity' => $hover_opacity,
					),
				),
				array(
					'selector' => $hover_tintable_image_selector,
					'styles'   => array(
						'filter' => $hover_image_filter,
					),
				),
				// The overlay is the figure's OWN pseudo-element, so it stays on the
				// figure rather than following the hover host: hosting it on Table's
				// row would tint the whole row instead of the logo. Table's figure
				// is `display: contents`, so it paints nothing there — same as the
				// normal-state overlay rule above.
				array(
					'selector' => $selectors['wrapper'] . ' .sp-lcpro-logo-carousel .sp-lcpro-logo-source__figure:hover::after',
					'styles'   => array(
						'background' => self::overlay_background_value($hover_overlay),
						'opacity'    => self::normalize_overlay_opacity(isset($hover_overlay['opacity']) ? $hover_overlay['opacity'] : null, 0),
					),
				),
			);

			$rules = array_merge($rules, self::get_hover_effect_rules($selectors, $hover_animation));

			return $rules;
		}

		/**
		 * Build a background value for overlay preview output.
		 *
		 * Solid overlays read the `color` field; gradient overlays read the
		 * `gradient` field, which already holds a complete CSS gradient string
		 * emitted by the editor's gradient picker — mirrors
		 * CssValueHelper::background_value(). Every declaration is run through
		 * CssUtils::sanitize_declaration_value() before it reaches the
		 * stylesheet, so the gradient is passed through verbatim.
		 *
		 * @param array<string, mixed> $overlay Overlay branch.
		 * @return string
		 */
		private static function overlay_background_value(array $overlay)
		{
			$type = isset($overlay['type']) ? (string) $overlay['type'] : 'solid';

			if ('gradient' === $type) {
				$gradient = isset($overlay['gradient']) ? trim((string) $overlay['gradient']) : '';

				return '' !== $gradient ? $gradient : 'transparent';
			}

			$color = CssUtils::sanitize_css_color(isset($overlay['color']) ? $overlay['color'] : '', 'transparent');

			return $color ?: 'transparent';
		}

		/**
		 * Normalize overlay opacity into a CSS decimal string.
		 *
		 * @param mixed $value Raw overlay opacity.
		 * @param int   $default Default percentage.
		 * @return string
		 */
		private static function normalize_overlay_opacity($value, $default)
		{
			if (! is_numeric($value)) {
				$value = $default;
			}

			$value = max(0, min(1, (float) $value));

			return rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
		}

		/**
		 * Normalize opacity into a CSS decimal string.
		 *
		 * @param mixed $value Raw percentage.
		 * @param int   $default Default percentage.
		 * @return string
		 */
		private static function normalize_opacity($value, $default)
		{
			if (! is_numeric($value)) {
				$value = $default;
			}

			$value = max(0, min(100, (float) $value)) / 100;

			return rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
		}
	}
}

if (! class_exists('LogoImagesCssColorSolver')) {
	/**
	 * Converts a target RGB color into the legacy CSS filter chain.
	 */
	class LogoImagesCssColorSolver
	{
		/** @var float */
		private $target_r;
		/** @var float */
		private $target_g;
		/** @var float */
		private $target_b;
		/** @var float */
		private $target_a;
		/** @var array<string, float> */
		private $target_hsl;
		/** @var array<string, float> */
		private $reused_color;

		public function __construct($r, $g, $b, $a = 1.0)
		{
			$this->target_r   = self::clamp_byte($r);
			$this->target_g   = self::clamp_byte($g);
			$this->target_b   = self::clamp_byte($b);
			$this->target_a   = max(0, min(1, (float) $a));
			$this->target_hsl = self::hsl(
				$this->target_r,
				$this->target_g,
				$this->target_b
			);
			$this->reused_color = array(
				'r' => 0.0,
				'g' => 0.0,
				'b' => 0.0,
			);
		}

		public function solve()
		{
			$wide = $this->solve_wide();
			$best = $this->solve_narrow($wide);

			return $this->css($best['values']);
		}

		private function solve_wide()
		{
			$a = array(60, 180, 18000, 600, 1.2, 1.2);
			$best = array(
				'loss'   => INF,
				'values' => array(50, 20, 3750, 50, 100, 100),
			);

			for ($i = 0; $best['loss'] > 25 && $i < 3; $i++) {
				$result = $this->spsa(5, $a, 15, array(50, 20, 3750, 50, 100, 100), 1000);
				if ($result['loss'] < $best['loss']) {
					$best = $result;
				}
			}

			return $best;
		}

		private function solve_narrow(array $wide)
		{
			$a0 = $wide['loss'] + 1;
			return $this->spsa(
				$wide['loss'],
				array(0.25 * $a0, 0.25 * $a0, $a0, 0.25 * $a0, 0.2 * $a0, 0.2 * $a0),
				2,
				$wide['values'],
				500
			);
		}

		private function spsa($A, array $a, $c, array $values, $iterations)
		{
			$best = null;
			$best_loss = INF;

			for ($k = 0; $k < $iterations; $k++) {
				$ck = $c / pow($k + 1, 1 / 6);
				$deltas = array();
				$high_args = array();
				$low_args = array();

				for ($i = 0; $i < 6; $i++) {
					$deltas[$i] = mt_rand(0, 1) ? 1 : -1;
					$high_args[$i] = $values[$i] + $ck * $deltas[$i];
					$low_args[$i]  = $values[$i] - $ck * $deltas[$i];
				}

				$loss_diff = $this->loss($high_args) - $this->loss($low_args);
				for ($i = 0; $i < 6; $i++) {
					$gradient  = ($loss_diff / (2 * $ck)) * $deltas[$i];
					$ak        = $a[$i] / pow($A + $k + 1, 1);
					$values[$i] = $this->fix_value($values[$i] - $ak * $gradient, $i);
				}

				$loss = $this->loss($values);
				if ($loss < $best_loss) {
					$best = $values;
					$best_loss = $loss;
				}
			}

			return array(
				'values' => $best,
				'loss'   => $best_loss,
			);
		}

		private function loss(array $filters)
		{
			$color = array('r' => 0.0, 'g' => 0.0, 'b' => 0.0);
			$color = $this->invert($color, $filters[0] / 100);
			$color = $this->sepia($color, $filters[1] / 100);
			$color = $this->saturate($color, $filters[2] / 100);
			$color = $this->hue_rotate($color, $filters[3] * 3.6);
			$color = $this->brightness($color, $filters[4] / 100);
			$color = $this->contrast($color, $filters[5] / 100);

			$color_hsl = self::hsl($color['r'], $color['g'], $color['b']);

			return abs($color['r'] - $this->target_r)
				+ abs($color['g'] - $this->target_g)
				+ abs($color['b'] - $this->target_b)
				+ abs($color_hsl['h'] - $this->target_hsl['h'])
				+ abs($color_hsl['s'] - $this->target_hsl['s'])
				+ abs($color_hsl['l'] - $this->target_hsl['l']);
		}

		private function css(array $filters)
		{
			return sprintf(
				'invert(%d%%) sepia(%d%%) saturate(%d%%) hue-rotate(%ddeg) brightness(%d%%) contrast(%d%%) opacity(%s)',
				round($filters[0]),
				round($filters[1]),
				round($filters[2]),
				round($filters[3] * 3.6),
				round($filters[4]),
				round($filters[5]),
				rtrim(rtrim(sprintf('%.3F', $this->target_a), '0'), '.')
			);
		}

		private function fix_value($value, $idx)
		{
			$max = 100;
			if (2 === $idx) {
				$max = 7500;
			} elseif (4 === $idx || 5 === $idx) {
				$max = 200;
			}

			if (3 === $idx) {
				if ($value > $max) {
					return fmod($value, $max);
				}
				if ($value < 0) {
					return $max + fmod($value, $max);
				}
				return $value;
			}

			return max(0, min($max, $value));
		}

		private function invert(array $color, $value)
		{
			return array(
				'r' => self::clamp_byte(($value + ($color['r'] / 255) * (1 - 2 * $value)) * 255),
				'g' => self::clamp_byte(($value + ($color['g'] / 255) * (1 - 2 * $value)) * 255),
				'b' => self::clamp_byte(($value + ($color['b'] / 255) * (1 - 2 * $value)) * 255),
			);
		}

		private function sepia(array $color, $value)
		{
			return $this->multiply(
				$color,
				array(
					0.393 + 0.607 * (1 - $value),
					0.769 - 0.769 * (1 - $value),
					0.189 - 0.189 * (1 - $value),
					0.349 - 0.349 * (1 - $value),
					0.686 + 0.314 * (1 - $value),
					0.168 - 0.168 * (1 - $value),
					0.272 - 0.272 * (1 - $value),
					0.534 - 0.534 * (1 - $value),
					0.131 + 0.869 * (1 - $value),
				)
			);
		}

		private function saturate(array $color, $value)
		{
			return $this->multiply(
				$color,
				array(
					0.213 + 0.787 * $value,
					0.715 - 0.715 * $value,
					0.072 - 0.072 * $value,
					0.213 - 0.213 * $value,
					0.715 + 0.285 * $value,
					0.072 - 0.072 * $value,
					0.213 - 0.213 * $value,
					0.715 - 0.715 * $value,
					0.072 + 0.928 * $value,
				)
			);
		}

		private function hue_rotate(array $color, $angle)
		{
			$radians = deg2rad($angle);
			$sin = sin($radians);
			$cos = cos($radians);

			return $this->multiply(
				$color,
				array(
					0.213 + 0.787 * $cos - 0.213 * $sin,
					0.715 - 0.715 * $cos - 0.715 * $sin,
					0.072 - 0.072 * $cos + 0.928 * $sin,
					0.213 - 0.213 * $cos + 0.143 * $sin,
					0.715 + 0.285 * $cos + 0.14 * $sin,
					0.072 - 0.072 * $cos - 0.283 * $sin,
					0.213 - 0.213 * $cos - 0.787 * $sin,
					0.715 - 0.715 * $cos + 0.715 * $sin,
					0.072 + 0.928 * $cos + 0.072 * $sin,
				)
			);
		}

		private function brightness(array $color, $value)
		{
			return $this->linear($color, $value, 0);
		}

		private function contrast(array $color, $value)
		{
			return $this->linear($color, $value, -0.5 * $value + 0.5);
		}

		private function linear(array $color, $slope, $intercept)
		{
			return array(
				'r' => self::clamp_byte($color['r'] * $slope + $intercept * 255),
				'g' => self::clamp_byte($color['g'] * $slope + $intercept * 255),
				'b' => self::clamp_byte($color['b'] * $slope + $intercept * 255),
			);
		}

		private function multiply(array $color, array $matrix)
		{
			return array(
				'r' => self::clamp_byte($color['r'] * $matrix[0] + $color['g'] * $matrix[1] + $color['b'] * $matrix[2]),
				'g' => self::clamp_byte($color['r'] * $matrix[3] + $color['g'] * $matrix[4] + $color['b'] * $matrix[5]),
				'b' => self::clamp_byte($color['r'] * $matrix[6] + $color['g'] * $matrix[7] + $color['b'] * $matrix[8]),
			);
		}

		private static function hsl($r, $g, $b)
		{
			$r /= 255;
			$g /= 255;
			$b /= 255;
			$max = max($r, $g, $b);
			$min = min($r, $g, $b);
			$h = 0.0;
			$s = 0.0;
			$l = ($max + $min) / 2;

			if ($max !== $min) {
				$d = $max - $min;
				$s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

				if ($max === $r) {
					$h = ($g - $b) / $d + ($g < $b ? 6 : 0);
				} elseif ($max === $g) {
					$h = ($b - $r) / $d + 2;
				} else {
					$h = ($r - $g) / $d + 4;
				}
				$h /= 6;
			}

			return array(
				'h' => $h * 100,
				's' => $s * 100,
				'l' => $l * 100,
			);
		}

		private static function clamp_byte($value)
		{
			return max(0, min(255, (float) $value));
		}
	}
}
