<?php
/**
 * AI REST Controller.
 *
 * Registers `/logo-carousel-pro/v1/ai-generate`, the one route the editor's AI
 * builder talks to.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AIRestController' ) ) {

	/**
	 * REST surface for the AI builder.
	 */
	class AIRestController {

		/**
		 * REST namespace, shared with the Ready Patterns controller.
		 */
		const NAMESPACE_V1 = 'logo-carousel-pro/v1';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		/**
		 * Register REST routes.
		 *
		 * @return void
		 */
		public function register_routes() {
			register_rest_route(
				self::NAMESPACE_V1,
				'/ai-generate',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'generate' ),
					'permission_callback' => array( $this, 'can_generate' ),
					'args'                => array(
						'prompt'             => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'blockName'          => array(
							'default'           => AIController::DEFAULT_BLOCK,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'existingAttributes' => array(
							'default'           => array(),
							'validate_callback' => static function ( $value ) {
								return is_array( $value );
							},
						),
						// The builder retries with this off when a matched pattern
						// turns out to be unusable in the caller's editor session.
						'allowPatterns'      => array(
							'default'           => true,
							'sanitize_callback' => static function ( $value ) {
								return rest_sanitize_boolean( $value );
							},
						),
						// False when the user picked a block in the modal rather
						// than leaving it on Auto. `blockName` is a default the
						// prompt may override, and a person who explicitly chose
						// Grid did not ask for the word "slider" to move them to
						// the Carousel.
						'autoRoute'          => array(
							'default'           => true,
							'sanitize_callback' => static function ( $value ) {
								return rest_sanitize_boolean( $value );
							},
						),
						// Picked in the modal when more than one provider is set
						// up. Empty means the one saved in Settings; the resolver
						// drops anything it does not recognise.
						'provider'           => array(
							'default'           => '',
							'sanitize_callback' => 'sanitize_key',
						),
						'model'              => array(
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				)
			);
		}

		/**
		 * Whether this request may generate.
		 *
		 * Whatever the caller's capabilities: the "Build with AI" module being
		 * off, or the site switching AI off (the `WP_AI_SUPPORT` constant or the
		 * `wp_supports_ai` filter), stops this route answering. Hiding the editor
		 * entry points while the route kept generating would leave the module
		 * state bypassable by anything that can reach the REST API. Returning the
		 * reason rather than false keeps an editor with a stale payload from being
		 * told it has a login problem.
		 *
		 * @return bool|\WP_Error
		 */
		public function can_generate() {
			if ( ! AIFeature::is_available() ) {
				return AIFeature::unavailable_error();
			}

			return current_user_can( 'edit_posts' );
		}

		/**
		 * Answer a natural language prompt with a Ready Pattern or generated attributes.
		 *
		 * The response is mode-tagged: `pattern` carries the manifest item the
		 * editor should insert, `attributes` carries a generated configuration.
		 *
		 * @param \WP_REST_Request $request Request object.
		 * @return \WP_REST_Response|\WP_Error
		 */
		public function generate( $request ) {
			$prompt              = $request->get_param( 'prompt' );
			$block_name          = $request->get_param( 'blockName' );
			$existing_attributes = $request->get_param( 'existingAttributes' );
			$allow_patterns      = $request->get_param( 'allowPatterns' );
			$auto_route          = $request->get_param( 'autoRoute' );

			if ( empty( $prompt ) ) {
				return new \WP_Error(
					'lcpro_ai_empty_prompt',
					__( 'Prompt text is required.', 'logo-carousel-free' ),
					array( 'status' => 400 )
				);
			}

			$target_block = (string) ( $block_name ? $block_name : AIController::DEFAULT_BLOCK );

			$result = AIController::generate_block_config(
				(string) $prompt,
				$target_block,
				is_array( $existing_attributes ) ? $existing_attributes : array(),
				(bool) $allow_patterns,
				(bool) $auto_route,
				(string) $request->get_param( 'provider' ),
				(string) $request->get_param( 'model' )
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( 'pattern' === $result['mode'] ) {
				return rest_ensure_response(
					array(
						'success'    => true,
						'mode'       => 'pattern',
						'blockName'  => $result['pattern']['blockName'],
						'pattern'    => $result['pattern'],
						'confidence' => $result['confidence'],
						'reason'     => $result['reason'],
					)
				);
			}

			return rest_ensure_response(
				array(
					'success'              => true,
					'mode'                 => 'attributes',
					'blockName'            => $result['blockName'],
					'attributes'           => $result['attributes'],
					// Non-empty only when a Ready Pattern was actually attempted
					// and the model explicitly declined every candidate — surfaced
					// so the editor can tell the user why they got one generic
					// block instead of the full layout they asked for, instead of
					// failing silently.
					'patternDeclineReason' => isset( $result['patternDeclineReason'] ) ? $result['patternDeclineReason'] : '',
				)
			);
		}
	}
}
