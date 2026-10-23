<?php
/**
 * Ready Patterns REST controller.
 *
 * @package ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns;

defined( 'ABSPATH' ) || exit;

/**
 * Registers /logo-carousel-pro/v1/patterns REST routes.
 */
class Controller {

	/**
	 * Pattern repository.
	 *
	 * @var PatternRepository
	 */
	private $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new PatternRepository();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			'logo-carousel-pro/v1',
			'/patterns',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_patterns' ),
				'permission_callback' => array( $this, 'can_edit_posts' ),
				'args'                => array(
					'force' => array(
						'default'           => false,
						'sanitize_callback' => static function ( $value ) {
							return rest_sanitize_boolean( $value );
						},
					),
				),
			)
		);

		register_rest_route(
			'logo-carousel-pro/v1',
			'/patterns/(?P<id>[\\w-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_pattern' ),
				'permission_callback' => array( $this, 'can_edit_posts' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Own path — "/patterns/(?P<id>)" above would swallow "/patterns/favorites".
		register_rest_route(
			'logo-carousel-pro/v1',
			'/pattern-favorites',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_favorites' ),
					'permission_callback' => array( $this, 'can_edit_posts' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_favorites' ),
					'permission_callback' => array( $this, 'can_edit_posts' ),
					'args'                => array(
						'id'     => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'slug'   => array(
							'default'           => '',
							'sanitize_callback' => 'sanitize_title',
						),
						'action' => array(
							'default'           => 'add',
							'validate_callback' => static function ( $value ) {
								return in_array( $value, array( 'add', 'remove' ), true );
							},
						),
					),
				),
			)
		);
	}

	/**
	 * Permission check for editor users.
	 *
	 * A disabled module serves nothing, whatever the caller's capabilities —
	 * hiding the editor entry point while the routes keep answering would leave
	 * "is this module off?" unanswerable from the server's side.
	 *
	 * The return type is left off because a permission callback may return
	 * either a bool or a WP_Error, and union types need PHP 8.
	 *
	 * @return bool|\WP_Error True when allowed, WP_Error otherwise.
	 */
	public function can_edit_posts() {
		if ( ! PatternRepository::is_enabled() ) {
			return new \WP_Error(
				'sp_lcpro_ready_patterns_disabled',
				__( 'The Ready Patterns Library module is turned off.', 'logo-carousel-free' ),
				array( 'status' => 403 )
			);
		}

		return current_user_can( 'edit_posts' );
	}

	/**
	 * Return the normalized pattern manifest.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_patterns( \WP_REST_Request $request ): \WP_REST_Response {
		$force = (bool) $request->get_param( 'force' );
		$items = $this->repository->get_manifest( $force );

		return rest_ensure_response( $items );
	}

	/**
	 * Return a single pattern payload.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_pattern( \WP_REST_Request $request ) {
		$pattern_id = $request->get_param( 'id' );
		$content    = $this->repository->get_pattern_content( $pattern_id );

		if ( null === $content || '' === $content || ! self::is_valid_pattern_markup( $content ) ) {
			return new \WP_Error(
				'sp_lcpro_pattern_not_found',
				__( 'Pattern not found.', 'logo-carousel-free' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response(
			array(
				'content' => $content,
			)
		);
	}

	/**
	 * Return the favorited pattern IDs.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_favorites(): \WP_REST_Response {
		return rest_ensure_response(
			array(
				'favorites' => PatternFavorites::get_all(),
			)
		);
	}

	/**
	 * Add or remove a pattern from the current user's favorites.
	 *
	 * The durable slug is the stored key; the ID is passed so a pre-slug entry for
	 * the same pattern is rewritten instead of lingering as a duplicate.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function update_favorites( \WP_REST_Request $request ): \WP_REST_Response {
		$pattern_id = $request->get_param( 'id' );
		$slug       = (string) $request->get_param( 'slug' );
		$action     = $request->get_param( 'action' );
		$key        = '' !== $slug ? $slug : $pattern_id;
		$legacy_id  = '' !== $slug ? $pattern_id : null;

		$favorites = 'remove' === $action
			? PatternFavorites::remove( $key, $legacy_id )
			: PatternFavorites::add( $key, $legacy_id );

		return rest_ensure_response(
			array(
				'favorites' => $favorites,
			)
		);
	}

	/**
	 * Whether serialized markup contains at least one WPCP block anywhere.
	 *
	 * A cheap gate that keeps foreign payloads from reaching the client; composite
	 * section patterns start with a wrapper, so position is not checked. The
	 * authoritative allow-list is client-side, where the registered block types and
	 * this site's `allowed_block_types` are actually known.
	 *
	 * @param string $content Serialized block markup.
	 * @return bool
	 */
	private static function is_valid_pattern_markup( string $content ): bool {
		if ( strlen( $content ) > PatternRepository::MAX_PAYLOAD_BYTES ) {
			return false;
		}

		return 1 === preg_match( '/<!--\s+wp:sp-logo-carousel\/[\w-]+/i', $content );
	}
}
