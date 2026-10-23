<?php
/**
 * Upload safety for logo media: real-MIME validation, the scoped SVG
 * allowance and uploads-directory hardening.
 *
 * Uploads behave exactly like WordPress's own: an identical file uploaded
 * again becomes a new attachment (core names it `logo-1.png`). Nothing here
 * hashes, reuses or deletes attachments.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LogoUploadResolver' ) ) {
	/**
	 * Upload validation and hardening for logo uploads.
	 */
	class LogoUploadResolver {
		/**
		 * Request flag for block-scoped uploads.
		 *
		 * @var string
		 */
		const UPLOAD_REQUEST_FLAG = 'sp_lcpro_logo_upload';

		/**
		 * Request flag for block-scoped SVG uploads.
		 *
		 * @var string
		 */
		const SVG_UPLOAD_REQUEST_FLAG = 'sp_lcpro_svg_upload';

		/**
		 * Marker placed inside a hardening file so re-runs can detect ownership
		 * without clobbering rules a host or another plugin already put there.
		 *
		 * @var string
		 */
		const HARDENING_MARKER = 'logo-carousel-pro-upload-hardening';

		/**
		 * Wire hooks.
		 *
		 * @return void
		 */
		public function init() {
			add_filter( 'wp_handle_upload_prefilter', array( $this, 'validate_uploaded_image_file' ) );
			add_filter( 'upload_mimes', array( $this, 'filter_upload_mimes' ), 10, 2 );
			add_filter( 'wp_prevent_unsupported_mime_type_uploads', array( $this, 'allow_scoped_svg_unsupported_mime_bypass' ), 10, 2 );

			// Defense-in-depth only: the mime/content checks above already stop a
			// disguised PHP file from ever being accepted as an image/SVG, so this
			// does not gate any decision. It only adds a filesystem-level guard so
			// that *if* an executable file ever reached the uploads directory by
			// any other means, the webserver would still refuse to run it.
			add_action( 'admin_init', array( $this, 'maybe_harden_uploads_directory' ) );
		}

		/**
		 * Ensure the uploads directory refuses to execute PHP, independent of any
		 * MIME/content validation performed earlier in the upload flow.
		 *
		 * Idempotent and additive: skipped entirely if a `.htaccess`/`index.php`
		 * already exists (host, another security plugin, or a previous run), so
		 * this never overwrites existing server configuration.
		 *
		 * @return void
		 */
		public function maybe_harden_uploads_directory() {
			if ( ! function_exists( 'wp_upload_dir' ) ) {
				return;
			}

			$upload_dir = wp_upload_dir();
			if ( empty( $upload_dir['basedir'] ) || ! empty( $upload_dir['error'] ) ) {
				return;
			}

			$base_dir = untrailingslashit( $upload_dir['basedir'] );
			if ( ! is_dir( $base_dir ) || ! is_writable( $base_dir ) ) {
				return;
			}

			$this->maybe_write_htaccess( $base_dir );
			$this->maybe_write_index_guard( $base_dir );
		}

		/**
		 * Write an Apache/LiteSpeed execution-denial `.htaccess` if none exists yet.
		 *
		 * @param string $base_dir Uploads base directory (no trailing slash).
		 * @return void
		 */
		private function maybe_write_htaccess( $base_dir ) {
			$htaccess_path = $base_dir . '/.htaccess';

			if ( file_exists( $htaccess_path ) ) {
				return;
			}

			$rules = "# BEGIN " . self::HARDENING_MARKER . "\n"
				. "# Prevent execution of scripts uploaded to this directory.\n"
				. "<IfModule mod_php.c>\n"
				. "\tphp_flag engine off\n"
				. "</IfModule>\n"
				. "<IfModule mod_php7.c>\n"
				. "\tphp_flag engine off\n"
				. "</IfModule>\n"
				. "<FilesMatch \"\\.(?:php|php\\d?|phtml|phar|pht)$\">\n"
				. "\tRequire all denied\n"
				. "</FilesMatch>\n"
				. "<IfModule !mod_authz_core.c>\n"
				. "\t<FilesMatch \"\\.(?:php|php\\d?|phtml|phar|pht)$\">\n"
				. "\t\tOrder allow,deny\n"
				. "\t\tDeny from all\n"
				. "\t</FilesMatch>\n"
				. "</IfModule>\n"
				. "# END " . self::HARDENING_MARKER . "\n";

			@file_put_contents( $htaccess_path, $rules, LOCK_EX );
		}

		/**
		 * Write a blank `index.php` guard against directory listing if none exists.
		 *
		 * @param string $base_dir Uploads base directory (no trailing slash).
		 * @return void
		 */
		private function maybe_write_index_guard( $base_dir ) {
			$index_path = $base_dir . '/index.php';

			if ( file_exists( $index_path ) ) {
				return;
			}

			@file_put_contents( $index_path, "<?php\n// Silence is golden.\n", LOCK_EX );
		}

		/**
		 * Validate uploaded image files against the real server-side mime type.
		 *
		 * @param array<string, mixed> $file Uploaded file payload.
		 * @return array<string, mixed>
		 */
		public function validate_uploaded_image_file( $file ) {
			if ( ! is_array( $file ) ) {
				return $file;
			}

			if ( ! $this->is_logo_block_upload_request() ) {
				return $file;
			}

			$file_name = isset( $file['name'] ) ? (string) $file['name'] : '';
			$tmp_name  = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';

			if ( '' === $file_name || '' === $tmp_name || ! file_exists( $tmp_name ) ) {
				return $file;
			}

			$allowed_mimes = $this->get_allowed_logo_image_mimes( $this->is_svg_upload_allowed_for_request() );
			$declared_type = isset( $file['type'] ) ? (string) $file['type'] : '';
			$is_image_like = 0 === strpos( strtolower( $declared_type ), 'image/' ) || $this->has_supported_image_extension( $file_name );

			if ( ! $is_image_like ) {
				return $file;
			}

			$wp_filetype = function_exists( 'wp_check_filetype_and_ext' )
				? wp_check_filetype_and_ext( $tmp_name, $file_name, $allowed_mimes )
				: array();
			$real_type   = isset( $wp_filetype['type'] ) ? (string) $wp_filetype['type'] : '';
			$real_ext    = isset( $wp_filetype['ext'] ) ? (string) $wp_filetype['ext'] : '';

			if ( '' === $real_type || '' === $real_ext || ! isset( $allowed_mimes[ $real_ext ] ) ) {
				$file['error'] = __( 'This file does not match a valid image format and could not be uploaded.', 'logo-carousel-free' );
				return $file;
			}

			$finfo_type = $this->detect_real_mime_type( $tmp_name );
			if ( '' !== $finfo_type && ! $this->is_server_mime_match( $finfo_type, $real_type ) ) {
				$file['error'] = __( 'This file appears to be mislabeled and could not be uploaded as an image.', 'logo-carousel-free' );
				return $file;
			}

			if ( 'image/svg+xml' === $real_type ) {
				if ( ! $this->is_svg_upload_allowed_for_request() ) {
					$file['error'] = __( 'SVG uploads are not enabled for this block.', 'logo-carousel-free' );
					return $file;
				}

				$sanitization = $this->sanitize_svg_upload_file( $tmp_name );
				if ( is_wp_error( $sanitization ) ) {
					$file['error'] = $sanitization->get_error_message();
					return $file;
				}
			}

			$file['type'] = $real_type;

			return $file;
		}

		/**
		 * Allow SVG only for trusted, block-scoped upload requests.
		 *
		 * @param array<string, string> $mimes Mime map.
		 * @param int|object|null       $user  Current user or user ID.
		 * @return array<string, string>
		 */
		public function filter_upload_mimes( $mimes, $user = null ) {
			if ( ! is_array( $mimes ) ) {
				$mimes = array();
			}

			if ( ! $this->is_logo_block_upload_request() || ! $this->is_svg_upload_allowed_for_request() ) {
				return $mimes;
			}

			$mimes['svg'] = 'image/svg+xml';

			return $mimes;
		}

		/**
		 * Bypass WordPress image-processing upload rejection for scoped SVG requests.
		 *
		 * WordPress can reject image/* uploads that do not map to server-side image
		 * processing. SVG is valid for this block after sanitization, so bypass that
		 * guard only for the trusted, request-scoped block upload.
		 *
		 * @param bool        $prevent   Whether WordPress should prevent the upload.
		 * @param string|null $mime_type Uploaded mime type if available.
		 * @return bool
		 */
		public function allow_scoped_svg_unsupported_mime_bypass( $prevent, $mime_type = null ) {
			if ( ! $prevent || ! $this->is_logo_block_upload_request() || ! $this->is_svg_upload_allowed_for_request() ) {
				return $prevent;
			}

			if ( 'image/svg+xml' !== strtolower( (string) $mime_type ) ) {
				return $prevent;
			}

			return false;
		}

		/**
		 * Determine whether the upload originated from the block-specific local upload flow.
		 *
		 * @return bool
		 */
		private function is_logo_block_upload_request() {
			$request_flag = $this->read_scoped_upload_flag( self::UPLOAD_REQUEST_FLAG );

			return '1' === $request_flag;
		}

		/**
		 * Check whether SVG uploads are enabled for this request and user.
		 *
		 * @return bool
		 */
		private function is_svg_upload_allowed_for_request() {
			$request_flag = $this->read_scoped_upload_flag( self::SVG_UPLOAD_REQUEST_FLAG );

			return '1' === $request_flag && current_user_can( 'manage_options' );
		}

		/**
		 * Read a scoped upload flag from request data.
		 *
		 * @param string $key Flag key.
		 * @return string
		 */
		private function read_scoped_upload_flag( $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			}

			if ( isset( $_GET[ $key ] ) ) {
				return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
			}

			if ( isset( $_REQUEST[ $key ] ) ) {
				return sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) );
			}

			return '';
		}

		/**
		 * Return the allowed image mime map used for logo uploads.
		 *
		 * @return array<string, string>
		 */
		private function get_allowed_logo_image_mimes( $include_svg = false ) {
			$mimes = array(
				'jpg'  => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'jpe'  => 'image/jpeg',
				'png'  => 'image/png',
				'gif'  => 'image/gif',
				'webp' => 'image/webp',
			);

			if ( $include_svg ) {
				$mimes['svg'] = 'image/svg+xml';
			}

			return $mimes;
		}

		/**
		 * Check whether the file name looks like a supported image upload.
		 *
		 * @param string $file_name Uploaded file name.
		 * @return bool
		 */
		private function has_supported_image_extension( $file_name ) {
			$extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

			return in_array( $extension, array_keys( $this->get_allowed_logo_image_mimes( true ) ), true );
		}

		/**
		 * Detect the real mime type from the temporary upload file.
		 *
		 * @param string $tmp_name Temporary upload file path.
		 * @return string
		 */
		private function detect_real_mime_type( $tmp_name ) {
			if ( ! class_exists( '\finfo' ) || ! is_readable( $tmp_name ) ) {
				return '';
			}

			$finfo = new \finfo( FILEINFO_MIME_TYPE );
			$mime  = $finfo->file( $tmp_name );

			return is_string( $mime ) ? strtolower( trim( $mime ) ) : '';
		}

		/**
		 * Compare WordPress-detected and finfo-detected mime types.
		 *
		 * @param string $server_mime Mime from finfo.
		 * @param string $wp_mime Mime from wp_check_filetype_and_ext.
		 * @return bool
		 */
		private function is_server_mime_match( $server_mime, $wp_mime ) {
			$server_mime = strtolower( trim( (string) $server_mime ) );
			$wp_mime     = strtolower( trim( (string) $wp_mime ) );

			if ( $server_mime === $wp_mime ) {
				return true;
			}

			$aliases = array(
				'image/svg'     => array( 'image/svg+xml', 'text/plain', 'application/xml', 'text/xml' ),
				'image/svg+xml' => array( 'image/svg', 'text/plain', 'application/xml', 'text/xml' ),
				'image/jpeg'    => array( 'image/pjpeg' ),
			);

			return isset( $aliases[ $wp_mime ] ) && in_array( $server_mime, $aliases[ $wp_mime ], true );
		}

		/**
		 * Sanitize the uploaded SVG file in place.
		 *
		 * @param string $tmp_name Temporary SVG path.
		 * @return true|\WP_Error
		 */
		private function sanitize_svg_upload_file( $tmp_name ) {
			if ( ! is_readable( $tmp_name ) ) {
				return new \WP_Error( 'sp_lcpro_svg_unreadable', __( 'The SVG file could not be read for sanitization.', 'logo-carousel-free' ) );
			}

			$markup = file_get_contents( $tmp_name );
			if ( false === $markup || '' === trim( (string) $markup ) ) {
				return new \WP_Error( 'sp_lcpro_svg_empty', __( 'The SVG file appears to be empty or unreadable.', 'logo-carousel-free' ) );
			}

			$sanitized = $this->sanitize_svg_markup( (string) $markup );
			if ( is_wp_error( $sanitized ) ) {
				return $sanitized;
			}

			$written = file_put_contents( $tmp_name, $sanitized, LOCK_EX );
			if ( false === $written ) {
				return new \WP_Error( 'sp_lcpro_svg_write_failed', __( 'The SVG file could not be sanitized before upload.', 'logo-carousel-free' ) );
			}

			return true;
		}

		/**
		 * Sanitize SVG markup and remove unsafe constructs.
		 *
		 * @param string $markup Raw SVG markup.
		 * @return string|\WP_Error
		 */
		private function sanitize_svg_markup( $markup ) {
			$markup = trim( (string) $markup );

			if ( '' === $markup ) {
				return new \WP_Error( 'sp_lcpro_svg_invalid', __( 'The SVG file is empty.', 'logo-carousel-free' ) );
			}

			if ( preg_match( '/<!DOCTYPE|<!ENTITY/i', $markup ) ) {
				return new \WP_Error( 'sp_lcpro_svg_doctype', __( 'The SVG file contains unsafe XML declarations.', 'logo-carousel-free' ) );
			}

			$markup = preg_replace( '/<\?(?:xml|php).*?\?>/is', '', $markup );

			if ( ! class_exists( '\DOMDocument' ) ) {
				return new \WP_Error(
					'sp_lcpro_svg_dom_required',
					__( 'Secure SVG sanitization requires the PHP DOM extension on this server.', 'logo-carousel-free' )
				);
			}

			$sanitized = $this->sanitize_svg_markup_with_dom( $markup );

			if ( is_wp_error( $sanitized ) ) {
				return $sanitized;
			}

			/**
			 * Filter sanitized SVG markup before it is written to disk.
			 *
			 * @param string $sanitized Sanitized SVG markup.
			 * @param string $markup    Original SVG markup.
			 */
			return (string) apply_filters( 'sp_lcpro_sanitized_svg_markup', $sanitized, $markup );
		}

		/**
		 * Sanitize SVG markup with DOMDocument.
		 *
		 * @param string $markup Raw SVG markup.
		 * @return string|\WP_Error
		 */
		private function sanitize_svg_markup_with_dom( $markup ) {
			$previous = libxml_use_internal_errors( true );
			$dom      = new \DOMDocument();
			$loaded   = $dom->loadXML( $markup, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NOBLANKS );
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );

			if ( ! $loaded || ! $dom->documentElement || 'svg' !== strtolower( $dom->documentElement->localName ) ) {
				return new \WP_Error( 'sp_lcpro_svg_parse_failed', __( 'The SVG file could not be parsed safely.', 'logo-carousel-free' ) );
			}

			$this->sanitize_svg_dom_node( $dom->documentElement );
			$sanitized = $dom->saveXML( $dom->documentElement );

			if ( ! is_string( $sanitized ) || '' === trim( $sanitized ) ) {
				return new \WP_Error( 'sp_lcpro_svg_sanitize_failed', __( 'The SVG file became invalid after sanitization.', 'logo-carousel-free' ) );
			}

			return trim( $sanitized );
		}

		/**
		 * Sanitize a DOM node and its children recursively.
		 *
		 * @param \DOMNode $node DOM node.
		 * @return void
		 */
		private function sanitize_svg_dom_node( $node ) {
			if ( XML_ELEMENT_NODE !== $node->nodeType ) {
				for ( $i = $node->childNodes->length - 1; $i >= 0; $i-- ) {
					$child = $node->childNodes->item( $i );
					if ( $child ) {
						$this->sanitize_svg_dom_node( $child );
					}
				}
				return;
			}

			$allowed_elements = $this->get_allowed_svg_elements();
			$tag_name         = strtolower( $node->localName );

			if ( ! in_array( $tag_name, $allowed_elements, true ) ) {
				if ( $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
				return;
			}

			if ( $node->hasAttributes() ) {
				for ( $i = $node->attributes->length - 1; $i >= 0; $i-- ) {
					$attribute = $node->attributes->item( $i );
					if ( ! $attribute ) {
						continue;
					}

					$name  = $attribute->nodeName;
					$value = $attribute->nodeValue;

					if ( ! $this->is_allowed_svg_attribute( $name ) ) {
						$node->removeAttributeNode( $attribute );
						continue;
					}

					$sanitized = $this->sanitize_svg_attribute_value( $name, $value );
					if ( '' === $sanitized ) {
						$node->removeAttributeNode( $attribute );
						continue;
					}

					$attribute->nodeValue = $sanitized;
				}
			}

			if ( 'style' === $tag_name ) {
				$sanitized_css = $this->sanitize_svg_style_block( $node->textContent );
				if ( '' === $sanitized_css ) {
					if ( $node->parentNode ) {
						$node->parentNode->removeChild( $node );
					}
					return;
				}

				while ( $node->firstChild ) {
					$node->removeChild( $node->firstChild );
				}

				$node->appendChild( $node->ownerDocument->createTextNode( $sanitized_css ) );

				return;
			}

			for ( $i = $node->childNodes->length - 1; $i >= 0; $i-- ) {
				$child = $node->childNodes->item( $i );
				if ( ! $child ) {
					continue;
				}

				if ( XML_COMMENT_NODE === $child->nodeType || XML_PI_NODE === $child->nodeType || XML_DOCUMENT_TYPE_NODE === $child->nodeType ) {
					$node->removeChild( $child );
					continue;
				}

				$this->sanitize_svg_dom_node( $child );
			}
		}

		/**
		 * Allowed SVG element names.
		 *
		 * @return array<int, string>
		 */
		private function get_allowed_svg_elements() {
			$elements = array(
				'svg',
				'g',
				'defs',
				'title',
				'desc',
				'metadata',
				'style',
				'symbol',
				'use',
				'path',
				'circle',
				'ellipse',
				'line',
				'polyline',
				'polygon',
				'rect',
				'clippath',
				'mask',
				'pattern',
				'filter',
				'lineargradient',
				'radialgradient',
				'stop',
				'fegaussianblur',
				'feoffset',
				'fecolormatrix',
				'feflood',
				'fecomposite',
				'feblend',
				'femerge',
				'femergenode',
				'fecomponenttransfer',
				'fefunca',
				'fefuncb',
				'fefuncg',
				'fefuncr',
				'fetile',
				'femorphology',
				'fedropshadow',
				'text',
				'tspan',
			);

			return array_values( apply_filters( 'sp_lcpro_allowed_svg_elements', $elements ) );
		}

		/**
		 * Check whether an SVG attribute is allowed.
		 *
		 * @param string $name Attribute name.
		 * @return bool
		 */
		private function is_allowed_svg_attribute( $name ) {
			$name    = strtolower( (string) $name );
			$allowed = array(
				'id',
				'class',
				'xmlns',
				'xmlns:xlink',
				'viewbox',
				'width',
				'height',
				'x',
				'y',
				'x1',
				'y1',
				'x2',
				'y2',
				'cx',
				'cy',
				'r',
				'rx',
				'ry',
				'd',
				'points',
				'fill',
				'fill-opacity',
				'fill-rule',
				'stroke',
				'stroke-opacity',
				'stroke-width',
				'stroke-linecap',
				'stroke-linejoin',
				'stroke-dasharray',
				'stroke-dashoffset',
				'opacity',
				'transform',
				'preserveaspectratio',
				'version',
				'role',
				'aria-hidden',
				'focusable',
				'clip-path',
				'clip-rule',
				'mask',
				'maskunits',
				'maskcontentunits',
				'gradientunits',
				'gradienttransform',
				'offset',
				'stop-color',
				'stop-opacity',
				'spreadmethod',
				'patternunits',
				'patterncontentunits',
				'patterntransform',
				'pattern',
				'filter',
				'filterunits',
				'primitiveunits',
				'result',
				'in',
				'in2',
				'stddeviation',
				'operator',
				'values',
				'flood-color',
				'flood-opacity',
				'color-interpolation-filters',
				'vector-effect',
				'shape-rendering',
				'stroke-miterlimit',
				'href',
				'xlink:href',
				'dx',
				'dy',
				'text-anchor',
				'font-family',
				'font-size',
				'font-weight',
				'dominant-baseline',
				'xml:space',
				'preservealpha',
				'basefrequency',
				'numoctaves',
				'seed',
				'surfacescale',
				'diffuseconstant',
				'specularconstant',
				'specularexponent',
				'kernelmatrix',
				'k1',
				'k2',
				'k3',
				'k4',
				'letter-spacing',
				'style',
			);

			$allowed = array_values( apply_filters( 'sp_lcpro_allowed_svg_attributes', $allowed ) );

			return in_array( $name, $allowed, true ) && 0 !== stripos( $name, 'on' );
		}

		/**
		 * Sanitize an SVG attribute value.
		 *
		 * @param string $name  Attribute name.
		 * @param string $value Attribute value.
		 * @return string
		 */
		private function sanitize_svg_attribute_value( $name, $value ) {
			$value = trim( (string) $value );
			$name  = strtolower( (string) $name );

			if ( '' === $value ) {
				return '';
			}

			if ( preg_match( '/(?:javascript:|data:|vbscript:|@import|expression|behavior:)/i', $value ) ) {
				return '';
			}

			if ( in_array( $name, array( 'href', 'xlink:href' ), true ) ) {
				return 0 === strpos( $value, '#' ) ? $value : '';
			}

			if ( 'style' === $name ) {
				return $this->sanitize_svg_style_attribute( $value );
			}

			if ( in_array( $name, array( 'clip-path', 'fill', 'stroke' ), true ) && 0 === stripos( $value, 'url(' ) ) {
				return preg_match( '/^url\(\s*#[-a-z0-9_:.]+\s*\)$/i', $value ) ? $value : '';
			}

			return preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value );
		}

		/**
		 * Sanitize a style attribute inside SVG.
		 *
		 * @param string $style Raw style string.
		 * @return string
		 */
		private function sanitize_svg_style_attribute( $style ) {
			$allowed_properties = array(
				'fill',
				'fill-opacity',
				'fill-rule',
				'stroke',
				'stroke-opacity',
				'stroke-width',
				'stroke-linecap',
				'stroke-linejoin',
				'stroke-dasharray',
				'stroke-dashoffset',
				'opacity',
				'display',
				'transform',
				'font-size',
				'font-family',
				'font-weight',
				'letter-spacing',
				'text-anchor',
				'clip-path',
				'mask',
				'filter',
				'stroke-miterlimit',
				'vector-effect',
				'shape-rendering',
			);
			$declarations       = array_filter( array_map( 'trim', explode( ';', (string) $style ) ) );
			$sanitized          = array();

			foreach ( $declarations as $declaration ) {
				if ( false === strpos( $declaration, ':' ) ) {
					continue;
				}

				list( $property, $value ) = array_map( 'trim', explode( ':', $declaration, 2 ) );
				$property                 = strtolower( $property );

				if ( ! in_array( $property, $allowed_properties, true ) ) {
					continue;
				}

				if ( preg_match( '/(?:javascript:|data:|vbscript:|@import|expression|behavior:)/i', $value ) ) {
					continue;
				}

				if ( false !== stripos( $value, 'url(' ) && ! preg_match( '/^url\(\s*#[-a-z0-9_:.]+\s*\)$/i', $value ) ) {
					continue;
				}

				$sanitized[] = $property . ':' . $value;
			}

			return implode( ';', $sanitized );
		}

		/**
		 * Sanitize CSS inside an SVG <style> element.
		 *
		 * @param string $css Raw CSS string.
		 * @return string
		 */
		private function sanitize_svg_style_block( $css ) {
			$css = trim( (string) $css );

			if ( '' === $css ) {
				return '';
			}

			if ( preg_match( '/(?:@import|javascript:|data:|vbscript:|expression|behavior:)/i', $css ) ) {
				return '';
			}

			preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER );

			if ( empty( $matches ) ) {
				return '';
			}

			$sanitized_rules = array();

			foreach ( $matches as $match ) {
				$selector = trim( $match[1] );
				$rules    = $this->sanitize_svg_style_attribute( $match[2] );

				if ( '' === $selector || '' === $rules ) {
					continue;
				}

				if ( ! preg_match( '/^[a-z0-9\s,\.\#:\-\_\>\+\~\*\[\]\(\)="\'`]+$/i', $selector ) ) {
					continue;
				}

				$sanitized_rules[] = $selector . '{' . $rules . '}';
			}

			return implode( '', $sanitized_rules );
		}
	}
}
