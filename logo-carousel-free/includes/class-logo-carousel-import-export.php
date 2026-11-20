<?php
/**
 * Custom import export.
 *
 * @link http://shapedplugin.com
 * @since 2.0.0
 *
 * @package Logo_Carousel.
 * @subpackage Logo_Carousel_Free/includes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Custom import export.
 */
class Logo_Carousel_Import_Export {

	/**
	 * Export
	 *
	 * @param  mixed $shortcode_ids Export logo and carousel shortcode ids.
	 * @return object
	 */
	public function export( $shortcode_ids ) {
		$export = array();
		if ( ! empty( $shortcode_ids ) ) {
			$post_type  = 'all_logos' === $shortcode_ids ? 'sp_logo_carousel' : 'sp_lc_shortcodes';
			$post_in    = 'all_logos' === $shortcode_ids || 'all_shortcodes' === $shortcode_ids ? '' : $shortcode_ids;
			$args       = array(
				'post_type'        => $post_type,
				'post_status'      => array( 'inherit', 'publish' ),
				'orderby'          => 'modified',
				'suppress_filters' => 1, // wpml, ignore language filter.
				'posts_per_page'   => -1,
				'post__in'         => $post_in,
			);
			$shortcodes = get_posts( $args );
			if ( ! empty( $shortcodes ) ) {
				foreach ( $shortcodes as $shortcode ) {
					$shortcode_export = array(
						'title'       => sanitize_text_field( $shortcode->post_title ),
						'original_id' => absint( $shortcode->ID ),
						'meta'        => array(),
					);
					if ( 'all_logos' === $shortcode_ids ) {
						$shortcode_export['image']     = get_the_post_thumbnail_url( $shortcode->ID, 'single-post-thumbnail' );
						$shortcode_export['all_logos'] = 'all_logos';
					}
					foreach ( get_post_meta( $shortcode->ID ) as $metakey => $value ) {
						$meta_key                              = sanitize_key( $metakey );
						$meta_value                            = is_serialized( $value[0] ) ? $value[0] : sanitize_text_field( $value[0] );
						$shortcode_export['meta'][ $meta_key ] = $meta_value;
					}
					$export['shortcode'][] = $shortcode_export;

					unset( $shortcode_export );
				}
				$export['metadata'] = array(
					'version' => SP_LC_VERSION,
					'date'    => gmdate( 'Y/m/d' ),
				);
			}
			return $export;
		}
	}

