<?php
/**
 * AI Controller.
 *
 * Orchestrates showcase generation: Ready Pattern matching first, attribute
 * generation second. Transport lives behind a TextGateway, so this class never
 * knows whether the request goes out through WordPress Connectors or through
 * this plugin's own stored key.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

use ShapedPlugin\LogoCarouselFree\Includes\AI\Gateway\GatewayResolver;
use ShapedPlugin\LogoCarouselFree\Includes\AI\Gateway\RemoteHttpGateway;
use ShapedPlugin\LogoCarouselFree\Includes\AI\Gateway\TextGateway;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AIController' ) ) {

	/**
	 * The AI builder's one entry point.
	 */
	class AIController {

		/**
		 * Longest prompt sent to a provider. Anything past this is layout prose,
		 * not intent.
		 */
		const MAX_PROMPT_LENGTH = 1000;

		/**
		 * Default block a request is built on when the caller names none.
		 */
		const DEFAULT_BLOCK = 'sp-logo-carousel/carousel';

		/**
		 * Build a logo showcase from a natural language prompt.
		 *
		 * Ready Patterns come first: a prompt a published pattern already answers
		 * is served by inserting that pattern, so nothing has to be assembled
		 * attribute by attribute. Attribute generation runs only when no pattern
		 * fits the request, and then on the block the prompt itself names
		 * (`AIBlockRouter`) — the returned `blockName` is what the editor should
		 * build, not necessarily what it asked for.
		 *
		 * @param string $prompt              User prompt string.
		 * @param string $block_name          Target block slug, a default the prompt may override.
		 * @param array  $existing_attributes Existing attributes map if updating.
		 * @param bool   $allow_patterns      Whether a Ready Pattern may answer this prompt.
		 * @param bool   $auto_route          Whether the prompt may move the build to another block.
		 * @param string $provider            Provider picked in the AI builder, or empty for the saved one.
		 * @param string $model               Model picked in the AI builder, or empty for the provider's default.
		 * @return array|\WP_Error Mode-tagged result array or WP_Error.
		 */
		public static function generate_block_config( $prompt, $block_name = self::DEFAULT_BLOCK, array $existing_attributes = array(), $allow_patterns = true, $auto_route = true, $provider = '', $model = '' ) {
			$gateway = GatewayResolver::resolve( $provider, $model );
			if ( is_wp_error( $gateway ) ) {
				return $gateway;
			}

			$prompt = self::trim_prompt( $prompt );

			// The free plugin builds only Carousel and Grid: a request aimed at
			// any other block (a Pro-only List/Marquee/Table) falls back to the
			// default rather than returning a block type this site cannot insert.
			if ( ! isset( AICatalogBuilder::SCHEMA_MAP[ AICatalogBuilder::slug( $block_name ) ] ) ) {
				$block_name = self::DEFAULT_BLOCK;
			}

			// Set only when a pattern was actually attempted and the model
			// explicitly declined every candidate — not for allow_patterns=false,
			// an empty shortlist, or an upstream HTTP/parse failure, none of which
			// are a real "no fit" verdict worth telling the user about.
			$pattern_decline_reason = '';

			// The patterns shortlisted for this prompt, kept so an attribute
			// build can borrow logos from the same topic without ranking twice.
			// Null means nothing was shortlisted yet.
			$pattern_candidates = null;

			if ( $allow_patterns && AIPatternMatcher::allows_pattern_insert( $existing_attributes ) ) {
				$pattern_result = self::match_ready_pattern( $gateway, $prompt, $block_name );
				if ( is_array( $pattern_result['match'] ) ) {
					return array(
						'mode'       => 'pattern',
						'pattern'    => $pattern_result['match']['pattern'],
						'confidence' => $pattern_result['match']['confidence'],
						'reason'     => $pattern_result['match']['reason'],
					);
				}
				$pattern_decline_reason = $pattern_result['declineReason'];
				$pattern_candidates     = $pattern_result['candidates'];
			}

			// No pattern answered the prompt, so this block is built from its own
			// settings — and every layer below reads them per block. A request that
			// names a block of its own has to reach it here, or it is answered with
			// another block's catalog and its own settings are dropped as unknown.
			// Only a fresh build is routed: existing attributes mean an author is
			// configuring a block that already exists, and that block is the
			// answer. A caller that picked a block by hand opts out with
			// `$auto_route` — their choice is not a default to improve on.
			if ( $auto_route && empty( $existing_attributes ) ) {
				$block_name = AIBlockRouter::resolve( $prompt, $block_name );
			}

			$raw_json = $gateway->generate_json(
				AIPromptBuilder::get_system_prompt( $block_name ),
				AIPromptBuilder::build_user_message( $prompt, $block_name, $existing_attributes )
			);

			// A failure names its provider and why it failed, so the builder can
			// say so and offer the next connected provider. Only the provider
			// asked is ever tried: switching behind the user's back would spend
			// their quota somewhere they did not choose.
			if ( is_wp_error( $raw_json ) ) {
				self::note_rejected_key( $gateway, $raw_json );
				return AIFailure::annotate( $raw_json, $gateway->get_provider() );
			}

			$parsed = self::decode_json_response( $raw_json );
			if ( ! is_array( $parsed ) ) {
				return AIFailure::annotate(
					new \WP_Error(
						'lcpro_ai_invalid_json',
						__( 'The AI provider replied, but not with a usable configuration. Please try again.', 'logo-carousel-free' ),
						array( 'status' => 500 )
					),
					$gateway->get_provider()
				);
			}

			// A block with no logos would hide every setting the prompt asked
			// for, so it borrows a matching Ready Pattern's demo logos.
			$attributes = AIPatternLogos::fill(
				AISchemaValidator::sanitize_and_merge( $parsed, $block_name, $existing_attributes ),
				$prompt,
				$block_name,
				$pattern_candidates
			);

			return array(
				'mode'                 => 'attributes',
				'blockName'            => $block_name,
				'attributes'           => $attributes,
				'patternDeclineReason' => $pattern_decline_reason,
			);
		}

		/**
		 * Ask the model to pick a Ready Pattern for the prompt.
		 *
		 * No candidates means no request: a prompt nothing in the catalog
		 * resembles is not worth a round-trip. A failed or unusable selection
		 * resolves to a null match so the caller falls through to attribute
		 * generation — but unlike a bare null, this always reports why, when the
		 * model itself said why. The shortlist always comes back too, so a
		 * fallback build can borrow logos from the same patterns.
		 *
		 * @param TextGateway $gateway    Resolved transport.
		 * @param string      $prompt     Sanitized user prompt.
		 * @param string      $block_name Target block slug.
		 * @return array<string, mixed>
		 */
		private static function match_ready_pattern( TextGateway $gateway, $prompt, $block_name ) {
			$candidates = AIPatternMatcher::shortlist( $prompt, $block_name );
			$no_match   = array(
				'match'         => null,
				'declineReason' => '',
				'candidates'    => $candidates,
			);

			if ( empty( $candidates ) ) {
				return $no_match;
			}

			$raw_json = $gateway->generate_json(
				AIPromptBuilder::get_pattern_selection_prompt(),
				AIPromptBuilder::build_pattern_selection_message(
					$prompt,
					$block_name,
					AIPatternMatcher::to_prompt_payload( $candidates )
				)
			);

			if ( is_wp_error( $raw_json ) ) {
				return $no_match;
			}

			$selection = self::decode_json_response( $raw_json );
			if ( ! is_array( $selection ) ) {
				return $no_match;
			}

			$match = AIPatternMatcher::resolve_selection( $selection, $candidates );

			return array(
				'match'         => $match,
				'declineReason' => null === $match && isset( $selection['reason'] ) && is_scalar( $selection['reason'] )
					? sanitize_text_field( (string) $selection['reason'] )
					: '',
				'candidates'    => $candidates,
			);
		}

		/**
		 * Record a provider refusing the key this plugin stores for it.
		 *
		 * Keys are revoked, rotated, and attached to accounts that stop paying
		 * long after they were verified. Without this, the settings screen keeps
		 * showing a key that worked once as current, and the only symptom is
		 * every generation failing.
		 *
		 * Only this plugin's own stored keys are marked: a credential held by
		 * WordPress Connectors is not ours to judge, and the key stored here as a
		 * fallback may be perfectly good.
		 *
		 * @param TextGateway $gateway Gateway that made the request.
		 * @param \WP_Error   $error   Error it returned.
		 * @return void
		 */
		private static function note_rejected_key( TextGateway $gateway, \WP_Error $error ) {
			if ( ! $gateway instanceof RemoteHttpGateway ) {
				return;
			}

			$data    = $error->get_error_data();
			$status  = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 0;
			$message = $error->get_error_message();

			// Gemini answers a bad key with 400 and says so in the message, where
			// every other provider here uses 401. 403 is left out on purpose: it is
			// also how providers report an unsupported region, a project-scoped key
			// or a model the organization cannot call, and switching AI off over
			// one of those would disable the feature for a working credential.
			$is_gemini_key_error = 400 === $status
				&& 'gemini' === $gateway->get_provider()
				&& false !== stripos( $message, 'api key' );

			if ( 401 !== $status && ! $is_gemini_key_error ) {
				return;
			}

			AIKeyManager::mark_key_invalid( $gateway->get_provider(), $message );
		}

		/**
		 * Decode a model response, tolerating markdown code fences.
		 *
		 * @param string $raw_json Raw response text.
		 * @return array|null Decoded array or null when unparseable.
		 */
		private static function decode_json_response( $raw_json ) {
			$clean_json = trim( (string) $raw_json );
			$clean_json = preg_replace( '/^```(?:json)?\s*/i', '', $clean_json );
			$clean_json = preg_replace( '/\s*```$/', '', (string) $clean_json );

			$parsed = json_decode( (string) $clean_json, true );

			return is_array( $parsed ) ? $parsed : null;
		}

		/**
		 * Constrain a prompt to the length a provider is asked to read.
		 *
		 * @param string $prompt Raw prompt.
		 * @return string
		 */
		private static function trim_prompt( $prompt ) {
			$prompt = trim( (string) $prompt );

			return mb_strlen( $prompt ) > self::MAX_PROMPT_LENGTH
				? mb_substr( $prompt, 0, self::MAX_PROMPT_LENGTH )
				: $prompt;
		}
	}
}
