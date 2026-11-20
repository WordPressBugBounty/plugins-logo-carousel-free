<?php
/**
 * Server-side Masonry layout CSS.
 *
 * Mirrors the editor emitter `masonryLayoutDynamicCss.js` assertion-for-
 * assertion (same selectors, values, device buckets).
 *
 * Masonry is laid out in two steps:
 *   - this CSS renders an even grid of `--sp-lcpro-masonry-columns` columns
 *     with the two gaps — what shows before scripts run, or without them;
 *   - the shared masonry layout (`shared/masonry/masonryLayout.js`) reads the
 *     same columns and gaps back, marks the list `sp-lcpro-masonry--packed`
 *     and drops every visible logo into the currently shortest column (the
 *     packed rules below take the items out of the grid flow).
 *
 * Each logo fills its column's width. In Original mode it keeps its own
 * shape from the item's `--sp-lcpro-logo-ratio` (stamped by the server from
 * the stored image size, or measured on load), so a column's height is known
 * before the image arrives; fixed and Custom ratios size the logo box as in
 * every other block.
 *
 * Every value that varies by device is a custom property or a gap on the
 * list, so Tablet / Mobile only re-declare those.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Sections\MasonryLayout;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\MasonryLayoutDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Support\ResponsiveCount;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\MasonryLayoutCss', false ) ) {
	/**
	 * Masonry layout rules mirrored from the editor emitter.
	 */
	class MasonryLayoutCss {
		/**
		 * Class the masonry layout puts on the list once it has packed it.
		 *
		 * @var string
		 */
		const PACKED_CLASS = 'sp-lcpro-masonry--packed';

		/**
		 * Custom property each item carries its width ÷ height in.
		 *
		 * @var string
		 */
		const LOGO_RATIO_PROPERTY = '--sp-lcpro-logo-ratio';

		/**
		 * Base (Desktop) rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, string>}>
		 */
		public static function base_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Desktop' );
		}

		/**
		 * Tablet rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, string>}>
		 */
		public static function tablet_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Tablet' );
		}

		/**
		 * Mobile rules.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @return array<int, array{selector:string, styles:array<string, string>}>
		 */
		public static function mobile_rules( array $selectors, array $attributes = array() ) {
			return self::get_rules( $selectors, $attributes, 'Mobile' );
		}

		/**
		 * Width ÷ height as a CSS number, or '' when either side is unusable.
		 *
		 * Parity: `getLogoRatio()` in `shared/masonry/logoRatio.js`.
		 *
		 * @param mixed $width Image width.
		 * @param mixed $height Image height.
		 * @return string Ratio rounded to 4 decimals, clamped to 0.2–10.
		 */
		public static function logo_ratio( $width, $height ) {
			$w = is_numeric( $width ) ? (float) $width : 0.0;
			$h = is_numeric( $height ) ? (float) $height : 0.0;

			if ( $w <= 0 || $h <= 0 ) {
				return '';
			}

			$ratio = min( 10, max( 0.2, $w / $h ) );

			return (string) ( round( $ratio * 10000 ) / 10000 );
		}

		/**
		 * Inline style declaring an item's ratio, or '' when it is unknown (the
		 * frontend runtime then measures the loaded image).
		 *
		 * @param array<string, mixed> $item Normalized logo item.
		 * @return string
		 */
		public static function item_style( array $item ) {
			$ratio = self::logo_ratio(
				isset( $item['imageWidth'] ) ? $item['imageWidth'] : '',
				isset( $item['imageHeight'] ) ? $item['imageHeight'] : ''
			);

			return '' === $ratio ? '' : self::LOGO_RATIO_PROPERTY . ':' . $ratio;
		}

		/**
		 * Build the rules for one device bucket.
		 *
		 * @param array<string, string> $selectors Selector map.
		 * @param array<string, mixed>  $attributes Block attributes.
		 * @param string                $device Device key.
		 * @return array<int, array{selector:string, styles:array<string, string>}>
		 */
		private static function get_rules( array $selectors, array $attributes, $device ) {
			$layout  = isset( $attributes['layout'] ) && is_array( $attributes['layout'] ) ? $attributes['layout'] : array();
			$shared  = isset( $layout['shared'] ) && is_array( $layout['shared'] ) ? $layout['shared'] : array();
			$is_base = 'Desktop' === $device;

			$columns = ResponsiveCount::resolve( self::shared_first( $shared, $layout, 'columns' ), $device, MasonryLayoutDefaults::DEFAULT_COLUMNS[ $device ], 1, 12 );
			$h_gap   = self::single( self::shared_first_array( $shared, $layout, 'horizontalGap' ), $device );
			$v_gap   = self::single( self::shared_first_array( $shared, $layout, 'verticalGap' ), $device );
			if ( $is_base ) {
				$h_gap = '' === $h_gap ? '0px' : $h_gap;
				$v_gap = '' === $v_gap ? '0px' : $v_gap;
			}

			$wrapper       = isset( $selectors['wrapper'] ) ? $selectors['wrapper'] : '';
			$items         = isset( $selectors['logoSourceItems'] ) ? $selectors['logoSourceItems'] : $wrapper . ' .sp-lcpro-logo-source__items';
			$item          = isset( $selectors['logoSourceItem'] ) ? $selectors['logoSourceItem'] : $wrapper . ' .sp-lcpro-logo-source__item';
			$carousel_root = $wrapper . ' .sp-lcpro-logo-carousel';
			$original_root = $carousel_root . '[data-sp-lcpro-aspect-ratio="original"]';
			$fixed_root    = $carousel_root . '[data-sp-lcpro-aspect-ratio]:not([data-sp-lcpro-aspect-ratio="original"]):not([data-sp-lcpro-aspect-ratio="custom"])';

			$container = array( '--sp-lcpro-masonry-columns' => (string) $columns );
			if ( $is_base ) {
				// A logo box spans its whole column, whatever the global slot token says.
				$container['--sp-lcpro-slot-max-width'] = '100%';
				$container['position']                  = 'relative';
				$container['display']                   = 'grid';
				$container['grid-template-columns']     = 'repeat(var(--sp-lcpro-masonry-columns), minmax(0, 1fr))';
				$container['align-items']               = 'start';
				$container['width']                     = '100%';
				$container['box-sizing']                = 'border-box';
			}
			if ( '' !== $h_gap ) {
				$container['column-gap'] = $h_gap;
			}
			if ( '' !== $v_gap ) {
				$container['row-gap'] = $v_gap;
			}

			$rules = array(
				array(
					'selector' => $items,
					'styles'   => $container,
				),
			);

			if ( ! $is_base ) {
				return $rules;
			}

			$rules[] = array(
				'selector' => $item,
				'styles'   => array(
					'display'        => 'flex',
					'flex-direction' => 'column',
					'align-items'    => 'stretch',
					'min-width'      => '0',
					'width'          => 'auto',
					'height'         => 'auto',
					'box-sizing'     => 'border-box',
				),
			);

			// Packed: the layout script owns each item's column (width) and
			// position; the list keeps the tallest column's height.
			$rules[] = array(
				'selector' => $items . '.' . self::PACKED_CLASS,
				'styles'   => array( 'display' => 'block' ),
			);
			$rules[] = array(
				'selector' => $items . '.' . self::PACKED_CLASS . ' > .sp-lcpro-logo-source__item',
				'styles'   => array(
					'position'           => 'absolute',
					'top'                => '0',
					'inset-inline-start' => '0',
					'margin'             => '0',
				),
			);

			// The logo box spans its column.
			$rules[] = array(
				'selector' => $original_root . ' .sp-lcpro-logo-source__figure',
				'styles'   => array( 'width' => '100%' ),
			);

			// A link / popup wrapper spans the box so the image inside can fill it.
			$rules[] = array(
				'selector' => $original_root . ' .sp-lcpro-logo-source__figure > .sp-lcpro-logo-source__link, '
					. $original_root . ' .sp-lcpro-logo-source__figure > .sp-lcpro-logo-source__popup-trigger, '
					. $fixed_root . ' .sp-lcpro-logo-source__figure > .sp-lcpro-logo-source__link, '
					. $fixed_root . ' .sp-lcpro-logo-source__figure > .sp-lcpro-logo-source__popup-trigger',
				'styles'   => array( 'width' => '100%' ),
			);

			// Original: the logo fills its column's width at its own ratio (same
			// selector string as LogoImagesCss's Original image rule, so this wins).
			$rules[] = array(
				'selector' => $original_root . ' .sp-lcpro-logo-source__image',
				'styles'   => array(
					'width'        => '100%',
					'height'       => 'auto',
					'max-width'    => '100%',
					'aspect-ratio' => 'var(--sp-lcpro-logo-ratio, auto)',
					'object-fit'   => 'contain',
				),
			);

			return $rules;
		}

		/**
		 * Read a scalar field shared-first, then flat.
		 *
		 * @param array<string, mixed> $shared Layout shared branch.
		 * @param array<string, mixed> $layout Layout branch.
		 * @param string               $key Field key.
		 * @return mixed
		 */
		private static function shared_first( array $shared, array $layout, $key ) {
			if ( isset( $shared[ $key ] ) && '' !== $shared[ $key ] ) {
				return $shared[ $key ];
			}

			return isset( $layout[ $key ] ) ? $layout[ $key ] : null;
		}

		/**
		 * Read an object field shared-first, then flat.
		 *
		 * @param array<string, mixed> $shared Layout shared branch.
		 * @param array<string, mixed> $layout Layout branch.
		 * @param string               $key Field key.
		 * @return array<string, mixed>
		 */
		private static function shared_first_array( array $shared, array $layout, $key ) {
			if ( isset( $shared[ $key ] ) && is_array( $shared[ $key ] ) ) {
				return $shared[ $key ];
			}
			if ( isset( $layout[ $key ] ) && is_array( $layout[ $key ] ) ) {
				return $layout[ $key ];
			}

			return array();
		}

		/**
		 * Single responsive value (number + unit) for a device.
		 *
		 * @param array<string, mixed> $responsive Responsive value.
		 * @param string               $device Device key.
		 * @return string
		 */
		private static function single( array $responsive, $device ) {
			$value = isset( $responsive['device'][ $device ] ) ? $responsive['device'][ $device ] : '';

			if ( '' === $value || null === $value || is_array( $value ) ) {
				return '';
			}

			$unit = isset( $responsive['unit'][ $device ] ) && '' !== $responsive['unit'][ $device ] ? (string) $responsive['unit'][ $device ] : 'px';

			return $value . $unit;
		}
	}
}
