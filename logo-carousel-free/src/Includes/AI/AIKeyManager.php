<?php
/**
 * AI Key Manager.
 *
 * Handles secure encryption, decryption, and storage of AI provider settings
 * and API keys.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

use ShapedPlugin\LogoCarouselFree\Includes\AI\Gateway\RemoteHttpGateway;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AIKeyManager' ) ) {

	/**
	 * Per-provider encrypted key storage plus the verification verdict for each.
	 *
	 * Stores one encrypted API key per provider (`api_keys[provider]`), not one
	 * shared key for whichever provider happens to be selected. Switching the
	 * provider dropdown without re-entering a key would otherwise silently pair
	 * the new provider with the previous provider's key — a guaranteed auth
	 * failure with no indication of the real cause. Per-provider storage also
	 * means switching back to a provider configured earlier doesn't force
	 * re-entering it.
	 */
	class AIKeyManager {

		/**
		 * Option key for AI settings.
		 */
		const OPTION_KEY = 'sp_lcpro_ai_settings';

		/**
		 * Recognized provider slugs, in the order the settings UI offers them.
		 *
		 * @var string[]
		 */
		const PROVIDERS = array( 'openai', 'anthropic', 'gemini', 'groq' );

		/**
		 * Recognized model ids per provider, mirroring the settings UI's
		 * PROVIDER_MODELS catalog (blocks/admin/pages/Settings/AISettingsTab.jsx).
		 * The first id in each list is that provider's recommended/default model.
		 *
		 * @var array<string, string[]>
		 */
		const PROVIDER_MODELS = array(
			'openai'    => array( 'gpt-5.6-terra', 'gpt-5.6-sol', 'gpt-5.6-luna' ),
			'anthropic' => array( 'claude-haiku-4-5-20251001', 'claude-sonnet-5' ),
			'gemini'    => array( 'gemini-3.6-flash', 'gemini-pro-latest' ),
			'groq'      => array( 'openai/gpt-oss-120b', 'openai/gpt-oss-20b', 'qwen/qwen3.8-27b' ),
		);

		/**
		 * Provider names as the settings screen and the AI builder show them.
		 *
		 * Brand names, so not translated.
		 *
		 * @var array<string, string>
		 */
		const PROVIDER_LABELS = array(
			'openai'    => 'OpenAI',
			'anthropic' => 'Anthropic',
			'gemini'    => 'Google Gemini',
			'groq'      => 'Groq',
		);

		/**
		 * Display names for the static model catalog, mirroring the settings UI's
		 * PROVIDER_MODELS labels (blocks/admin/pages/Settings/AISettingsTab.jsx).
		 *
		 * @var array<string, string>
		 */
		const MODEL_LABELS = array(
			'gpt-5.6-terra'             => 'GPT-5.6 Terra (Recommended)',
			'gpt-5.6-sol'               => 'GPT-5.6 Sol',
			'gpt-5.6-luna'              => 'GPT-5.6 Luna',
			'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (Recommended)',
			'claude-sonnet-5'           => 'Claude Sonnet 5',
			'gemini-3.6-flash'          => 'Gemini 3.6 Flash (Recommended)',
			'gemini-pro-latest'         => 'Gemini Pro',
			'openai/gpt-oss-120b'       => 'GPT-OSS 120B (Recommended)',
			'openai/gpt-oss-20b'        => 'GPT-OSS 20B',
			'qwen/qwen3.8-27b'          => 'Qwen3.8 27B',
		);

		/**
		 * Default provider when none is stored.
		 */
		const DEFAULT_PROVIDER = 'gemini';

		/**
		 * Default model when none is stored.
		 */
		const DEFAULT_MODEL = 'gemini-3.6-flash';

		/**
		 * How long a verification verdict is trusted before the key is re-checked.
		 *
		 * Long enough that opening the settings screen is not a stream of
		 * provider requests, short enough that a key revoked upstream stops being
		 * advertised as working within the day. A verdict is also discarded the
		 * moment the key it described changes, regardless of age.
		 */
		const STATUS_TTL = 12 * HOUR_IN_SECONDS;

		/**
		 * Load the stored option, migrating the legacy single-key shape
		 * (`api_key`, one key for whichever provider was last selected) into the
		 * per-provider shape (`api_keys[provider]`) the first time it's read
		 * after upgrading. Runs once: after migration `api_key` no longer exists.
		 *
		 * @return array<string, mixed>
		 */
		private static function get_raw_settings() {
			$opts = get_option( self::OPTION_KEY, array() );
			if ( ! is_array( $opts ) ) {
				$opts = array();
			}

			if ( array_key_exists( 'api_key', $opts ) ) {
				$provider = isset( $opts['provider'] ) ? sanitize_key( $opts['provider'] ) : self::DEFAULT_PROVIDER;
				$api_keys = isset( $opts['api_keys'] ) && is_array( $opts['api_keys'] ) ? $opts['api_keys'] : array();

				if ( ! empty( $opts['api_key'] ) && empty( $api_keys[ $provider ] ) ) {
					$api_keys[ $provider ] = $opts['api_key'];
				}

				unset( $opts['api_key'] );
				$opts['api_keys'] = $api_keys;
				update_option( self::OPTION_KEY, $opts );
			}

			if ( ! isset( $opts['api_keys'] ) || ! is_array( $opts['api_keys'] ) ) {
				$opts['api_keys'] = array();
			}

			if ( ! isset( $opts['key_status'] ) || ! is_array( $opts['key_status'] ) ) {
				$opts['key_status'] = array();
			}

			return $opts;
		}

		/**
		 * Save AI settings: provider, model, and the API key for that one provider.
		 *
		 * @param array $settings Input settings: provider, model, api_key.
		 * @return bool True on success, false on failure.
		 */
		public static function save_settings( array $settings ) {
			$result = self::save( $settings );

			return $result['saved'];
		}

		/**
		 * Save one provider's API key and report what happened to it.
		 *
		 * `provider` names the provider being edited — its key is the only one
		 * this touches. It does not make that provider the Primary: the Primary
		 * changes only on `make_primary`, or when the stored one stops being
		 * ready (see resolve_primary()), so the first key saved becomes the
		 * Primary and removing the Primary's key promotes the next ready one.
		 *
		 * A key is verified with its provider before it is stored, and a key the
		 * provider rejects is not stored at all: the previously saved key stays
		 * exactly as it was. Overwriting a working credential with a typo, and
		 * then reporting it as "Configured", is the failure this guards against.
		 *
		 * The model is not chosen on the settings screen — the AI builder picks
		 * one per request. The stored model follows the Primary: kept while the
		 * Primary is unchanged, the new Primary's recommended model otherwise.
		 *
		 * @param array $settings Input: provider, api_key, remove_key, make_primary, model (optional).
		 * @return array<string, mixed> Outcome: saved, provider (edited), primary, key.
		 */
		public static function save( array $settings ) {
			$edited = isset( $settings['provider'] ) ? sanitize_key( $settings['provider'] ) : self::DEFAULT_PROVIDER;
			if ( ! in_array( $edited, self::PROVIDERS, true ) ) {
				$edited = self::DEFAULT_PROVIDER;
			}

			$existing   = self::get_raw_settings();
			$api_keys   = $existing['api_keys'];
			$key_status = $existing['key_status'];
			$stored     = self::get_settings();

			$outcome = array(
				'state'   => 'unchanged',
				'message' => '',
				'stored'  => false,
			);

			// A masked placeholder (the UI echoes back "abcd••••••••••••wxyz" for
			// a key it never lets JS see in full) means "leave this provider's
			// stored key alone" — only a real, freshly typed key overwrites it.
			// Any mask bullet marks one: no provider issues keys containing
			// that character, and it also catches the older "••••••••<last 4>"
			// mask from a settings tab left open across an update.
			//
			// `remove_key` is the explicit "delete this provider's key" — an
			// empty field on its own never removes anything.
			$submitted = isset( $settings['api_key'] ) && is_string( $settings['api_key'] ) ? $settings['api_key'] : '';

			if ( ! empty( $settings['remove_key'] ) ) {
				unset( $api_keys[ $edited ], $key_status[ $edited ] );
				$outcome['state'] = 'removed';
			} elseif ( '' !== $submitted && ! self::is_masked( $submitted ) ) {
				$outcome = self::store_submitted_key( $edited, $submitted, $api_keys, $key_status );
			}

			$after = array(
				'api_keys'   => $api_keys,
				'key_status' => $key_status,
			);
			$ready = self::ready_providers( $after );

			$stored_primary = $stored['provider'];
			if ( ! empty( $settings['make_primary'] ) && in_array( $edited, $ready, true ) ) {
				$stored_primary = $edited;
			}
			$primary = self::resolve_primary( $stored_primary, $ready, $edited );

			$saved = update_option(
				self::OPTION_KEY,
				array(
					'provider'   => $primary,
					'model'      => self::model_for_primary( $primary, $stored, $edited, $settings ),
					'api_keys'   => $api_keys,
					'key_status' => $key_status,
				)
			);

			return array(
				'saved'    => $saved,
				'provider' => $edited,
				'primary'  => $primary,
				'key'      => $outcome,
			);
		}

		/**
		 * The provider the AI builder tries first.
		 *
		 * The stored Primary while it can answer; otherwise the provider just
		 * edited, if it can; otherwise the first one that can, in settings
		 * order. With nothing ready the stored choice stands, so it is back in
		 * charge the moment it gets a key.
		 *
		 * Pure — every input is already resolved.
		 *
		 * @param string   $stored    Stored Primary.
		 * @param string[] $ready     Providers that can answer, in settings order.
		 * @param string   $candidate Provider to prefer when the stored one cannot answer.
		 * @return string Provider slug.
		 */
		public static function resolve_primary( $stored, array $ready, $candidate = '' ) {
			if ( in_array( $stored, $ready, true ) ) {
				return $stored;
			}

			if ( '' !== $candidate && in_array( $candidate, $ready, true ) ) {
				return $candidate;
			}

			if ( ! empty( $ready ) ) {
				return (string) reset( $ready );
			}

			return in_array( $stored, self::PROVIDERS, true ) ? $stored : self::DEFAULT_PROVIDER;
		}

		/**
		 * The Primary as the site stands now.
		 *
		 * @return string Provider slug.
		 */
		public static function primary_provider() {
			$settings = self::get_settings();

			return self::resolve_primary( $settings['provider'], self::ready_providers( self::get_raw_settings() ) );
		}

		/**
		 * Providers that can answer a request right now, in settings order.
		 *
		 * Ready means a WordPress Connectors connection, or a stored key its
		 * provider has not refused. Read from cache only — this runs on page
		 * loads and saves, never worth a provider round trip.
		 *
		 * @param array $opts Raw settings (api_keys + key_status).
		 * @return string[] Provider slugs.
		 */
		private static function ready_providers( array $opts ) {
			$opts  = array_merge(
				array(
					'api_keys'   => array(),
					'key_status' => array(),
				),
				$opts
			);
			$ready = array();

			foreach ( self::PROVIDERS as $slug ) {
				$status  = self::read_status( $slug, $opts );
				$has_key = AIKeyValidator::STATE_NONE !== $status['state'] && AIKeyValidator::STATE_INVALID !== $status['state'];

				if ( $has_key || ConnectorBridge::is_connected( $slug, false ) ) {
					$ready[] = $slug;
				}
			}

			return $ready;
		}

		/**
		 * The model to store for the Primary.
		 *
		 * A model submitted for the Primary itself is validated as before; with
		 * none, an unchanged Primary keeps its model and a new one starts on its
		 * recommended model — the old Primary's model belongs to another
		 * provider.
		 *
		 * @param string $primary  Primary after this save.
		 * @param array  $stored   Settings before this save.
		 * @param string $edited   Provider this save edited.
		 * @param array  $settings Submitted input.
		 * @return string Model id.
		 */
		private static function model_for_primary( $primary, array $stored, $edited, array $settings ) {
			$allowed_models = self::allowed_models( $primary );
			$model          = ( $edited === $primary && isset( $settings['model'] ) ) ? sanitize_text_field( $settings['model'] ) : '';

			if ( '' === $model && $primary === $stored['provider'] ) {
				$model = $stored['model'];
			}

			// A stored id may be one RemoteHttpGateway::DEPRECATED_MODEL_MAP
			// already knows how to remap — canonicalize it before falling back
			// to the provider default, so re-saving an unrelated field doesn't
			// silently swap the model out from under the user.
			if ( ! in_array( $model, $allowed_models, true ) ) {
				$remap = isset( RemoteHttpGateway::DEPRECATED_MODEL_MAP[ $primary ] )
					? RemoteHttpGateway::DEPRECATED_MODEL_MAP[ $primary ]
					: array();
				$model = isset( $remap[ $model ] ) ? $remap[ $model ] : $model;
			}

			// A model still not recognized, but already stored for this same
			// Primary, is left alone: it was valid when chosen, and a connector's
			// model list being momentarily unavailable must not rewrite it.
			if ( '' !== $stored['model'] && $model === $stored['model'] && $primary === $stored['provider'] ) {
				$allowed_models[] = $stored['model'];
			}

			if ( ! in_array( $model, $allowed_models, true ) ) {
				$model = isset( $allowed_models[0] ) ? $allowed_models[0] : '';
			}

			return $model;
		}

		/**
		 * Verify a freshly typed key and, if the provider accepts it, store it.
		 *
		 * A provider that cannot be reached leaves the key stored but unverified
		 * rather than refused: a site behind an outage or a firewall must still be
		 * able to configure a credential, and "not verified" is an honest label
		 * for one nobody has confirmed yet.
		 *
		 * @param string $provider   Provider slug.
		 * @param string $submitted  Raw key exactly as submitted.
		 * @param array  $api_keys   Encrypted keys, modified in place.
		 * @param array  $key_status Verdicts, modified in place.
		 * @return array<string, mixed> Outcome for this key.
		 */
		private static function store_submitted_key( $provider, $submitted, array &$api_keys, array &$key_status ) {
			$api_key = sanitize_text_field( $submitted );

			// sanitize_text_field() trims, so a field holding only spaces arrives
			// here empty. That is not "leave the key alone" — the user typed
			// something, and it cannot become a credential.
			if ( '' === $api_key ) {
				return array(
					'state'   => AIKeyValidator::STATE_EMPTY,
					'message' => __( 'Enter an API key — the field contains only spaces.', 'logo-carousel-free' ),
					'stored'  => false,
				);
			}

			$verdict = AIKeyValidator::validate( $provider, $api_key );

			if ( AIKeyValidator::STATE_INVALID === $verdict['state'] || AIKeyValidator::STATE_EMPTY === $verdict['state'] ) {
				return array(
					'state'   => $verdict['state'],
					'message' => $verdict['message'],
					'stored'  => false,
				);
			}

			$encrypted = self::encrypt( $api_key );
			if ( '' === $encrypted ) {
				return array(
					'state'   => 'failed',
					'message' => __( 'The API key could not be encrypted for storage. Please try saving again.', 'logo-carousel-free' ),
					'stored'  => false,
				);
			}

			$api_keys[ $provider ]   = $encrypted;
			$key_status[ $provider ] = self::status_record( $api_key, $verdict );

			return array(
				'state'   => $verdict['state'],
				'message' => $verdict['message'],
				'stored'  => true,
			);
		}

		/**
		 * Shape one stored verdict.
		 *
		 * The fingerprint ties a verdict to the exact key it describes, so a
		 * verdict can never outlive its key — a key replaced through any path
		 * (import, WP-CLI, a direct option write) leaves a fingerprint that no
		 * longer matches and the stale verdict is discarded.
		 *
		 * @param string $api_key Plaintext key the verdict is about.
		 * @param array  $verdict Verdict from AIKeyValidator.
		 * @return array<string, mixed>
		 */
		private static function status_record( $api_key, array $verdict ) {
			return array(
				'state'       => $verdict['state'],
				'message'     => $verdict['message'],
				'checked_at'  => time(),
				'fingerprint' => self::fingerprint( $api_key ),
			);
		}

		/**
		 * Salted, non-reversible identifier for a key.
		 *
		 * @param string $api_key Plaintext key.
		 * @return string Fingerprint.
		 */
		private static function fingerprint( $api_key ) {
			return substr( wp_hash( $api_key ), 0, 16 );
		}

		/**
		 * Model ids a provider may be saved with.
		 *
		 * A provider reached through Connectors is not limited to this plugin's
		 * hardcoded catalog — the AI client registry reports what that provider
		 * actually serves, which is both wider and more current. The static
		 * catalog stays allowed alongside it, so disconnecting a connector never
		 * invalidates a model the user picked earlier.
		 *
		 * The connector half is read from cache only. Validating a submitted
		 * value against a live probe would mean a provider that fails to answer
		 * between rendering the form and submitting it silently rewrites the
		 * user's model.
		 *
		 * @param string $provider Provider slug.
		 * @return string[] Allowed model ids, this plugin's recommended one first.
		 */
		public static function allowed_models( $provider ) {
			$catalog = isset( self::PROVIDER_MODELS[ $provider ] ) ? self::PROVIDER_MODELS[ $provider ] : array();

			return array_values( array_unique( array_merge( $catalog, ConnectorBridge::get_cached_model_ids( $provider ) ) ) );
		}

		/**
		 * Get the decrypted API key for a provider.
		 *
		 * @param string $provider Provider slug, or empty for the configured provider.
		 * @return string Decrypted API key or empty string.
		 */
		public static function get_decrypted_key( $provider = '' ) {
			$opts     = self::get_raw_settings();
			$provider = '' !== $provider ? sanitize_key( $provider ) : (string) ( isset( $opts['provider'] ) ? $opts['provider'] : self::DEFAULT_PROVIDER );

			if ( empty( $opts['api_keys'][ $provider ] ) ) {
				return '';
			}

			return self::decrypt( (string) $opts['api_keys'][ $provider ] );
		}

		/**
		 * Current verdict on a provider's stored key, without contacting anyone.
		 *
		 * @param string $provider Provider slug.
		 * @return array<string, mixed> Verdict.
		 */
		public static function get_key_status( $provider ) {
			return self::read_status( $provider, self::get_raw_settings() );
		}

		/**
		 * Verify a provider's stored key and remember the answer.
		 *
		 * Re-uses a fresh verdict by default so opening the settings screen does
		 * not become a round of provider requests; `$force` is for the user
		 * explicitly asking for a re-check.
		 *
		 * @param string $provider Provider slug.
		 * @param bool   $force    Ignore a cached verdict and ask the provider again.
		 * @return array<string, mixed> Verdict.
		 */
		public static function verify_stored_key( $provider, $force = false ) {
			$provider = sanitize_key( $provider );
			$opts     = self::get_raw_settings();
			$current  = self::read_status( $provider, $opts );

			if ( AIKeyValidator::STATE_NONE === $current['state'] ) {
				return $current;
			}

			if ( ! $force && AIKeyValidator::STATE_UNKNOWN !== $current['state'] ) {
				return $current;
			}

			$api_key = self::get_decrypted_key( $provider );

			// Something is stored but no longer decrypts: this site's salts
			// changed since it was saved (a moved database, rotated
			// wp-config.php keys). That key can never be read again, so it gets a
			// verdict of its own rather than staying "unknown" — an unknown that
			// no re-check can resolve is a screen that asks forever.
			$verdict = '' === $api_key
				? array(
					'state'   => AIKeyValidator::STATE_INVALID,
					'message' => __( 'The stored API key can no longer be read — this site\'s security keys changed since it was saved. Paste the key again.', 'logo-carousel-free' ),
				)
				: AIKeyValidator::validate( $provider, $api_key );

			$record = self::status_record( $api_key, $verdict );

			$opts['key_status'][ $provider ] = $record;
			update_option( self::OPTION_KEY, $opts );

			return self::public_status( $record );
		}

		/**
		 * Record that a provider refused the stored key during a real request.
		 *
		 * A key can be revoked, rotated or hit an expired billing account long
		 * after it was verified. The screen that reports it must not keep
		 * claiming the key works simply because it did when it was saved.
		 *
		 * @param string $provider Provider slug.
		 * @param string $message  Provider's own explanation.
		 * @return void
		 */
		public static function mark_key_invalid( $provider, $message ) {
			$provider = sanitize_key( $provider );
			$api_key  = self::get_decrypted_key( $provider );

			if ( '' === $api_key ) {
				return;
			}

			$opts                            = self::get_raw_settings();
			$opts['key_status'][ $provider ] = self::status_record(
				$api_key,
				array(
					'state'   => AIKeyValidator::STATE_INVALID,
					'message' => AIKeyValidator::rejection_message( $provider, sanitize_text_field( $message ) ),
				)
			);

			update_option( self::OPTION_KEY, $opts );
		}

		/**
		 * Read a provider's stored verdict out of an already-loaded option.
		 *
		 * @param string $provider Provider slug.
		 * @param array  $opts     Raw settings.
		 * @return array<string, mixed>
		 */
		private static function read_status( $provider, array $opts ) {
			$empty = array(
				'state'      => AIKeyValidator::STATE_NONE,
				'message'    => '',
				'checked_at' => 0,
			);

			if ( empty( $opts['api_keys'][ $provider ] ) ) {
				return $empty;
			}

			$unknown = array(
				'state'      => AIKeyValidator::STATE_UNKNOWN,
				'message'    => '',
				'checked_at' => 0,
			);

			$record = isset( $opts['key_status'][ $provider ] ) ? $opts['key_status'][ $provider ] : null;
			if ( ! is_array( $record ) || empty( $record['state'] ) ) {
				return $unknown;
			}

			// A verdict older than the TTL, or one describing a different key than
			// the one now stored, says nothing about what is stored today.
			$checked_at = isset( $record['checked_at'] ) ? (int) $record['checked_at'] : 0;
			if ( $checked_at < ( time() - self::STATUS_TTL ) ) {
				return $unknown;
			}

			$fingerprint = isset( $record['fingerprint'] ) ? (string) $record['fingerprint'] : '';
			if ( '' === $fingerprint || self::fingerprint( self::decrypt( (string) $opts['api_keys'][ $provider ] ) ) !== $fingerprint ) {
				return $unknown;
			}

			return self::public_status( $record );
		}

		/**
		 * Reduce a stored verdict to the fields other layers may see.
		 *
		 * @param array $record Stored verdict.
		 * @return array<string, mixed>
		 */
		private static function public_status( array $record ) {
			return array(
				'state'      => (string) $record['state'],
				'message'    => isset( $record['message'] ) ? (string) $record['message'] : '',
				'checked_at' => isset( $record['checked_at'] ) ? (int) $record['checked_at'] : 0,
			);
		}

		/**
		 * Get stored AI settings (provider + model only — keys never leave this
		 * class unmasked).
		 *
		 * @return array<string, string> Saved settings.
		 */
		public static function get_settings() {
			$opts = self::get_raw_settings();

			return array(
				'provider' => isset( $opts['provider'] ) ? (string) $opts['provider'] : self::DEFAULT_PROVIDER,
				'model'    => isset( $opts['model'] ) ? (string) $opts['model'] : self::DEFAULT_MODEL,
			);
		}

		/**
		 * Get public-safe AI settings for JS localization.
		 *
		 * `has_key` answers the one question the block editor's AI builder asks:
		 * can a request be made right now. That is true for a provider configured
		 * in WordPress Connectors even though this plugin stores no key for it,
		 * and false for a key the provider is known to reject.
		 *
		 * `keys` reports this plugin's own storage per provider, so the settings
		 * screen can show the right masked value the instant the provider
		 * dropdown changes, with no round trip. `connectors` reports the same
		 * providers' state in WordPress Connectors, which is what decides whether
		 * the key field is shown at all.
		 *
		 * Only the settings screen needs an authoritative connector answer, and
		 * only it needs the model list. The block editor's payload is built on
		 * requests that can be REST requests, so it settles for the free answer
		 * rather than letting a page load reach out to a provider.
		 *
		 * @param bool $probe_connectors Whether to verify connector state and read model lists, at the cost of an HTTP request on a cold cache.
		 * @return array<string, mixed> Public settings.
		 */
		public static function get_public_settings( $probe_connectors = false ) {
			$settings = self::get_settings();

			// Report the Primary as it resolves today: a stored Primary whose key
			// was removed or refused hands over to the next ready provider, and
			// the model it carries belongs to the old one.
			$provider = self::primary_provider();
			if ( $provider !== $settings['provider'] ) {
				$allowed  = self::allowed_models( $provider );
				$settings = array(
					'provider' => $provider,
					'model'    => isset( $allowed[0] ) ? $allowed[0] : '',
				);
			}

			// No key is verified here. This runs while an admin page renders, and
			// a provider that hangs would hold the whole screen for the
			// validator's timeout. A key with no fresh verdict is reported as
			// `unknown`, which the settings screen resolves through
			// lcpro_ai_verify_key without blocking anything — and which no screen
			// renders as "Configured".
			$opts = self::get_raw_settings();
			$keys = array();
			foreach ( self::PROVIDERS as $slug ) {
				$decrypted     = self::get_decrypted_key( $slug );
				$status        = self::read_status( $slug, $opts );
				$keys[ $slug ] = array(
					'has_key'    => '' !== $decrypted,
					'masked_key' => self::mask( $decrypted ),
					'status'     => $status['state'],
					'message'    => $status['message'],
					'checked_at' => $status['checked_at'],
				);
			}

			$current = isset( $keys[ $provider ] ) ? $keys[ $provider ] : array(
				'has_key'    => false,
				'masked_key' => '',
				'status'     => AIKeyValidator::STATE_NONE,
				'message'    => '',
			);

			$connectors = ConnectorBridge::get_public_statuses( self::PROVIDERS, $probe_connectors );

			// Only connected providers report models: a disconnected one has
			// nothing to say, and the settings screen falls back to its static
			// catalog.
			$connector_models = array();
			if ( $probe_connectors ) {
				foreach ( self::PROVIDERS as $slug ) {
					$models = ConnectorBridge::get_models( $slug );
					if ( ! empty( $models ) ) {
						$connector_models[ $slug ] = $models;
					}
				}
			}

			$usable_key = $current['has_key'] && AIKeyValidator::STATE_INVALID !== $current['status'];

			// What the AI builder offers in its provider picker. A connected
			// provider's models come from what the settings screen last cached,
			// never from a live probe: this runs while the editor loads.
			$models_by_provider = array();
			foreach ( self::PROVIDERS as $slug ) {
				$models_by_provider[ $slug ] = isset( $connector_models[ $slug ] )
					? $connector_models[ $slug ]
					: ConnectorBridge::get_cached_models( $slug );
			}

			return array(
				'provider'         => $provider,
				'primary'          => $provider,
				'model'            => $settings['model'],
				'providers'        => self::build_available_providers( $keys, $connectors, $models_by_provider, $settings ),
				'has_key'          => $usable_key || ! empty( $connectors[ $provider ]['connected'] ),
				'key_status'       => $current['status'],
				'key_message'      => isset( $current['message'] ) ? $current['message'] : '',
				'masked_key'       => $current['masked_key'],
				'keys'             => $keys,
				'connectors'       => $connectors,
				'connector_models' => $connector_models,
				'connectors_url'   => ConnectorBridge::settings_url(),
				'ai_enabled'       => AIFeature::is_available(),
			);
		}

		/**
		 * Providers the AI builder can send a request to right now.
		 *
		 * A provider qualifies with a key stored here that its provider has not
		 * refused, or with a connection in WordPress Connectors. With more than
		 * one, the editor offers the choice. The provider selected in Settings
		 * is the default: listed first and flagged `isDefault`, so the editor
		 * opens on it; the rest follow in settings-screen order.
		 *
		 * Pure: every input is already resolved, so this reads no option and
		 * makes no request.
		 *
		 * @param array $keys               Provider → key info, as get_public_settings() builds it.
		 * @param array $connectors         Provider → connector state, from ConnectorBridge::get_public_statuses().
		 * @param array $models_by_provider Provider → [{value,label}] reported by a connector, possibly empty.
		 * @param array $settings           Saved provider and model.
		 * @return array<int, array<string, mixed>> Providers, the default first.
		 */
		public static function build_available_providers( array $keys, array $connectors, array $models_by_provider, array $settings ) {
			$saved_provider = isset( $settings['provider'] ) ? (string) $settings['provider'] : self::DEFAULT_PROVIDER;
			$saved_model    = isset( $settings['model'] ) ? (string) $settings['model'] : '';
			$providers      = array();
			$default_entry  = null;

			foreach ( self::PROVIDERS as $slug ) {
				$key          = isset( $keys[ $slug ] ) && is_array( $keys[ $slug ] ) ? $keys[ $slug ] : array();
				$is_connected = ! empty( $connectors[ $slug ]['connected'] );
				$has_key      = ! empty( $key['has_key'] )
					&& ( ! isset( $key['status'] ) || AIKeyValidator::STATE_INVALID !== $key['status'] );

				if ( ! $is_connected && ! $has_key ) {
					continue;
				}

				// A connected provider's own list wins: the registry cannot offer
				// a model it has retired. The static catalog is the fallback.
				$reported = $is_connected && ! empty( $models_by_provider[ $slug ] ) && is_array( $models_by_provider[ $slug ] )
					? $models_by_provider[ $slug ]
					: array();
				$models   = ! empty( $reported ) ? array_values( $reported ) : self::catalog_models( $slug );
				$ids      = array_column( $models, 'value' );

				$recommended = isset( self::PROVIDER_MODELS[ $slug ][0] ) ? self::PROVIDER_MODELS[ $slug ][0] : '';
				$default     = in_array( $recommended, $ids, true ) ? $recommended : ( isset( $ids[0] ) ? (string) $ids[0] : '' );

				// The saved model belongs to the saved provider. Offer it even when
				// the list no longer carries it, so the editor never silently
				// swaps the model the settings screen shows as chosen.
				if ( $slug === $saved_provider && '' !== $saved_model ) {
					if ( ! in_array( $saved_model, $ids, true ) ) {
						array_unshift(
							$models,
							array(
								'value' => $saved_model,
								'label' => isset( self::MODEL_LABELS[ $saved_model ] ) ? self::MODEL_LABELS[ $saved_model ] : $saved_model,
							)
						);
					}
					$default = $saved_model;
				}

				$entry = array(
					'value'        => $slug,
					'label'        => self::PROVIDER_LABELS[ $slug ],
					'source'       => $is_connected ? 'connector' : 'key',
					'models'       => $models,
					'defaultModel' => $default,
					'isDefault'    => $slug === $saved_provider,
				);

				if ( $entry['isDefault'] ) {
					$default_entry = $entry;
				} else {
					$providers[] = $entry;
				}
			}

			if ( null !== $default_entry ) {
				array_unshift( $providers, $default_entry );
			}

			return $providers;
		}

		/**
		 * The static model catalog for a provider, as select options.
		 *
		 * @param string $provider Provider slug.
		 * @return array<int, array<string, string>> Model options.
		 */
		private static function catalog_models( $provider ) {
			$ids = isset( self::PROVIDER_MODELS[ $provider ] ) ? self::PROVIDER_MODELS[ $provider ] : array();

			return array_map(
				static function ( $id ) {
					return array(
						'value' => $id,
						'label' => isset( self::MODEL_LABELS[ $id ] ) ? self::MODEL_LABELS[ $id ] : $id,
					);
				},
				$ids
			);
		}

		/**
		 * The character a masked key is padded with.
		 */
		const MASK_CHAR = '•';

		/**
		 * Mask a decrypted key for display: first 4 characters, 12 bullets,
		 * last 4 — the same shape as the license key on the License screen.
		 *
		 * Enough to recognise which key is stored, never enough to use it. A key
		 * of 8 characters or fewer would be shown whole by that shape, so it is
		 * masked entirely.
		 *
		 * @param string $decrypted Decrypted API key, or empty.
		 * @return string Masked key, or empty string when there is nothing to mask.
		 */
		public static function mask( $decrypted ) {
			$decrypted = trim( (string) $decrypted );
			if ( '' === $decrypted ) {
				return '';
			}

			$length = strlen( $decrypted );
			if ( $length <= 8 ) {
				return str_repeat( self::MASK_CHAR, $length );
			}

			return substr( $decrypted, 0, 4 ) . str_repeat( self::MASK_CHAR, 12 ) . substr( $decrypted, -4 );
		}

		/**
		 * Whether a submitted value is a masked placeholder rather than a key.
		 *
		 * @param string $value Submitted value.
		 * @return bool True when it carries a mask bullet.
		 */
		public static function is_masked( $value ) {
			return false !== strpos( (string) $value, self::MASK_CHAR );
		}

		/**
		 * Encrypt a string using OpenSSL with the site's auth salt.
		 *
		 * @param string $value Plaintext value.
		 * @return string Base64 encoded IV + encrypted data.
		 */
		private static function encrypt( $value ) {
			if ( empty( $value ) ) {
				return '';
			}

			$key = wp_salt( 'auth' );
			$iv  = openssl_random_pseudo_bytes( 16 );
			$enc = openssl_encrypt( $value, 'aes-256-cbc', $key, 0, $iv );

			if ( false === $enc ) {
				return '';
			}

			return base64_encode( $iv . $enc ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary ciphertext, not obfuscation.
		}

		/**
		 * Decrypt a string using OpenSSL with the site's auth salt.
		 *
		 * @param string $raw Base64 payload.
		 * @return string Decrypted plaintext value.
		 */
		private static function decrypt( $raw ) {
			if ( empty( $raw ) ) {
				return '';
			}

			$data = base64_decode( $raw, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- binary ciphertext, not obfuscation.
			if ( false === $data || strlen( $data ) < 17 ) {
				return '';
			}

			$iv  = substr( $data, 0, 16 );
			$enc = substr( $data, 16 );
			$key = wp_salt( 'auth' );
			$dec = openssl_decrypt( $enc, 'aes-256-cbc', $key, 0, $iv );

			return false !== $dec ? $dec : '';
		}
	}
}
