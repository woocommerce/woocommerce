<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;

/**
 * Helper for CSV import functionality.
 *
 * @since 9.3.0
 */
class CSVUploadHelper {

	/**
	 * Name (inside the uploads folder) to use for the CSV import directory.
	 *
	 * @return string
	 */
	protected function get_import_subdir_name(): string {
		return 'wc-imports';
	}

	/**
	 * Returns the full path to the CSV import directory within the uploads folder.
	 * It will attempt to create the directory if it doesn't exist.
	 *
	 * @param bool $create TRUE to attempt to create the directory. FALSE otherwise.
	 * @return string
	 * @throws \Exception In case the upload directory doesn't exits or can't be created.
	 */
	public function get_import_dir( bool $create = true ): string {
		$wp_upload_dir = wp_upload_dir( null, $create );
		if ( $wp_upload_dir['error'] ) {
			throw new \Exception( esc_html( $wp_upload_dir['error'] ) );
		}

		$upload_dir = trailingslashit( $wp_upload_dir['basedir'] ) . $this->get_import_subdir_name();
		if ( $create ) {
			FilesystemUtil::mkdir_p_not_indexable( $upload_dir );
		}
		return $upload_dir;
	}

	/**
	 * Handles a CSV file upload.
	 *
	 * @param string     $import_type        Type of upload or context.
	 * @param string     $files_index        $_FILES index that contains the file to upload.
	 * @param array|null $allowed_mime_types List of allowed MIME types.
	 * @return array {
	 *     Details for the uploaded file.
	 *
	 *     @type int    $id   Attachment ID.
	 *     @type string $file Full path to uploaded file.
	 * }
	 *
	 * @throws \Exception In case of error.
	 */
	public function handle_csv_upload( string $import_type, string $files_index = 'import', ?array $allowed_mime_types = null ): array {
		$import_type = sanitize_key( $import_type );
		if ( ! $import_type ) {
			throw new \Exception( 'Import type is invalid.' );
		}

		if ( ! $allowed_mime_types ) {
			$allowed_mime_types = array(
				'csv' => 'text/csv',
				'txt' => 'text/plain',
			);
		}

		$file = $_FILES[ $files_index ] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			throw new \Exception( esc_html__( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.', 'woocommerce' ) );
		}

		if ( ! function_exists( 'wp_import_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/import.php';
		}

		// Make sure upload dir exists.
		$this->get_import_dir();

		// Add prefix.
		$file['name'] = $import_type . '-' . $file['name'];

		$overrides_callback = function ( $overrides_ ) use ( $allowed_mime_types ) {
			$overrides_['test_form'] = false;
			$overrides_['test_type'] = true;
			$overrides_['mimes']     = $allowed_mime_types;
			return $overrides_;
		};

		add_filter( 'upload_dir', array( $this, 'override_upload_dir' ) );
		add_filter( 'wp_unique_filename', array( $this, 'override_unique_filename' ), 0, 2 );
		add_filter( 'wp_handle_upload_overrides', $overrides_callback, 999 );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'remove_txt_from_uploaded_file' ), 0 );

		$orig_files_import = $_FILES['import'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		$_FILES['import']  = $file;  // wp_import_handle_upload() expects the file to be in 'import'.

		$upload = wp_import_handle_upload();

		remove_filter( 'upload_dir', array( $this, 'override_upload_dir' ) );
		remove_filter( 'wp_unique_filename', array( $this, 'override_unique_filename' ), 0 );
		remove_filter( 'wp_handle_upload_overrides', $overrides_callback, 999 );
		remove_filter( 'wp_handle_upload_prefilter', array( $this, 'remove_txt_from_uploaded_file' ), 0 );

		if ( $orig_files_import ) {
			$_FILES['import'] = $orig_files_import;
		} else {
			unset( $_FILES['import'] );
		}

		if ( ! empty( $upload['error'] ) ) {
			throw new \Exception( esc_html( $upload['error'] ) );
		}

		if ( ! wc_is_file_valid_csv( $upload['file'], false ) ) {
			wp_delete_attachment( $file['id'], true );
			throw new \Exception( esc_html__( 'Invalid file type for a CSV import.', 'woocommerce' ) );
		}

		return $upload;
	}

	/**
	 * Hooked onto 'upload_dir' to override the default upload directory for a CSV upload.
	 *
	 * @param array $uploads WP upload dir details.
	 * @return array
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function override_upload_dir( $uploads ): array {
		$new_subdir = '/' . $this->get_import_subdir_name();

		$uploads['path']   = $uploads['basedir'] . $new_subdir;
		$uploads['url']    = $uploads['baseurl'] . $new_subdir;
		$uploads['subdir'] = $new_subdir;

		return $uploads;
	}

	/**
	 * Adds a random string to the name of an uploaded CSV file to make it less discoverable. Hooked onto 'wp_unique_filename'.
	 *
	 * @param string $filename File name.
	 * @param string $ext      File extension.
	 * @return string
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function override_unique_filename( string $filename, string $ext ): string {
		$length = min( 10, 255 - strlen( $filename ) - 1 );
		if ( 1 < $length ) {
			$suffix   = strtolower( wp_generate_password( $length, false, false ) );
			$filename = substr( $filename, 0, strlen( $filename ) - strlen( $ext ) ) . '-' . $suffix . $ext;
		}

		return $filename;
	}

	/**
	 * `wp_import_handle_upload()` appends .txt to any file name. This function is hooked onto 'wp_handle_upload_prefilter'
	 * to remove those extra characters.
	 *
	 * @param array $file File details in the form of a $_FILES entry.
	 * @return array Modified file details.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function remove_txt_from_uploaded_file( array $file ): array {
		$file['name'] = substr( $file['name'], 0, -4 );
		return $file;
	}
}
