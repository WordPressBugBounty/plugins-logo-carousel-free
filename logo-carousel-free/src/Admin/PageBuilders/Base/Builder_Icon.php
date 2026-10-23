<?php
/**
 * Shared addon icon for page builder integrations.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Single source of the Logo Carousel addon icon shown in page builders.
 *
 * The mark is the plugin's monochrome `sp-logo-icon-block` glyph — the same icon
 * the classic "Logo Carousel Shortcode" Elementor widget has always used — so the
 * saved template element sits beside it in every builder's panel as one family.
 *
 * Every builder accepts a different form of it: Elementor and Bricks render an
 * icon class into `<i>` (the font glyph, which takes the panel's own icon colour),
 * Beaver and Divi take inline SVG markup (painted with `currentColor` for the same
 * reason), Oxygen renders a URL as an `<img>` and WPBakery paints a class as a
 * background image (both get the neutral grey file). This class hands each one the
 * form its API expects so no integration re-implements the lookup.
 *
 * @since 4.0.0
 */
class Builder_Icon {

	/**
	 * Class name used by builders that only accept a CSS class.
	 *
	 * @var string
	 */
	const CSS_CLASS = 'lcpro-builder-icon';

	/**
	 * Fontello glyph class of the plugin mark, for builders that render `<i class>`.
	 *
	 * @var string
	 */
	const FONT_CLASS = 'sp-logo-icon-block';

	/**
	 * Stylesheet handle of the glyph font. Shared with the classic Elementor
	 * widget (Element_Shortcode_Addons) so the sheet loads once when both run.
	 *
	 * @var string
	 */
	const FONT_HANDLE = 'logo_carousel_pro_elementor_addons_icon';

	/**
	 * Icon location relative to SP_LOGO_CAROUSEL_FREE_PATH / SP_LOGO_CAROUSEL_FREE_URL.
	 *
	 * Both constants already end in `src/`, so this must not repeat it.
	 *
	 * @var string
	 */
	const RELATIVE = 'Admin/PageBuilders/assets/icon.svg';

	/**
	 * The same mark on a 32:18 canvas, drawn 18 units tall and centred: the
	 * shape of the icons Oxygen's Add panel shows at 32px wide.
	 *
	 * @var string
	 */
	const RELATIVE_WIDE = 'Admin/PageBuilders/assets/icon-wide.svg';

	/**
	 * Cached inline SVG markup.
	 *
	 * @var string|null
	 */
	private static $svg = null;

	/**
	 * Icon URL, for builders that render the icon as an image.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public static function url() {
		return SP_LOGO_CAROUSEL_FREE_URL . self::RELATIVE;
	}

	/**
	 * URL of the 32:18 icon, for Oxygen.
	 *
	 * Oxygen draws an element icon 32px wide at its own aspect ratio inside a
	 * fixed 62px tile. Its icons are 32×18; the square file came out 32px tall
	 * and pushed a two-line label out of the tile.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public static function wide_url() {
		return SP_LOGO_CAROUSEL_FREE_URL . self::RELATIVE_WIDE;
	}

	/**
	 * Icon filesystem path, for builders that read the file from disk.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public static function path() {
		return SP_LOGO_CAROUSEL_FREE_PATH . self::RELATIVE;
	}

	/**
	 * Inline SVG markup, for builders that embed the icon directly.
	 *
	 * @since 4.0.0
	 *
	 * @return string Empty string when the file cannot be read.
	 */
	public static function svg() {
		if ( null === self::$svg ) {
			$path     = self::path();
			$contents = is_readable( $path ) ? file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
			// Inline markup follows the builder's icon colour instead of the file's grey.
			self::$svg = preg_replace( '/\sfill="#[0-9a-fA-F]{3,6}"/', ' fill="currentColor"', self::normalize_svg( trim( (string) $contents ) ), 1 );
		}

		return self::$svg;
	}

