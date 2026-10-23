<?php
/**
 * AI Failure.
 *
 * Names why a provider request failed, so the AI builder can say so and offer
 * the next connected provider instead of a bare error.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AIFailure' ) ) {

	/**
	 * Classifies and annotates a failed provider request.
	 */
	class AIFailure {

		/**
		 * Quota, rate limit, credits or overload.
		 */
		const LIMIT = 'limit';

		/**
		 * A reply with no usable JSON.
		 */
		const INCOMPLETE = 'incomplete';

		/**
		 * The provider did not answer in time.
		 */
		const TIMEOUT = 'timeout';

		/**
		 * The key or its permissions were refused.
		 */
		const AUTH = 'auth';

		/**
		 * Anything else.
		 */
		const ERROR = 'error';

		/**
		 * Why a request failed, as one key the editor can phrase.
		 *
		 * Providers word the same failure differently and share no code, so the
		 * HTTP status is read first and the message matched after it.
		 *
		 * @param \WP_Error $error Failed request.
		 * @return string One of the reason constants.
		 */
		public static function reason( \WP_Error $error ) {
			$code    = (string) $error->get_error_code();
			$message = (string) $error->get_error_message();
			$data    = $error->get_error_data();
			$status  = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 0;

			if ( 429 === $status || preg_match( '/quota|rate[ _-]?limit|exhausted|too many requests|overloaded|credit balance|insufficient[ _-]?(funds|quota)|billing/i', $message ) ) {
				return self::LIMIT;
			}

			if ( 'lcpro_ai_invalid_json' === $code ) {
				return self::INCOMPLETE;
			}

			if ( preg_match( '/timed? ?out|cURL error 28/i', $message ) ) {
				return self::TIMEOUT;
			}

			if ( 401 === $status || 403 === $status || preg_match( '/api[ _-]?key|unauthori[sz]ed|authenticat|permission denied/i', $message ) ) {
				return self::AUTH;
			}

			return self::ERROR;
		}

		/**
		 * Add the provider and the reason to a failed request's data.
		 *
		 * The REST response carries error data to the editor, which uses it to
		 * name the provider that failed and to offer the next one. The status is
		 * normalised to 502: the site did not fail, the provider did.
		 *
		 * @param \WP_Error $error    Failed request.
		 * @param string    $provider Provider slug that was asked.
		 * @return \WP_Error The same error with provider, label and reason set.
		 */
		public static function annotate( \WP_Error $error, $provider ) {
			// sanitize_key() without the WordPress dependency, so this stays
			// unit-testable.
			$provider = preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $provider ) );
			$data     = $error->get_error_data();
			$data     = is_array( $data ) ? $data : array();

			$data['provider'] = $provider;
			$data['label']    = isset( AIKeyManager::PROVIDER_LABELS[ $provider ] ) ? AIKeyManager::PROVIDER_LABELS[ $provider ] : '';
			$data['reason']   = self::reason( $error );

			if ( isset( $data['status'] ) ) {
				$data['providerStatus'] = (int) $data['status'];
			}
			$data['status'] = 502;

			$error->add_data( $data );

			return $error;
		}
	}
}
