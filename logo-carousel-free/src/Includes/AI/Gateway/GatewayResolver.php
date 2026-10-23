<?php
/**
 * Gateway Resolver.
 *
 * Decides how a completion request reaches its provider: through WordPress
 * Connectors when the site owner already configured one there, otherwise
 * through this plugin's own stored key.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI\Gateway;

use ShapedPlugin\LogoCarouselFree\Includes\AI\AIFeature;
use ShapedPlugin\LogoCarouselFree\Includes\AI\AIKeyManager;
use ShapedPlugin\LogoCarouselFree\Includes\AI\ConnectorBridge;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\Gateway\GatewayResolver' ) ) {

	/**
	 * Picks the transport for one completion request.
	 */
	class GatewayResolver {

		/**
		 * Pick the gateway for a provider.
		 *
		 * Connectors win whenever they can actually serve the request. A key
		 * stored by this plugin is never deleted or migrated because of that —
		 * it stays as the fallback for the day the provider plugin is
		 * deactivated, the connector key is revoked, or the site moves to a
		 * WordPress without Connectors.
		 *
		 * The AI builder may name a provider and model of its own when more than
		 * one provider is set up. A provider this plugin does not know falls back
		 * to the saved one, and a model the provider is not known to offer is
		 * dropped rather than sent upstream to fail.
		 *
		 * @param string $provider Provider slug, or empty for the configured provider.
		 * @param string $model    Model id, or empty for the provider's saved or default model.
		 * @return TextGateway|\WP_Error Gateway, or WP_Error when nothing can serve the request.
		 */
		public static function resolve( $provider = '', $model = '' ) {
			// Last line of defence: no completion leaves the site while the
			// module is off, however the caller got here.
			if ( ! AIFeature::is_available() ) {
				return AIFeature::unavailable_error();
			}

			$settings = AIKeyManager::get_settings();
			$provider = sanitize_key( (string) $provider );
			if ( ! in_array( $provider, AIKeyManager::PROVIDERS, true ) ) {
				$provider = AIKeyManager::primary_provider();
			}

			$model = (string) $model;
			if ( '' !== $model && ! in_array( $model, AIKeyManager::allowed_models( $provider ), true ) ) {
				$model = '';
			}

			// The stored model belongs to the stored provider. Resolving a
			// different provider means there is no model choice to carry over.
			if ( '' === $model && $provider === $settings['provider'] ) {
				$model = $settings['model'];
			}

			if ( ConnectorBridge::is_connected( $provider ) ) {
				return new ConnectorGateway( $provider, $model );
			}

			$api_key = AIKeyManager::get_decrypted_key( $provider );
			if ( '' === $api_key ) {
				return new \WP_Error( 'lcpro_ai_missing_key', self::missing_key_message( $provider ), array( 'status' => 400 ) );
			}

			return new RemoteHttpGateway( $provider, $model, $api_key );
		}

		/**
		 * Explain a missing credential in terms of where the user can supply one.
		 *
		 * Pointing at this plugin's settings when WordPress already offers a
		 * connector for the provider sends the user to the wrong screen, and sets
		 * up exactly the duplicate key entry the connector integration exists to
		 * avoid.
		 *
		 * @param string $provider Provider slug.
		 * @return string Message.
		 */
		private static function missing_key_message( $provider ) {
			if ( ConnectorBridge::is_registered( $provider ) ) {
				return __( 'This AI provider has no API key yet. Connect it under Settings > Connectors, or add a key in Logo Carousel Settings.', 'logo-carousel-free' );
			}

			return __( 'AI API key is missing. Please configure your API key in Logo Carousel Settings.', 'logo-carousel-free' );
		}
	}
}
