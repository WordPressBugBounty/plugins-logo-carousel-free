<?php
/**
 * Ready Patterns source resolution, caching, and payload lookup.
 *
 * @package ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns;

defined( 'ABSPATH' ) || exit;

/**
 * Loads manifests and single-pattern payloads from the pattern server, cached in
 * uploads. Nothing ships inside the plugin: an unreachable server means an empty
 * library, not a stale one.
 */
class PatternRepository {

	/**
	 * Default remote manifest URL (Logo Carousel pattern server).
	 */
	private const REMOTE_MANIFEST_URL = 'https://demo.logocarousel.com/wp-json/logo-carousel/v1/pattern-list/';

	/**
	 * Manifest cache TTL in seconds (24 hours).
	 */
	private const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Single-payload cache TTL in seconds. Short on purpose: patterns are edited
	 * server-side and a stale payload is worse than a slow insert.
	 */
	private const PAYLOAD_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * Transient cache key.
	 *
	 * Distinct from Pro's `sp_lcpro_ready_patterns_manifest`: the cache holds
	 * NORMALIZED items, and the free plugin's normalized shape carries the
	 * `requiresPro` flag. Reading a manifest Pro cached (same site, Pro since
	 * deactivated) would list Pro-only patterns as insertable.
	 */
	private const TRANSIENT_KEY = 'sp_lcpro_ready_patterns_manifest_free';

	/**
	 * Shape version of the cached manifest. Bump when the normalized item shape
	 * changes, so a cache written by an older build is refetched, not served.
	 */
	private const CACHE_SCHEMA = 2;

	/**
	 * Per-payload transient key prefix.
	 */
	private const PAYLOAD_TRANSIENT_PREFIX = 'sp_lcpro_rp_payload_';

	/**
	 * Option holding the payload cache generation. Payload keys are salted with it,
	 * so bumping the number retires every cached payload at once — transient names
	 * are hashed per pattern ID and cannot be enumerated for deletion.
	 */
	private const PAYLOAD_GENERATION_OPTION = 'sp_lcpro_rp_payload_generation';

	/**
	 * Uploads sub-directory for cache files.
	 */
	private const UPLOADS_SUBDIR = 'logo-carousel-pro';

	/**
	 * Cache filename inside the uploads sub-directory (free-specific for the
	 * same reason as TRANSIENT_KEY).
	 */
	private const CACHE_FILENAME = 'ready-patterns-free.json';

	/**
	 * Hard ceiling on a single pattern payload. Bounds what a buggy or compromised
	 * server can hand to `parse()` in an editor session.
	 */
	public const MAX_PAYLOAD_BYTES = 262144;

	/**
	 * Return the normalized flat manifest.
	 *
	 * @param bool $force When true, bypass cache and revalidate.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_manifest( bool $force = false ): array {
		// Remote fetching switched off: there is no local catalog to fall back to, and a
		// cache written before the filter was added must not resurrect remote items.
		if ( '' === $this->get_remote_manifest_url() ) {
			return array();
		}

		$cached = $this->read_cache();

		// Serve a non-empty, unexpired cache. An empty normalized list must not
		// stick for the full TTL — authoring often fills the remote catalog after
		// an empty poll was cached, and the editor would show "no patterns" all day.
		if (
			! $force
			&& null !== $cached
			&& isset( $cached['normalized'] )
			&& is_array( $cached['normalized'] )
			&& array() !== $cached['normalized']
		) {
			return $cached['normalized'];
		}

		if ( $force ) {
			// Refresh has to reach the payloads too: a pattern edited server-side keeps
			// inserting its old markup for the rest of the payload TTL otherwise.
			$this->bump_payload_generation();
		}

		$fetched = $this->fetch_source( $cached );

		if ( 'not_modified' === $fetched['status'] && null !== $cached && isset( $cached['normalized'] ) && is_array( $cached['normalized'] ) ) {
			// Nothing changed upstream: extend the expiry and reuse the stored items.
			$cached['expires'] = time() + self::CACHE_TTL;
			$this->write_cache( $cached );

			return $cached['normalized'];
		}

		$source     = $fetched['data'];
		$normalized = Normalizer::normalize( $source );

		// An unreadable/failed source must not poison the cache for a full TTL.
		if ( is_array( $source ) ) {
			$this->write_cache(
				array(
					'grouped'       => $source,
					'normalized'    => $normalized,
					'schema'        => self::CACHE_SCHEMA,
					'source'        => $this->get_remote_manifest_url(),
					'etag'          => $fetched['etag'],
					'last_modified' => $fetched['last_modified'],
					'expires'       => time() + self::CACHE_TTL,
				)
			);
		} elseif ( null !== $cached && isset( $cached['normalized'] ) && is_array( $cached['normalized'] ) ) {
			// Failed refresh with a readable cache: keep serving what we have.
			$normalized = $cached['normalized'];
		}

		return $normalized;
	}

	/**
	 * Return serialized block markup for a pattern ID.
	 *
	 * @param string|int $pattern_id Pattern identifier.
	 * @return string|null Content string or null when unknown.
	 */
	public function get_pattern_content( $pattern_id ): ?string {
		$pattern_id = is_numeric( $pattern_id ) ? (string) (int) $pattern_id : sanitize_text_field( (string) $pattern_id );
		if ( '' === $pattern_id ) {
			return null;
		}

		$cache_key = $this->payload_cache_key( $pattern_id );
		$cached    = get_transient( $cache_key );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $this->within_size_limit( $cached ) ? $cached : null;
		}

