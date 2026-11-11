<?php
/**
 * Saved Templates — dedicated block-HTML CPT + render shortcode.
 *
 * Stores a reusable block layout in `post_content` (authored in the block
 * editor) and renders it anywhere via `[lc_template id="X"]` → do_blocks().
 * Sibling-aligned with woo (`sps_store_template`) and wp-carousel-pro
 * (`sp_wpcp_template`). Gated by the `saved-templates` feature module: when the
 * module is off the CPT is not registered and the shortcode renders nothing.
 *
 * Registration + rendering only — the dashboard CRUD lives in
 * Admin\Dashboard\SavedTemplatesRestController. Pure helpers are static so they
 * unit-test without WordPress.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/includes
 * @author     ShapedPlugin<support@shapedplugin.com>
 */

namespace ShapedPlugin\LogoCarouselFree\Includes;

use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\ModulesVisibility;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\SavedTemplates' ) ) {

	/**
	 * Registers the saved-template CPT and its render shortcode.
	 */
	class SavedTemplates {

		/**
		 * Custom post type slug.
		 *
		 * @var string
		 */
		const POST_TYPE = 'sp_lc_template';

		/**
		 * Render shortcode tag.
		 *
		 * @var string
		 */
		const SHORTCODE = 'lc_template';

		/**
		 * Feature-module slug that gates this feature.
		 *
		 * @var string
		 */
		const MODULE = 'saved-templates';

		/**
		 * Module visibility store (the gate).
		 *
		 * @var ModulesVisibility
		 */
		private $modules;

		/**
		 * IDs currently being rendered — guards against a template embedding
		 * its own shortcode (infinite recursion).
		 *
		 * @var array<int, bool>
		 */
		private static $rendering = array();

		/**
		 * Constructor.
		 *
		 * @param ModulesVisibility|null $modules Injected for tests.
		 */
		public function __construct( ?ModulesVisibility $modules = null ) {
			$this->modules = $modules ? $modules : new ModulesVisibility();

			add_action( 'init', array( $this, 'register_post_type' ), 11 );
			add_action( 'admin_init', array( $this, 'redirect_template_list' ) );
			add_filter( 'use_block_editor_for_post_type', array( $this, 'force_block_editor' ), 10, 2 );
			// "View Post" / permalink in the block editor → Saved Templates tab.
			add_filter( 'post_type_link', array( $this, 'filter_template_permalink' ), 10, 2 );
			// Shortcode is always registered so a disabled module degrades to an
			// empty string rather than leaking the raw tag to visitors.
			add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
		}

		/**
		 * Admin URL for the dashboard Saved Templates tab.
		 *
		 * @return string
		 */
		public static function templates_tab_url() {
			return admin_url( 'edit.php?post_type=sp_logo_carousel&page=lcpro_dashboard' ) . '#save-templates';
		}

		/**
		 * Redirect the native CPT list table to the dashboard Saved Templates tab.
		 *
		 * @return void
		 */
		public function redirect_template_list() {
			if ( ! $this->is_module_enabled() ) {
				return;
			}

			global $pagenow;

			if ( 'edit.php' !== $pagenow ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only redirect gate.
			$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

			if ( self::POST_TYPE !== $post_type ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

			if ( 'lcpro_dashboard' === $page ) {
				return;
			}

			wp_safe_redirect( self::templates_tab_url() );
			exit;
		}

		/**
		 * Point template permalinks (editor "View Post") at the Saved Templates tab.
		 *
		 * @param string       $post_link Default permalink.
		 * @param \WP_Post|int $post      Post object or ID.
		 * @return string
		 */
		public function filter_template_permalink( $post_link, $post ) {
			$post = get_post( $post );

			if ( ! $post || self::POST_TYPE !== $post->post_type ) {
				return $post_link;
			}

			return self::templates_tab_url();
		}

		/**
		 * Whether the saved-templates module is enabled.
		 *
		 * @return bool
		 */
		public function is_module_enabled() {
			return (bool) $this->modules->is_enabled( self::MODULE );
		}

		/**
		 * Register the CPT (only when the module is enabled).
		 *
		 * @return void
		 */
		public function register_post_type() {
			if ( ! $this->is_module_enabled() ) {
				return;
			}

			register_post_type( self::POST_TYPE, self::post_type_args() );
		}

		/**
		 * CPT registration args. Static + side-effect-free so it is unit
		 * testable; block editor is forced via `show_in_rest`.
		 *
		 * @return array<string, mixed>
		 */
		public static function post_type_args() {
			$capability = apply_filters( 'sp_lc_ui_permission', 'manage_options' );
			$show_ui    = function_exists( 'current_user_can' ) ? current_user_can( $capability ) : true;

			$labels = array(
				'name'               => __( 'Saved Templates', 'logo-carousel-free' ),
				'singular_name'      => __( 'Saved Template', 'logo-carousel-free' ),
				'menu_name'          => __( 'Saved Templates', 'logo-carousel-free' ),
				'add_new'            => __( 'Add New Template', 'logo-carousel-free' ),
				'add_new_item'       => __( 'Add New Template', 'logo-carousel-free' ),
				'edit_item'          => __( 'Edit Template', 'logo-carousel-free' ),
				'new_item'           => __( 'New Template', 'logo-carousel-free' ),
				'view_item'          => __( 'View Template', 'logo-carousel-free' ),
				'search_items'       => __( 'Search Templates', 'logo-carousel-free' ),
				'not_found'          => __( 'No templates found', 'logo-carousel-free' ),
				'not_found_in_trash' => __( 'No templates found in Trash', 'logo-carousel-free' ),
			);

			return array(
				'label'               => __( 'Saved Templates', 'logo-carousel-free' ),
				'labels'              => $labels,
				'description'         => __( 'Reusable block layouts for Logo Carousel.', 'logo-carousel-free' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => $show_ui,
				'show_in_menu'        => false,
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'hierarchical'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'show_in_rest'        => true,
				'menu_position'       => 81,
				'supports'            => array( 'title', 'editor' ),
				'capability_type'     => 'post',
			);
		}

		/**
		 * Force the block editor for our CPT even when the Classic Editor
		 * plugin is active (templates are block markup by definition).
		 *
		 * @param bool   $use_block_editor Current decision.
		 * @param string $post_type        Post type being evaluated.
		 * @return bool
		 */
		public function force_block_editor( $use_block_editor, $post_type ) {
			if ( self::POST_TYPE === $post_type ) {
				return true;
			}

			return $use_block_editor;
		}

		/**
		 * Build the render shortcode for a template ID (pure).
		 *
		 * @param int $id Template post ID.
		 * @return string e.g. `[lc_template id="12"]`.
		 */
		public static function build_shortcode( $id ) {
			return sprintf( '[%s id="%d"]', self::SHORTCODE, absint( $id ) );
		}

		/**
		 * Whether a template post may be rendered for the current viewer (pure).
		 *
		 * Published templates render for everyone; non-published render only for
		 * a user who can edit them (live preview in the editor).
		 *
		 * @param \WP_Post|null $post           Fetched post (or null).
		 * @param bool          $module_enabled Module gate state.
		 * @param bool          $can_edit       Whether the viewer can edit it.
		 * @return bool
		 */
		public static function can_render( $post, $module_enabled, $can_edit ) {
			if ( ! $module_enabled || ! $post || self::POST_TYPE !== $post->post_type ) {
				return false;
			}

			if ( 'publish' === $post->post_status ) {
				return true;
			}

			return (bool) $can_edit;
		}

		/**
		 * Render `[lc_template id="X"]` → the template's blocks.
		 *
		 * @param array<string, mixed>|string $atts Shortcode attributes.
		 * @return string Rendered HTML (empty on any failure).
		 */
		public function render_shortcode( $atts ) {
			$atts = shortcode_atts( array( 'id' => 0 ), (array) $atts, self::SHORTCODE );
			$id   = absint( $atts['id'] );

			if ( ! $id || isset( self::$rendering[ $id ] ) ) {
				return '';
			}

			$post = get_post( $id );

			if ( ! self::can_render( $post, $this->is_module_enabled(), current_user_can( 'edit_post', $id ) ) ) {
				return '';
			}

			if ( post_password_required( $post ) ) {
				return get_the_password_form( $post );
			}

			// Guard the block-render pass against self-embedding recursion.
			self::$rendering[ $id ] = true;
			$content                = do_shortcode( do_blocks( $post->post_content ) );
			unset( self::$rendering[ $id ] );

			if ( '' === trim( (string) $content ) ) {
				return '';
			}

			return sprintf(
				'<div class="lcpro-template lcpro-template-%d">%s</div>',
				$id,
				$content
			);
		}
	}
}