	/**
	 * Export Accordion by ajax.
	 *
	 * @return void
	 */
	public function export_shortcodes() {
		$nonce = ( ! empty( $_POST['nonce'] ) ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'splogocarousel_options_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Authorization failed!', 'logo-carousel-free' ),
				),
				401
			);
		}

		$_capability = apply_filters( 'splogocarousel_import_export_capability', 'manage_options' );
		if ( ! current_user_can( $_capability ) ) {
			wp_send_json_error( array( 'error' => esc_html__( 'You do not have permission to export.', 'logo-carousel-free' ) ) );
		}

		$shortcode_ids = '';
		if ( isset( $_POST['lcp_ids'] ) ) {
			$shortcode_ids = is_array( $_POST['lcp_ids'] ) ? wp_unslash( array_map( 'absint', $_POST['lcp_ids'] ) ) : sanitize_text_field( wp_unslash( $_POST['lcp_ids'] ) );
		}

		$export = $this->export( $shortcode_ids );

		if ( is_wp_error( $export ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html( $export->get_error_message() ),
				),
				400
			);
		}

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
            // @codingStandardsIgnoreLine
            echo wp_json_encode($export, JSON_PRETTY_PRINT);
			die;
		}

		wp_send_json( $export, 200 );
	}
	/**
	 * Insert an attachment from an URL address.
	 *
	 * @param  String $url remote url.
	 * @param  Int    $parent_post_id parent post id.
	 * @return Int    Attachment ID
	 */
	public function insert_attachment_from_url( $url, $parent_post_id = null ) {
		require_once ABSPATH . 'wp-admin/includes/post.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_title = sanitize_file_name( pathinfo( $url, PATHINFO_FILENAME ) );
		// Does the attachment already exist ?
		$attachment_id = post_exists( $attachment_title, '', '', 'attachment' );
		if ( $attachment_id ) {
			return absint( $attachment_id );
		}

		$url       = esc_url_raw( $url );
		$file_name = sanitize_file_name( basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
		if ( empty( $url ) || empty( $file_name ) ) {
			return false;
		}

		// Download to a temp file. wp_safe_remote_get() rejects local and private network addresses (SSRF).
		$tmp_file = wp_tempnam( $file_name );
		if ( ! $tmp_file ) {
			return false;
		}
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 10,
				'redirection'         => 3,
				'stream'              => true,
				'filename'            => $tmp_file,
				'limit_response_size' => 10 * MB_IN_BYTES,
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			wp_delete_file( $tmp_file );
			return false;
		}

		// Restrict to images, checked against the actual file content, not just the file name.
		$file_type = wp_check_filetype_and_ext( $tmp_file, $file_name );
		if ( empty( $file_type['type'] ) || ! wp_match_mime_types( 'image', $file_type['type'] ) || ! wp_get_image_mime( $tmp_file ) ) {
			wp_delete_file( $tmp_file );
			return false;
		}
		if ( ! empty( $file_type['proper_filename'] ) ) {
			$file_name = $file_type['proper_filename'];
		}

		// Let WordPress move the file into uploads and create the attachment with its metadata.
		$attach_id = media_handle_sideload(
			array(
				'name'     => $file_name,
				'tmp_name' => $tmp_file,
			),
			(int) $parent_post_id,
			null,
			array( 'post_title' => $attachment_title )
		);
		if ( is_wp_error( $attach_id ) ) {
			wp_delete_file( $tmp_file );
			return false;
		}

		return $attach_id;
	}

	/**
	 * Recursively determine whether a decoded value contains any object.
	 *
	 * After unserialize( ..., array( 'allowed_classes' => false ) ) every object
	 * becomes a __PHP_Incomplete_Class instance, so this catches both.
	 *
	 * @param mixed $data Decoded meta value.
	 * @return bool
	 */
	private static function contains_object( $data ) {
		if ( is_object( $data ) ) {
			return true;
		}
		if ( is_array( $data ) ) {
			foreach ( $data as $item ) {
				if ( self::contains_object( $item ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Sanitize one imported meta value (already unserialized).
	 *
	 * The logo's Logo URL is kept as a URL, so query strings and encoded
	 * characters survive; every other text value is sanitized as plain text.
	 *
	 * @param string $key   Meta key.
	 * @param mixed  $value Meta value.
	 * @return mixed
	 */
	private static function sanitize_import_meta( $key, $value ) {
		$logo_link = null;
		if ( 'sp_logo_carousel_link_option' === $key && is_array( $value ) && isset( $value['lcp_logo_link'] ) && is_string( $value['lcp_logo_link'] ) ) {
			$logo_link = esc_url_raw( trim( $value['lcp_logo_link'] ) );
		}

		$value = map_deep(
			$value,
			function ( $item ) {
				return is_string( $item ) ? sanitize_text_field( $item ) : $item;
			}
		);

		if ( null !== $logo_link ) {
			$value['lcp_logo_link'] = $logo_link;
		}

		return $value;
	}

	/**
	 * Import logo ans shortcode.
	 *
	 * @param  mixed $shortcodes Import logo and carousel shortcode array.
	 *
	 * @throws \Exception If get error.
	 * @return object
	 */
	public function import( $shortcodes ) {
		$errors        = array();
		$lcp_post_type = 'sp_logo_carousel';
		foreach ( $shortcodes as $index => $shortcode ) {
			$errors[ $index ] = array();
			$new_shortcode_id = 0;
			$lcp_post_type    = isset( $shortcode['all_logos'] ) ? 'sp_logo_carousel' : 'sp_lc_shortcodes';
			try {
				$new_shortcode_id = wp_insert_post(
					array(
						'post_title'  => isset( $shortcode['title'] ) ? sanitize_text_field( $shortcode['title'] ) : '',
						'post_status' => 'publish',
						'post_type'   => $lcp_post_type,
					),
					true
				);
				if ( isset( $shortcode['all_logos'] ) ) {
					$url = isset( $shortcode['image'] ) && ! empty( $shortcode['image'] ) ? $shortcode['image'] : '';
					if ( $url ) {
						// Insert attachment id.
						$thumb_id                           = $this->insert_attachment_from_url( $url, $new_shortcode_id );
						$shortcode['meta']['_thumbnail_id'] = $thumb_id;
					}
				}
				if ( is_wp_error( $new_shortcode_id ) ) {
					throw new \Exception( $new_shortcode_id->get_error_message() );
				}

				if ( isset( $shortcode['meta'] ) && is_array( $shortcode['meta'] ) ) {
					foreach ( $shortcode['meta'] as $key => $value ) {
						if ( ! is_string( $key ) || '' === $key || ( ! is_scalar( $value ) && ! is_array( $value ) ) ) {
							continue;
						}
						if ( is_scalar( $value ) ) {
							$value = str_replace( '{#ID#}', $new_shortcode_id, (string) $value );
						}

						if ( is_string( $value ) && is_serialized( $value ) ) {
							$serialized_false = 'b:0;' === $value;
							$value            = unserialize( $value, array( 'allowed_classes' => false ) ); // phpcs:ignore -- no classes allowed.
							// Skip a setting that failed to unserialize or carried a PHP object.
							if ( ( false === $value && ! $serialized_false ) || self::contains_object( $value ) ) {
								continue;
							}
						} elseif ( is_array( $value ) && self::contains_object( $value ) ) {
							continue;
						}

						// Sanitized after unserializing: run over the serialized text, a value
						// whose length changed (a URL with "&" or "%20") broke the whole setting.
						$value = self::sanitize_import_meta( $key, $value );

						update_post_meta(
							$new_shortcode_id,
							$key,
							$value
						);
					}
				}
			} catch ( \Exception $e ) {
				array_push( $errors[ $index ], $e->getMessage() );

				// If there was a failure somewhere, clean up.
				wp_trash_post( $new_shortcode_id );
			}

			// If no errors, remove the index.
			if ( ! count( $errors[ $index ] ) ) {
				unset( $errors[ $index ] );
			}

			// External modules manipulate data here.
			do_action( 'sp_logo_carousel_shortcode_imported', $new_shortcode_id );
		}

		$errors = reset( $errors );
		return isset( $errors[0] ) ? new \WP_Error( 'import_accordion_error', $errors[0] ) : $lcp_post_type;
	}

	/**
	 * Import logos/shortcodes by ajax.
	 *
	 * @return void
	 */
	public function import_shortcodes() {
		$nonce = ( ! empty( $_POST['nonce'] ) ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'splogocarousel_options_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Authorization failed!', 'logo-carousel-free' ),
				),
				401
			);
		}

		$_capability = apply_filters( 'splogocarousel_import_export_capability', 'manage_options' );
		if ( ! current_user_can( $_capability ) ) {
			wp_send_json_error( array( 'error' => esc_html__( 'You do not have permission to import.', 'logo-carousel-free' ) ) );
		}

		// Decoded raw: meta values are serialized, and are sanitized in import() once unserialized.
		$data = isset( $_POST['shortcode'] ) && is_string( $_POST['shortcode'] ) ? wp_unslash( $_POST['shortcode'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $data ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Nothing to import.', 'logo-carousel-free' ) ), 400 );
		}

		// Decode JSON with error checking.
		$decoded_data = json_decode( $data, true );
		if ( is_string( $decoded_data ) ) {
			$decoded_data = json_decode( $decoded_data, true );
		}
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid JSON data.', 'logo-carousel-free' ),
				),
				400
			);
		}

		// Check if shortcode key exists and is valid.
		if ( ! isset( $decoded_data['shortcode'] ) || ! is_array( $decoded_data['shortcode'] ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid shortcode data structure.', 'logo-carousel-free' ),
				),
				400
			);
		}

		$shortcodes = array();
		foreach ( $decoded_data['shortcode'] as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$meta = isset( $item['meta'] ) && is_array( $item['meta'] ) ? $item['meta'] : array();
			unset( $item['meta'] );
			$item         = map_deep(
				$item,
				function ( $value ) {
					return is_string( $value ) ? sanitize_text_field( $value ) : $value;
				}
			);
			$item['meta'] = $meta;

			$shortcodes[ $index ] = $item;
		}

		$status = $this->import( $shortcodes );

		if ( is_wp_error( $status ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html( $status->get_error_message() ),
				),
				400
			);
		}

		wp_send_json_success( $status, 200 );
	}
}
