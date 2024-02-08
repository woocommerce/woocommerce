<?php
/**
 * FontFamily class file
 */

namespace Automattic\WooCommerce\Internal\Font;

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
	protected static function sanitize_src( $value ) {
		$value = ltrim( $value );
		return false === wp_http_validate_url( $value ) ? (string) $value : sanitize_url( $value );
	}

	/**
	 * Handles file upload error.
	 *
	 * @since 6.5.0
	 *
	 * @param array  $file    File upload data.
	 * @param string $message Error message from wp_handle_upload().
	 * @return WP_Error WP_Error object.
	 */
	private static function handle_font_file_upload_error( $file, $message ) {
		$status = 500;
		$code   = 'rest_font_upload_unknown_error';

		if ( __( 'Sorry, you are not allowed to upload this file type.' ) === $message ) {
			$status = 400;
			$code   = 'rest_font_upload_invalid_file_type';
		}

		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * Handles the upload of a font file using wp_handle_upload().
	 *
	 * @since 6.5.0
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
	 * @param array $settings The font face settings.
	 * @param int   $parent_font_family_id The parent font family ID.
	 * @return \WP_Error|\WP_Post The inserted font face post or an error if the font face already exists.
	 */
	public static function insert_font_face( array $settings, int $parent_font_family_id ) {
		$parsed_font_face['fontFamily'] = addslashes( \WP_Font_Utils::sanitize_font_family( $settings['fontFamily'] ) );
		$parsed_font_face['fontStyle']  = sanitize_text_field( $settings['fontStyle'] );
		$parsed_font_face['fontWeight'] = sanitize_text_field( $settings['fontWeight'] );
		$file                           = self::download_file( $settings['src'] );

		$uploaded_file = self::handle_font_file_upload( $file );

		$parsed_font_face['src']     = self::sanitize_src( $uploaded_file['url'] );
		$parsed_font_face['preview'] = sanitize_url( $settings['preview'] );

		$slug = \WP_Font_Utils::get_font_face_slug( $settings );

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
}
