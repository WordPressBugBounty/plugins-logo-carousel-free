<?php
/**
 * Saved template dropdown source for page builder integrations.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base;

use ShapedPlugin\LogoCarouselFree\Includes\SavedTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Single source of the published saved templates every builder's picker offers.
 *
 * Each builder wants a slightly different shape — its own "nothing selected" key and
 * label, and WPBakery wants label ⇒ ID rather than ID ⇒ label — so the query lives here
 * once and the shape is applied on the way out.
 *
 * @since 4.0.0
 */
class Template_List {

	/**
	 * Published templates, ID => title, newest first. Queried once per request.
	 *
	 * @var array|null
	 */
	private static $templates = null;

	/**
	 * Published saved templates, newest first.
	 *
	 * @since 4.0.0
	 *
	 * @return array ID => title.
	 */
	public static function all() {
		if ( null !== self::$templates ) {
			return self::$templates;
		}

		$templates = array();

		$query = new \WP_Query(
			array(
				'post_type'              => SavedTemplates::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 500,
				'orderby'                => 'ID',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $query->posts as $post ) {
			$templates[ $post->ID ] = ! empty( $post->post_title ) ? $post->post_title : '#' . $post->ID;
		}

		self::$templates = $templates;

		return self::$templates;
	}

	/**
	 * Templates with a leading placeholder entry, for a builder select control.
	 *
	 * @since 4.0.0
	 *
	 * @param string|int $placeholder_key   Value the control uses for "nothing selected".
	 * @param string     $placeholder_label Label for that entry.
	 * @return array Key => label.
	 */
	public static function options( $placeholder_key = '0', $placeholder_label = '' ) {
		if ( '' === $placeholder_label ) {
			$placeholder_label = esc_html__( '- Select Template -', 'logo-carousel-free' );
		}

		return array( $placeholder_key => $placeholder_label ) + self::all();
	}

	/**
	 * Templates keyed by label, the shape WPBakery's dropdown expects.
	 *
	 * @since 4.0.0
	 *
	 * @param string|int $placeholder_key   Value the control uses for "nothing selected".
	 * @param string     $placeholder_label Label for that entry.
	 * @return array Label => key.
	 */
	public static function flipped_options( $placeholder_key = 'none', $placeholder_label = '' ) {
		$flipped = array();

		foreach ( self::options( $placeholder_key, $placeholder_label ) as $key => $label ) {
			$flipped[ $label ] = $key;
		}

		return $flipped;
	}
}
