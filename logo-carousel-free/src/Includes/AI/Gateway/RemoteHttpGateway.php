<?php
/**
 * Remote HTTP Gateway.
 *
 * Talks to provider APIs directly with a key this plugin stores itself. Used
 * when the provider is not configured through WordPress Connectors — either
 * because the site predates the Connectors API, because the provider plugin
 * that implements the connector is missing, or because core ships no connector
 * for that provider at all (Groq).
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI\Gateway;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\Gateway\RemoteHttpGateway' ) ) {

	/**
	 * Completion transport backed by this plugin's own stored key.
	 */
	class RemoteHttpGateway implements TextGateway {

		/**
		 * How long to wait on a provider before giving up.
		 */
		const TIMEOUT = 30;

		/**
		 * Retired model ids, mapped to their current equivalent, per provider.
		 *
		 * A saved setting can outlive the model it names — a provider retires a
		 * model generation and the id starts 404ing, or gets shut down outright.
		 * Every provider consults this table the same way, so a saved setting
		 * keeps working through the next deprecation wave instead of failing
		 * silently until someone notices and re-saves it.
		 *
		 * A provider's array holds only ids with a confirmed or dated retirement
		 * — this is a live map of confirmed dead ids, not a place to guess ones
		 * that might exist. Groq's is empty: no Groq id has a confirmed/dated
		 * retirement in this codebase's own history yet.
		 *
		 * Connector-backed requests never consult this table: the AI client
		 * registry only ever offers models the provider currently serves.
		 *
		 * @var array<string, array<string, string>>
		 */
		const DEPRECATED_MODEL_MAP = array(
			'openai'    => array(
				'gpt-4-turbo' => 'gpt-5.6-terra',
			),
			'anthropic' => array(
				'claude-3-5-sonnet-20241022' => 'claude-sonnet-5',
				'claude-3-5-haiku-20241022'  => 'claude-haiku-4-5-20251001',
			),
			'groq'      => array(),
			'gemini'    => array(
				'gemini-1.5-flash'        => 'gemini-3.6-flash',
				'gemini-2.0-flash'        => 'gemini-3.6-flash',
				'gemini-2.5-flash'        => 'gemini-3.6-flash',
				'gemini-1.5-flash-latest' => 'gemini-3.6-flash',
				'gemini-1.5-pro'          => 'gemini-pro-latest',
				'gemini-2.5-pro'          => 'gemini-pro-latest',
				'gemini-3.6-pro'          => 'gemini-pro-latest',
			),
		);

		/**
		 * Plugin provider slug.
		 *
		 * @var string
		 */
		private $provider;

		/**
		 * Configured model id, possibly empty or retired.
		 *
		 * @var string
		 */
		private $model;

		/**
		 * Decrypted API key for this provider.
		 *
		 * @var string
		 */
		private $api_key;

		/**
		 * Constructor.
		 *
		 * @param string $provider Plugin provider slug.
		 * @param string $model    Configured model id.
		 * @param string $api_key  Decrypted API key.
		 */
		public function __construct( $provider, $model, $api_key ) {
			$this->provider = (string) $provider;
			$this->model    = (string) $model;
			$this->api_key  = (string) $api_key;
		}

		/**
		 * Provider slug this gateway is configured for.
		 *
		 * @return string Provider slug.
		 */
		public function get_provider() {
			return $this->provider;
		}

		/**
		 * Request one JSON completion from the configured provider.
		 *
		 * @param string $system System prompt.
		 * @param string $user   User message.
		 * @return string|\WP_Error Raw response text, or WP_Error on failure.
		 */
		public function generate_json( $system, $user ) {
			if ( '' === $this->api_key ) {
				return new \WP_Error(
					'lcpro_ai_missing_key',
					__( 'AI API key is missing. Please configure your API key in Logo Carousel Settings.', 'logo-carousel-free' ),
					array( 'status' => 400 )
				);
			}

			if ( 'gemini' === $this->provider ) {
				return self::request_gemini( $this->api_key, $this->model, $system, $user );
			}

			if ( 'anthropic' === $this->provider ) {
				return self::request_anthropic( $this->api_key, $this->model, $system, $user );
			}

			if ( 'groq' === $this->provider ) {
				return self::request_groq( $this->api_key, $this->model, $system, $user );
			}

			return self::request_openai( $this->api_key, $this->model, $system, $user );
		}

		/**
		 * Resolve the model id to actually request.
		 *
		 * An empty setting falls back to the provider's current default; a known
		 * retired id remaps to its current equivalent via DEPRECATED_MODEL_MAP.
		 *
		 * @param string $provider      Provider slug.
		 * @param string $model         Configured model, possibly empty or retired.
		 * @param string $default_model Model to use when none is configured.
		 * @return string
		 */
		private static function resolve_model( $provider, $model, $default_model ) {
			$resolved = ! empty( $model ) ? $model : $default_model;
			$remap    = isset( self::DEPRECATED_MODEL_MAP[ $provider ] ) ? self::DEPRECATED_MODEL_MAP[ $provider ] : array();

			return isset( $remap[ $resolved ] ) ? $remap[ $resolved ] : $resolved;
		}

		/**
		 * Send request to OpenAI's Chat Completions API.
		 *
		 * @param string $api_key API key.
		 * @param string $model   Model name.
		 * @param string $system  System prompt.
		 * @param string $user    User message.
		 * @return string|\WP_Error Raw response text or WP_Error.
		 */
		private static function request_openai( $api_key, $model, $system, $user ) {
			return self::request_openai_compatible(
				$api_key,
				'https://api.openai.com/v1/chat/completions',
				'openai',
				$model,
				'gpt-5.6-terra',
				$system,
				$user
			);
		}

		/**
		 * Send request to Groq's Chat Completions API.
		 *
		 * Groq speaks the same OpenAI Chat Completions wire format, just at its
		 * own host with its own (free-tier) models.
		 *
		 * @param string $api_key API key.
		 * @param string $model   Model name.
		 * @param string $system  System prompt.
		 * @param string $user    User message.
		 * @return string|\WP_Error Raw response text or WP_Error.
		 */
		private static function request_groq( $api_key, $model, $system, $user ) {
			return self::request_openai_compatible(
				$api_key,
				'https://api.groq.com/openai/v1/chat/completions',
				'groq',
				$model,
				'openai/gpt-oss-120b',
				$system,
				$user
			);
		}

		/**
		 * Send a Chat Completions request to any OpenAI-wire-compatible provider.
		 *
		 * @param string $api_key       API key.
		 * @param string $url           Provider's chat completions endpoint.
		 * @param string $provider      Provider slug, for DEPRECATED_MODEL_MAP lookup.
		 * @param string $model         Model name.
		 * @param string $default_model Model to fall back to when none is configured.
		 * @param string $system        System prompt.
		 * @param string $user          User message.
		 * @return string|\WP_Error Raw response text or WP_Error.
		 */
		private static function request_openai_compatible( $api_key, $url, $provider, $model, $default_model, $system, $user ) {
			$body = array(
				'model'           => self::resolve_model( $provider, $model, $default_model ),
				'messages'        => array(
					array(
						'role'    => 'system',
						'content' => $system,
					),
					array(
						'role'    => 'user',
						'content' => $user,
					),
				),
				'response_format' => array( 'type' => 'json_object' ),
				'temperature'     => self::TEMPERATURE,
			);

			$response = wp_remote_post(
				$url,
				array(
					'timeout' => self::TIMEOUT,
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode( $body ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status = wp_remote_retrieve_response_code( $response );
			$data   = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 !== $status ) {
				$msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'AI API request failed.', 'logo-carousel-free' );
				return new \WP_Error( 'lcpro_ai_api_error', $msg, array( 'status' => $status ) );
			}

			return isset( $data['choices'][0]['message']['content'] ) ? $data['choices'][0]['message']['content'] : '';
		}

		/**
		 * Send request to Anthropic Messages API.
		 *
		 * @param string $api_key API key.
		 * @param string $model   Model name.
		 * @param string $system  System prompt.
		 * @param string $user    User message.
		 * @return string|\WP_Error Raw response text or WP_Error.
		 */
		private static function request_anthropic( $api_key, $model, $system, $user ) {
			$url  = 'https://api.anthropic.com/v1/messages';
			$body = array(
				'model'      => self::resolve_model( 'anthropic', $model, 'claude-haiku-4-5-20251001' ),
				// Anthropic requires an explicit cap; the OpenAI-compatible providers don't.
				'max_tokens' => self::MAX_TOKENS,
				'system'     => $system,
				'messages'   => array(
					array(
						'role'    => 'user',
						'content' => $user,
					),
				),
			);

			$response = wp_remote_post(
				$url,
				array(
					'timeout' => self::TIMEOUT,
					'headers' => array(
						'x-api-key'         => $api_key,
						'anthropic-version' => '2023-06-01',
						'Content-Type'      => 'application/json',
					),
					'body'    => wp_json_encode( $body ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status = wp_remote_retrieve_response_code( $response );
			$data   = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 !== $status ) {
				$msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Anthropic API request failed.', 'logo-carousel-free' );
				return new \WP_Error( 'lcpro_ai_api_error', $msg, array( 'status' => $status ) );
			}

			return isset( $data['content'][0]['text'] ) ? $data['content'][0]['text'] : '';
		}

		/**
		 * Send request to Google Gemini API.
		 *
		 * @param string $api_key API key.
		 * @param string $model   Model name.
		 * @param string $system  System prompt.
		 * @param string $user    User message.
		 * @return string|\WP_Error Raw response text or WP_Error.
		 */
		private static function request_gemini( $api_key, $model, $system, $user ) {
			$selected_model = self::resolve_model( 'gemini', $model, 'gemini-3.6-flash' );
			$url            = 'https://generativelanguage.googleapis.com/v1beta/models/' . $selected_model . ':generateContent?key=' . rawurlencode( $api_key );

			$body = array(
				'systemInstruction' => array(
					'parts' => array( array( 'text' => $system ) ),
				),
				'contents'          => array(
					array(
						'parts' => array( array( 'text' => $user ) ),
					),
				),
				'generationConfig'  => array(
					'responseMimeType' => 'application/json',
				),
			);

			$response = wp_remote_post(
				$url,
				array(
					'timeout' => self::TIMEOUT,
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => wp_json_encode( $body ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status = wp_remote_retrieve_response_code( $response );
			$data   = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 !== $status ) {
				$msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Gemini API request failed.', 'logo-carousel-free' );
				return new \WP_Error( 'lcpro_ai_api_error', $msg, array( 'status' => $status ) );
			}

			return isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ? $data['candidates'][0]['content']['parts'][0]['text'] : '';
		}
	}
}