	/**
	 * Drop the intrinsic `width`/`height` off the root `<svg>` tag.
	 *
	 * Builders drop the icon into a small slot and size it from their own CSS,
	 * but they only set one axis — Divi's rule is
	 * `.et-fb-icon svg { display:block; width:100%; fill:inherit }`. Without both
	 * attributes the `viewBox` supplies the aspect ratio and the builder's own
	 * sizing wins.
	 *
	 * @since 4.0.0
	 *
	 * @param string $svg SVG markup.
	 * @return string
	 */
	public static function normalize_svg( $svg ) {
		if ( ! preg_match( '/<svg\b[^>]*>/i', $svg, $matches, PREG_OFFSET_CAPTURE ) ) {
			return $svg;
		}

		$open_tag = $matches[0][0];
		$offset   = $matches[0][1];
		$stripped = preg_replace( '/\s+(?:width|height)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $open_tag );

		return substr_replace( $svg, $stripped, $offset, strlen( $open_tag ) );
	}

	/**
	 * CSS painting the icon on builders that only accept a class name.
	 *
	 * Two flavours, because the builders hand the class to different kinds of element:
	 *
	 * - `$sized` true — a font-icon slot, `<i class="…">`, which is `display:inline` and
	 *   therefore has no box for a background to paint on. The image goes on a sized
	 *   `::before` and the host is made `inline-block`. (Elementor and Bricks use the
	 *   FONT_CLASS glyph instead, so nothing ships this flavour today.)
	 * - `$sized` false — an element that already is a sized box with a background, so the
	 *   image goes straight on it. WPBakery's `.vc_element-icon` works this way.
	 *
	 * @since 4.0.0
	 *
	 * @param string|null $selector Selector to paint. Defaults to the shared class.
	 * @param bool        $sized    Whether the icon lands in a font-icon slot.
	 * @return string
	 */
	public static function css( $selector = null, $sized = true ) {
		if ( empty( $selector ) ) {
			$selector = '.' . self::CSS_CLASS;
		}

		$paint = sprintf(
			'background-image:url("%s");background-repeat:no-repeat;background-position:center;background-size:contain;',
			esc_url( self::url() )
		);

		if ( ! $sized ) {
			return $selector . '{' . $paint . '}';
		}

		return sprintf(
			'%1$s{display:inline-block;width:1em;height:1em;line-height:1;}%1$s::before{content:"";display:block;width:100%%;height:100%%;%2$s}',
			$selector,
			$paint
		);
	}

	/**
	 * Inline SVG for Divi's module list.
	 *
	 * Divi draws an SVG module icon in a 28px box but reserves only 16px of
	 * layout for it (`margin: -6px`): its own marks sit in the middle 16px of the
	 * box. Ours fills the box edge to edge, so it ran 6px into the label below.
	 * Padding the viewBox by 6/16 of the artwork on each side draws the mark at
	 * 16px, like the native module icons.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public static function divi_svg() {
		return self::pad_viewbox( self::svg(), 6 / 16 );
	}

	/**
	 * Grow the root `<svg>` viewBox on every side, shrinking the artwork
	 * inside the same box.
	 *
	 * Pure.
	 *
	 * @since 4.0.0
	 *
	 * @param string $svg   SVG markup.
	 * @param float  $ratio Padding per side, as a share of the artwork's size.
	 * @return string Unchanged when the root tag has no usable viewBox.
	 */
	public static function pad_viewbox( $svg, $ratio ) {
		$number = '(-?[0-9]*\.?[0-9]+)';

		if ( ! preg_match( '/(<svg\b[^>]*\bviewBox\s*=\s*")\s*' . $number . '[\s,]+' . $number . '[\s,]+' . $number . '[\s,]+' . $number . '\s*"/i', $svg, $m ) ) {
			return $svg;
		}

		$width  = (float) $m[4];
		$height = (float) $m[5];
		$pad_x  = $width * $ratio;
		$pad_y  = $height * $ratio;

		$view_box = implode(
			' ',
			array_map(
				static function ( $value ) {
					return rtrim( rtrim( number_format( $value, 3, '.', '' ), '0' ), '.' );
				},
				array( (float) $m[2] - $pad_x, (float) $m[3] - $pad_y, $width + 2 * $pad_x, $height + 2 * $pad_y )
			)
		);

		return substr_replace( $svg, $m[1] . $view_box . '"', strpos( $svg, $m[0] ), strlen( $m[0] ) );
	}

	/**
	 * `et_builder_module_icons` callback shared by the Divi 4 and Divi 5 modules.
	 *
	 * Handing Divi a ready `icon_svg` skips the branch of
	 * `ET_Builder_Element::get_module_icons()` that reads the file itself (and would
	 * keep the raw `width`/`height`). A module registering this must not also set
	 * `$icon_path`, or the file contents would overwrite the value below.
	 *
	 * @since 4.0.0
	 *
	 * @param array        $icons Registered module icons.
	 * @param string|array $slugs Module slug(s) to paint.
	 * @return array
	 */
	public static function divi_module_icons( $icons, $slugs ) {
		if ( ! is_array( $icons ) ) {
			return $icons;
		}

		$svg = self::divi_svg();
		if ( empty( $svg ) ) {
			return $icons;
		}

		foreach ( (array) $slugs as $slug ) {
			$icons[ $slug ] = array( 'icon_svg' => $svg );
		}

		return $icons;
	}

	/**
	 * Enqueue the glyph font behind FONT_CLASS.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function enqueue_font() {
		wp_enqueue_style( self::FONT_HANDLE, SP_LOGO_CAROUSEL_FREE_URL . 'Admin/assets/css/fontello.min.css', array(), SP_LOGO_CAROUSEL_FREE_VERSION, 'all' );
	}

	/**
	 * Register, fill and enqueue a src-less stylesheet carrying the icon CSS.
	 *
	 * Builders print their element list from their own editor bootstrap, so the
	 * rule has to ride on a handle of our own instead of an inline style added to
	 * a shared handle after `wp_head`.
	 *
	 * @since 4.0.0
	 *
	 * @param string      $handle   Stylesheet handle.
	 * @param string|null $selector Selector to paint. Defaults to the shared class.
	 * @param bool        $sized    Whether to emit the `::before` sizing box.
	 * @return void
	 */
	public static function enqueue_css( $handle, $selector = null, $sized = true ) {
		if ( ! wp_style_is( $handle, 'registered' ) ) {
			wp_register_style( $handle, false, array(), SP_LOGO_CAROUSEL_FREE_VERSION );
			wp_add_inline_style( $handle, self::css( $selector, $sized ) );
		}

		wp_enqueue_style( $handle );
	}
}
