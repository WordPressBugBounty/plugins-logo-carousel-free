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

		if ( ! class_exists( 'WP_Http' ) ) {
			include_once ABSPATH . WPINC . '/class-http.php';
		}
		$attachment_title = sanitize_file_name( pathinfo( $url, PATHINFO_FILENAME ) );
		// Does the attachment already exist ?
		$attachment_id = post_exists( $attachment_title, '', '', 'attachment' );
		if ( $attachment_id ) {
			return absint( $attachment_id );
		}

		$http     = new WP_Http();
		$response = $http->request( $url );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$upload = wp_upload_bits( basename( $url ), null, $response['body'] );
		if ( ! empty( $upload['error'] ) ) {
			return false;
		}

		$file_path = $upload['file'];
		$file_name = basename( $file_path );
		$file_type = wp_check_filetype( $file_name, null );

		// Double-check MIME type & restrict to safe file types (images only, here).
		if ( empty( $file_type['type'] ) || ! wp_match_mime_types( 'image', $file_type['type'] ) ) {
			// Delete the invalid file immediately.
			wp_delete_file( $file_path );
			return false;
		}

		$wp_upload_dir = wp_upload_dir();

		$post_info = array(
			'guid'           => esc_url_raw( $wp_upload_dir['url'] . '/' . $file_name ),
			'post_mime_type' => sanitize_mime_type( $file_type['type'] ),
			'post_title'     => esc_html( $attachment_title ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Create the attachment.
		$attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );

		// Include image.php.
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Define attachment metadata.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

		// Assign metadata to attachment.
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
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
						update_post_meta(
							$new_shortcode_id,
							$key,
							maybe_unserialize( str_replace( '{#ID#}', $new_shortcode_id, $value ) )
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

		$data = isset( $_POST['shortcode'] ) ? wp_kses_data( wp_unslash( $_POST['shortcode'] ) ) : '';

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

		$shortcodes = map_deep(
			$decoded_data['shortcode'],
			function ( $value ) {
				return is_string( $value ) ? sanitize_text_field( $value ) : $value;
			}
		);

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
