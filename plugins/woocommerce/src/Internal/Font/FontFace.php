<?php
/**
 * FontFace class file
 */

namespace Automattic\WooCommerce\Internal\Font;

// IMPORTANT: We have to switch to the WordPress API to create the FontFace post type when they will be implemented: https://github.com/WordPress/gutenberg/issues/58670!

/**
 * Helper class for font face related functionality.
 *
 * @internal Just for internal use.
 */
class FontFace {

	const POST_TYPE = 'wp_font_face';

	/**
	 * Gets the installed font face by slug.
	 *
	 * @param string $slug The font face slug.
	 * @return \WP_Post|null The font face post or null if not found.
	 */
	public static function get_installed_font_faces_by_slug( $slug ) {
		$query = new \WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'name'                   => $slug,
			)
		);

		if ( ! empty( $query->get_posts() ) ) {
			return $query->get_posts()[0];
		}
		return null;
	}

	/**
	 * Sanitizes a single src value for a font face.
	 *
	 * Copied from Gutenberg: https://github.com/WordPress/gutenberg/blob/8d94c3bd7af977d998466b56bd773f9b19de8d03/lib/compat/wordpress-6.5/fonts/class-wp-rest-font-faces-controller.php/#L837-L840
	 *
	 * @param string $value Font face src that is a URL or the key for a $_FILES array item.
	 *
	 * @return string Sanitized value.
	 */
	private static function sanitize_src( $value ) {
		$value = ltrim( $value );
		return false === wp_http_validate_url( $value ) ? (string) $value : esc_url_raw( $value );
	}

	/**
	 * Handles file upload error.
	 *
	 * Copied from Gutenberg: https://github.com/WordPress/gutenberg/blob/b283c47dba96d74dd7589a823d8ab84c9e5a4765/lib/compat/wordpress-6.5/fonts/class-wp-rest-font-faces-controller.php/#L859-L883
	 *
	 * @param array  $file    File upload data.
	 * @param string $message Error message from wp_handle_upload().
	 * @return WP_Error WP_Error object.
	 */
	private static function handle_font_file_upload_error( $file, $message ) {
		$status = 500;
		$code   = 'rest_font_upload_unknown_error';

		if ( __( 'Sorry, you are not allowed to upload this file type.', 'woocommerce' ) === $message ) {
			$status = 400;
			$code   = 'rest_font_upload_invalid_file_type';
		}

		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * Handles the upload of a font file using wp_handle_upload().
	 *
	 * Copied from Gutenberg: https://github.com/WordPress/gutenberg/blob/b283c47dba96d74dd7589a823d8ab84c9e5a4765/lib/compat/wordpress-6.5/fonts/class-wp-rest-font-faces-controller.php/#L859-L883
	 *
	 * @param array $file Single file item from $_FILES.
	 * @return array Array containing uploaded file attributes on success, or error on failure.
	 */
	private static function handle_font_file_upload( $file ) {
		add_filter( 'upload_mimes', array( 'WP_Font_Utils', 'get_allowed_font_mime_types' ) );
		add_filter( 'upload_dir', 'wp_get_font_dir' );

		$overrides = array(
			'upload_error_handler' => array( self::class, 'handle_font_file_upload_error' ),
			// Arbitrary string to avoid the is_uploaded_file() check applied
			// when using 'wp_handle_upload'.
			'action'               => 'wp_handle_font_upload',
			// Not testing a form submission.
			'test_form'            => false,
			// Seems mime type for files that are not images cannot be tested.
			// See wp_check_filetype_and_ext().
			'test_type'            => true,
			// Only allow uploading font files for this request.
			'mimes'                => \WP_Font_Utils::get_allowed_font_mime_types(),
		);

		$uploaded_file = wp_handle_upload( $file, $overrides );

		remove_filter( 'upload_dir', 'wp_get_font_dir' );
		remove_filter( 'upload_mimes', array( 'WP_Font_Utils', 'get_allowed_font_mime_types' ) );

		return $uploaded_file;
	}

	/**
	 * Downloads a file from a URL.
	 *
	 * @param string $file_url The file URL.
	 **/
	private static function download_file( $file_url ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$allowed_extensions = array( 'ttf', 'otf', 'woff', 'woff2', 'eot' );

		$allowed_extensions = array_map( 'preg_quote', $allowed_extensions );

		// Set variables for storage, fix file filename for query strings.
		preg_match( '/[^\?]+\.(' . implode( '|', $allowed_extensions ) . ')\b/i', $file_url, $matches );
		$file_array         = array();
		$file_array['name'] = wp_basename( $matches[0] );

		// Download file to temp location.
		$file_array['tmp_name'] = download_url( $file_url );
		return $file_array;
	}

	/**
	 * Inserts a font face.
	 *
	 * @param array $font_face The font face settings.
	 * @param int   $parent_font_family_id The parent font family ID.
	 * @return \WP_Error|\WP_Post The inserted font face post or an error if the font face already exists.
	 */
	public static function insert_font_face( array $font_face, int $parent_font_family_id ) {
		$slug = \WP_Font_Utils::get_font_face_slug( $font_face );

		// Check that the font face slug is unique.
		$query = new \WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'posts_per_page'         => 1,
				'name'                   => $slug,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! empty( $query->get_posts() ) ) {
			return new \WP_Error(
				'duplicate_font_face',
				/* translators: %s: Font face slug. */
				sprintf( __( 'A font face with slug "%s" already exists.', 'woocommerce' ), $slug ),
			);
		}

		// Validate the font face settings.

		$validation_error = self::validate_font_face( $font_face );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		$parsed_font_face['fontFamily'] = addslashes( \WP_Font_Utils::sanitize_font_family( $font_face['fontFamily'] ) );
		$parsed_font_face['fontStyle']  = sanitize_text_field( $font_face['fontStyle'] );
		$parsed_font_face['fontWeight'] = sanitize_text_field( $font_face['fontWeight'] );
		$file                           = self::download_file( $font_face['src'] );

		$uploaded_file = self::handle_font_file_upload( $file );

		$parsed_font_face['src']     = self::sanitize_src( $uploaded_file['url'] );
		$parsed_font_face['preview'] = esc_url_raw( $font_face['preview'] );

		// Insert the font face.
		wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_parent'  => $parent_font_family_id,
				'post_title'   => $slug,
				'post_name'    => sanitize_title( $slug ),
				'post_content' => wp_json_encode( $parsed_font_face ),
				'post_status'  => 'publish',
			)
		);
	}


	/**
	 * Validates a font face.
	 *
	 * @param array $font_face The font face settings.
	 * @return \WP_Error|null The error if the font family is invalid, null otherwise.
	 */
	private static function validate_font_face( $font_face ) {
		// Validate the font face family name.
		if ( empty( $font_face['fontFamily'] ) ) {
			return new \WP_Error(
				'invalid_font_face_font_family',
				__( 'The font face family name is required.', 'woocommerce' ),
			);
		}

		// Validate the font face font style.
		if ( empty( $font_face['fontStyle'] ) ) {
			return new \WP_Error(
				'invalid_font_face_font_style',
				__( 'The font face font style is required.', 'woocommerce' ),
			);
		}

		// Validate the font face weight.
		if ( empty( $font_face['fontWeight'] ) ) {
			return new \WP_Error(
				'invalid_font_face_font_weight',
				__( 'The font face weight is required.', 'woocommerce' ),
			);
		}

		// Validate the font face src.
		if ( empty( $font_face['src'] ) ) {
			return new \WP_Error(
				'invalid_font_face_src',
				__( 'The font face src is required.', 'woocommerce' ),
			);
		}
	}

}