		$remote_url = $this->get_remote_manifest_url();
		if ( '' === $remote_url ) {
			return null;
		}

		$content = $this->fetch_remote_pattern_content( $pattern_id, $remote_url );
		if ( ! is_string( $content ) || '' === $content ) {
			return null;
		}

		if ( ! $this->within_size_limit( $content ) ) {
			// Oversized payloads are rejected like any invalid payload — and not cached.
			return null;
		}

		set_transient( $cache_key, $content, self::PAYLOAD_TTL );

		return $content;
	}

	/**
	 * Transient key for a pattern payload in the current cache generation.
	 *
	 * @param string $pattern_id Sanitized pattern identifier.
	 * @return string
	 */
	private function payload_cache_key( string $pattern_id ): string {
		// Salted with the source: pattern IDs are only unique per server, so a
		// payload cached from another manifest URL must never answer for this one.
		return self::PAYLOAD_TRANSIENT_PREFIX . $this->payload_generation() . '_' . md5( $this->get_remote_manifest_url() . '|' . $pattern_id );
	}

	/**
	 * Current payload cache generation.
	 *
	 * @return int
	 */
	private function payload_generation(): int {
		return absint( get_option( self::PAYLOAD_GENERATION_OPTION, 0 ) );
	}

	/**
	 * Retire every cached payload by moving to the next generation. Superseded
	 * transients are left to expire on their own TTL.
	 *
	 * @return void
	 */
	private function bump_payload_generation(): void {
		update_option( self::PAYLOAD_GENERATION_OPTION, $this->payload_generation() + 1, false );
	}

	/**
	 * Whether the feature is enabled for the editor.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		/**
		 * Filter whether the Ready Patterns library is enabled in the block editor.
		 *
		 * @param bool $enabled Default enabled state.
		 */
		$enabled = apply_filters( 'sp_lcpro_ready_patterns_enabled', true );

		if ( ! $enabled ) {
			return false;
		}

		return ( new \ShapedPlugin\LogoCarouselFree\Includes\Dashboard\ModulesVisibility() )->is_enabled( 'ready-patterns' );
	}

	/**
	 * REST route path for apiFetch (no /wp-json prefix).
	 *
	 * @return string
	 */
	public static function rest_base(): string {
		return '/logo-carousel-pro/v1/patterns';
	}

	/**
	 * Fetch the remote source, revalidating with stored ETag / Last-Modified.
	 *
	 * @param array<string, mixed>|null $cached Existing cache payload, when readable.
	 * @return array{status: string, data: array<string, mixed>|null, etag: string, last_modified: string}
	 */
	private function fetch_source( ?array $cached ): array {
		$result = array(
			'status'        => 'failed',
			'data'          => null,
			'etag'          => '',
			'last_modified' => '',
		);

		$remote_url = $this->get_remote_manifest_url();
		if ( '' === $remote_url ) {
			return $result;
		}

		$headers = array();
		if ( is_array( $cached ) ) {
			if ( ! empty( $cached['etag'] ) && is_string( $cached['etag'] ) ) {
				$headers['If-None-Match'] = $cached['etag'];
			}
			if ( ! empty( $cached['last_modified'] ) && is_string( $cached['last_modified'] ) ) {
				$headers['If-Modified-Since'] = $cached['last_modified'];
			}
		}

		$response = wp_safe_remote_get(
			$remote_url,
			array(
				'timeout' => 15,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $result;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 304 === $code ) {
			$result['status'] = 'not_modified';

			return $result;
		}

		if ( 200 !== $code ) {
			return $result;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return $result;
		}

		$result['status']        = 'ok';
		$result['data']          = $data;
		$result['etag']          = (string) wp_remote_retrieve_header( $response, 'etag' );
		$result['last_modified'] = (string) wp_remote_retrieve_header( $response, 'last-modified' );

		return $result;
	}

	/**
	 * Remote manifest URL after filter (empty string disables remote fetching).
	 *
	 * @return string
	 */
	private function get_remote_manifest_url(): string {
		/**
		 * Filter the remote Ready Patterns manifest URL.
		 *
		 * Return an empty string to disable remote fetching.
		 *
		 * @param string $url Default remote manifest URL.
		 */
		$url = apply_filters( 'sp_lcpro_patterns_remote_url', self::REMOTE_MANIFEST_URL );

		if ( ! is_string( $url ) ) {
			return '';
		}

		$url = esc_url_raw( $url );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Whether a payload is within the ingest size limit.
	 *
	 * @param string|null $content Payload markup.
	 * @return bool
	 */
	private function within_size_limit( ?string $content ): bool {
		if ( null === $content ) {
			return false;
		}

		return strlen( $content ) <= self::MAX_PAYLOAD_BYTES;
	}

	/**
	 * Fetch a single pattern payload from the remote server.
	 *
	 * @param string $pattern_id      Pattern ID.
	 * @param string $remote_manifest Remote manifest URL.
	 * @return string|null
	 */
	private function fetch_remote_pattern_content( string $pattern_id, string $remote_manifest ): ?string {
		/**
		 * Filter the remote single-pattern URL.
		 *
		 * @param string $url             Default derived URL (may be empty).
		 * @param string $pattern_id      Requested pattern ID.
		 * @param string $remote_manifest Remote manifest URL.
		 */
		$url = apply_filters( 'sp_lcpro_patterns_single_remote_url', '', $pattern_id, $remote_manifest );

		if ( '' === $url ) {
			$url = preg_replace( '#/pattern-list/?$#', '/single-pattern/' . rawurlencode( $pattern_id ), $remote_manifest );
			if ( ! is_string( $url ) || $url === $remote_manifest ) {
				return null;
			}
		}

		$url = esc_url_raw( $url );
		if ( ! is_string( $url ) || '' === $url ) {
			return null;
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( is_array( $data ) && ! empty( $data['content'] ) && is_string( $data['content'] ) ) {
			return $data['content'];
		}

		if ( is_string( $body ) && false !== strpos( $body, '<!-- wp:' ) ) {
			return $body;
		}

		return null;
	}

	/**
	 * Read manifest cache from uploads file or transient.
	 *
	 * @return array<string, mixed>|null
	 */
	private function read_cache(): ?array {
		$source = $this->get_remote_manifest_url();

		$file_cache = $this->read_file_cache();
		if ( self::is_usable_cache( $file_cache, $source ) ) {
			return $file_cache;
		}

		$transient = get_transient( self::TRANSIENT_KEY );
		if (
			is_array( $transient )
			&& isset( $transient['expires'] )
			&& (int) $transient['expires'] > time()
			&& self::is_usable_cache( $transient, $source )
		) {
			return $transient;
		}

		return null;
	}

	/**
	 * Whether a cached manifest was written by this build for this source.
	 *
	 * Pure. A cache from another manifest URL (the server moved, or the
	 * `sp_lcpro_patterns_remote_url` filter changed) is refetched rather than
	 * served: its items, thumbnails and validators all belong to the old server.
	 *
	 * @param array<string, mixed>|null $cache  Cache payload.
	 * @param string                    $source Current remote manifest URL.
	 * @return bool
	 */
	public static function is_usable_cache( ?array $cache, string $source ): bool {
		return null !== $cache
			&& self::CACHE_SCHEMA === (int) ( $cache['schema'] ?? 0 )
			&& '' !== $source
			&& ( $cache['source'] ?? '' ) === $source;
	}

	/**
	 * Read cache JSON from uploads directory.
	 *
	 * @return array<string, mixed>|null
	 */
	private function read_file_cache(): ?array {
		$path = $this->get_cache_file_path();
		if ( ! is_readable( $path ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			return null;
		}

		$data = json_decode( $contents, true );
		if ( ! is_array( $data ) || empty( $data['expires'] ) || (int) $data['expires'] <= time() ) {
			return null;
		}

		return $data;
	}

	/**
	 * Persist manifest cache to uploads file with transient fallback.
	 *
	 * @param array<string, mixed> $payload Cache payload.
	 */
	private function write_cache( array $payload ): void {
		$path = $this->get_cache_file_path();
		$dir  = dirname( $path );

		if ( wp_mkdir_p( $dir ) && is_writable( $dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$written = file_put_contents( $path, wp_json_encode( $payload ) );
			if ( false !== $written ) {
				return;
			}
		}

		set_transient( self::TRANSIENT_KEY, $payload, self::CACHE_TTL );
	}

	/**
	 * Absolute path to the uploads cache file.
	 *
	 * @return string
	 */
	private function get_cache_file_path(): string {
		$upload_dir = wp_upload_dir();
		$base       = isset( $upload_dir['basedir'] ) ? $upload_dir['basedir'] : WP_CONTENT_DIR . '/uploads';

		return trailingslashit( $base ) . self::UPLOADS_SUBDIR . '/' . self::CACHE_FILENAME;
	}
}
