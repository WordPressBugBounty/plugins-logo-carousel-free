<?php
/**
 * Resolve the set of Google Font families a block actually uses.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Styles;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GoogleFontsResolver' ) ) {
	/**
	 * Walks a block's normalized/raw attribute tree for `family` typography
	 * branches tagged `type: 'google'` by the editor (see
	 * `blocks/components/typography/typography.js`) and builds the
	 * fonts.googleapis.com URL for the families actually selected.
	 */
	class GoogleFontsResolver {
		/**
		 * Collect deduped `Family+Name:wght@000` tokens from a block's attributes.
		 *
		 * @param array<string, mixed> $attributes Block attributes (any depth).
		 * @return array<int, string>
		 */
		public static function collect_families( array $attributes ) {
			$families = array();
			self::walk( $attributes, $families );

			return array_values( array_unique( $families ) );
		}

		/**
		 * Build the fonts.googleapis.com CSS2 URL for a family token list.
		 *
		 * @param array<int, string> $families Tokens from collect_families().
		 * @return string Empty string when there is nothing to load.
		 */
		public static function build_url( array $families ) {
			if ( empty( $families ) ) {
				return '';
			}

			$params = array();
			foreach ( $families as $family ) {
				$params[] = 'family=' . $family;
			}

			return 'https://fonts.googleapis.com/css2?' . implode( '&', $params ) . '&display=swap';
		}

		/**
		 * Recursively scan for `family` typography branches.
		 *
		 * @param array<string, mixed> $node Current branch.
		 * @param array<int, string>   $families Accumulator (by reference).
		 * @return void
		 */
		private static function walk( array $node, array &$families ) {
			if (
				isset( $node['family'], $node['type'] )
				&& is_string( $node['family'] )
				&& '' !== $node['family']
				&& 'google' === $node['type']
			) {
				$families[] = self::to_token( $node['family'], isset( $node['fontWeight'] ) ? $node['fontWeight'] : '' );
			}

			foreach ( $node as $value ) {
				if ( is_array( $value ) ) {
					self::walk( $value, $families );
				}
			}
		}

		/**
		 * Build a single `Family+Name:wght@000` token.
		 *
		 * @param string $family Font family name.
		 * @param mixed  $font_weight Raw font-weight value.
		 * @return string
		 */
		private static function to_token( $family, $font_weight ) {
			$weight = preg_replace( '/\D/', '', (string) $font_weight );
			if ( '' === $weight ) {
				$weight = '400';
			}

			return str_replace( ' ', '+', $family ) . ':wght@' . $weight;
		}
	}
}
