<?php
/**
 * REST controller for the Saved Templates dashboard tab.
 *
 * Exposes list / duplicate / delete over the saved-template CPT under
 * `lcpro/v1/templates`. Every route is capability-gated (manage_options,
 * filterable) and protected by the core cookie + `wp_rest` nonce the SPA sends
 * as `X-WP-Nonce`. Routes only register when the `saved-templates` module is on.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/admin/dashboard
 * @author     ShapedPlugin<support@shapedplugin.com>
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\Dashboard;

use ShapedPlugin\LogoCarouselFree\Includes\SavedTemplates;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Admin\Dashboard\SavedTemplatesRestController' ) ) {

	/**
	 * CRUD-lite REST endpoints for saved templates.
	 */
	class SavedTemplatesRestController {

		/**
		 * REST namespace.
		 *
		 * @var string
		 */
		const NAMESPACE = 'lcpro/v1';

		/**
		 * REST base.
		 *
		 * @var string
		 */
		const BASE = 'templates';

		/**
		 * Upper bound on templates returned in one list call.
		 *
		 * @var int
		 */
		const MAX_ITEMS = 200;

		/**
		 * Saved-templates feature (CPT constants + module gate).
		 *
		 * @var SavedTemplates
		 */
		private $templates;

		/**
		 * Constructor.
		 *
		 * @param SavedTemplates|null $templates Injected for tests.
		 */
		public function __construct( ?SavedTemplates $templates = null ) {
			$this->templates = $templates ? $templates : new SavedTemplates();

			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		/**
		 * Register the REST routes (module-gated).
		 *
		 * @return void
		 */
		public function register_routes() {
			if ( ! $this->templates->is_module_enabled() ) {
				return;
			}

			$permission = array( $this, 'check_permission' );

			register_rest_route(
				self::NAMESPACE,
				'/' . self::BASE,
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_items' ),
						'permission_callback' => $permission,
					),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/' . self::BASE . '/duplicate',
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'duplicate_item' ),
						'permission_callback' => $permission,
						'args'                => array(
							'id' => array(
								'required'          => true,
								'sanitize_callback' => 'absint',
								'validate_callback' => static function ( $value ) {
									return absint( $value ) > 0;
								},
							),
						),
					),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/' . self::BASE . '/delete',
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'delete_items' ),
						'permission_callback' => $permission,
						'args'                => array(
							'ids' => array(
								'required' => true,
							),
						),
					),
				)
			);
		}

		/**
		 * Capability check for every route.
		 *
		 * @return bool
		 */
		public function check_permission() {
			$capability = apply_filters( 'sp_lc_ui_permission', 'manage_options' );

			return current_user_can( $capability );
		}

		/**
		 * GET /templates — list templates (published + drafts), newest first.
		 *
		 * @return WP_REST_Response
		 */
		public function get_items() {
			$query = new WP_Query(
				array(
					'post_type'              => SavedTemplates::POST_TYPE,
					'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
					'posts_per_page'         => self::MAX_ITEMS,
					'orderby'                => 'date',
					'order'                  => 'DESC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'suppress_filters'       => false,
				)
			);

			$items = array();
			foreach ( $query->posts as $post ) {
				$items[] = $this->prepare_item( $post );
			}

			return rest_ensure_response( $items );
		}

		/**
		 * POST /templates/duplicate — clone a template as a draft.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function duplicate_item( WP_REST_Request $request ) {
			$id     = absint( $request->get_param( 'id' ) );
			$source = get_post( $id );

			if ( ! $source || SavedTemplates::POST_TYPE !== $source->post_type ) {
				return new WP_Error(
					'lcpro_template_not_found',
					__( 'Template not found.', 'logo-carousel-free' ),
					array( 'status' => 404 )
				);
			}

			$title = $this->duplicate_title( $source->post_title );

			$new_id = wp_insert_post(
				array(
					'post_type'    => SavedTemplates::POST_TYPE,
					'post_status'  => 'draft',
					'post_title'   => $title,
					'post_content' => $source->post_content,
				),
				true
			);

			if ( is_wp_error( $new_id ) ) {
				return new WP_Error(
					'lcpro_template_duplicate_failed',
					__( 'Could not duplicate the template.', 'logo-carousel-free' ),
					array( 'status' => 500 )
				);
			}

			return rest_ensure_response( $this->prepare_item( get_post( $new_id ) ) );
		}

		/**
		 * POST /templates/delete — trash one or many templates.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function delete_items( WP_REST_Request $request ) {
			$ids = self::normalize_ids( $request->get_param( 'ids' ) );

			if ( empty( $ids ) ) {
				return new WP_Error(
					'lcpro_template_no_ids',
					__( 'No templates selected.', 'logo-carousel-free' ),
					array( 'status' => 400 )
				);
			}

			$deleted = array();
			foreach ( $ids as $id ) {
				$post = get_post( $id );

				if ( ! $post || SavedTemplates::POST_TYPE !== $post->post_type ) {
					continue;
				}

				if ( ! current_user_can( 'delete_post', $id ) ) {
					continue;
				}

				if ( wp_trash_post( $id ) ) {
					$deleted[] = $id;
				}
			}

			return rest_ensure_response( array( 'deleted' => $deleted ) );
		}

		/**
		 * Shape a post for the dashboard table.
		 *
		 * @param \WP_Post $post Template post.
		 * @return array<string, mixed>
		 */
		private function prepare_item( $post ) {
			$title    = get_the_title( $post );
			$edit_url = get_edit_post_link( $post->ID, 'raw' );

			$blocks   = self::block_types( $post->post_content );

			return array(
				'id'          => (int) $post->ID,
				'title'       => '' !== $title ? $title : __( '(no title)', 'logo-carousel-free' ),
				'status'      => $post->post_status,
				'date'        => $post->post_date,
				'dateDisplay' => get_the_date( '', $post ),
				'shortcode'   => SavedTemplates::build_shortcode( $post->ID ),
				'editUrl'     => $edit_url ? $edit_url : admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
				'blockTypes'  => $blocks,
				'blockLabel'  => self::block_label( $blocks ),
			);
		}

		/**
		 * Display names for the blocks this plugin ships.
		 *
		 * @return array<string, string> Block slug => column label.
		 */
		private static function block_names() {
			return array(
				'carousel' => __( 'Carousel', 'logo-carousel-free' ),
				'grid'     => __( 'Grid', 'logo-carousel-free' ),
				'list'     => __( 'List', 'logo-carousel-free' ),
				'marquee'  => __( 'Marquee', 'logo-carousel-free' ),
				'table'    => __( 'Table', 'logo-carousel-free' ),
			);
		}

		/**
		 * Which of this plugin's blocks a template holds, in document order.
		 *
		 * Reads the serialized block delimiters rather than `parse_blocks()`:
		 * the column needs names only, and the list endpoint runs this over
		 * every template in the library. Pure — unit tested.
		 *
		 * @param string $content Saved template content.
		 * @return string[] Unique block labels, first use first.
		 */
		public static function block_types( $content ) {
			if ( ! is_string( $content ) || '' === $content ) {
				return array();
			}

			if ( ! preg_match_all( '#<!--\s+wp:sp-logo-carousel/([a-z0-9-]+)#', $content, $matches ) ) {
				return array();
			}

			$known  = self::block_names();
			$labels = array();

			foreach ( $matches[1] as $slug ) {
				$label = isset( $known[ $slug ] ) ? $known[ $slug ] : ucwords( str_replace( '-', ' ', $slug ) );

				$labels[ $label ] = $label;
			}

			return array_values( $labels );
		}

		/**
		 * Collapse the block list into the single Block-column label.
		 *
		 * @param string[] $types Labels from block_types().
		 * @return string Empty when the template holds none of our blocks.
		 */
		public static function block_label( array $types ) {
			if ( empty( $types ) ) {
				return '';
			}

			if ( 1 === count( $types ) ) {
				return $types[0];
			}

			/* translators: 1: first block name, 2: how many further block types the template holds. */
			return sprintf( __( '%1$s +%2$d', 'logo-carousel-free' ), $types[0], count( $types ) - 1 );
		}

		/**
		 * Append " (Copy)" to a duplicated template's title.
		 *
		 * @param string $title Source title.
		 * @return string
		 */
		private function duplicate_title( $title ) {
			$title = '' !== $title ? $title : __( 'Untitled template', 'logo-carousel-free' );

			/* translators: %s: source template title. */
			return sprintf( __( '%s (Copy)', 'logo-carousel-free' ), $title );
		}

		/**
		 * Coerce a raw `ids` param (array or JSON/CSV string) to positive ints
		 * (pure — unit tested).
		 *
		 * @param mixed $raw Incoming ids.
		 * @return int[] Unique positive integers.
		 */
		public static function normalize_ids( $raw ) {
			if ( is_string( $raw ) ) {
				$decoded = json_decode( $raw, true );
				$raw     = is_array( $decoded ) ? $decoded : explode( ',', $raw );
			}

			if ( ! is_array( $raw ) ) {
				$raw = array( $raw );
			}

			$ids = array();
			foreach ( $raw as $value ) {
				$id = absint( $value );
				if ( $id > 0 ) {
					$ids[ $id ] = $id;
				}
			}

			return array_values( $ids );
		}
	}
}
