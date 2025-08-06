<?php
/**
 * Handles Batch JSON export.
 *
 * @package  WooCommerce\Export
 * @version  3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_JSON_Exporter', false ) ) {
	require_once WC_ABSPATH . 'includes/export/abstract-wc-json-exporter.php';
}

/**
 * WC_JSON_Batch_Exporter Class.
 */
abstract class WC_JSON_Batch_Exporter extends WC_JSON_Exporter {

	/**
	 * Page being exported
	 *
	 * @var integer
	 */
	protected $page = 1;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->column_names = $this->get_default_column_names();
	}

	/**
	 * Get file path to export to.
	 *
	 * @return string
	 */
	protected function get_file_path() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . $this->get_filename();
	}

	/**
	 * Get the file contents.
	 *
	 * @since 3.1.0
	 * @return string
	 */
	public function get_file() {
		$file_path = $this->get_file_path();
		error_log( 'JSON Export: Trying to read file: ' . $file_path );
		$file = '';
		if ( @file_exists( $file_path ) ) {
			$file = @file_get_contents( $file_path );
			error_log( 'JSON Export: File exists, content length: ' . strlen( $file ) );
		} else {
			error_log( 'JSON Export: File does not exist, creating empty file' );
			@file_put_contents( $file_path, '' );
			@chmod( $file_path, 0664 );
		}
		return $file;
	}

	/**
	 * Serve the file and remove once sent to the client.
	 *
	 * @since 3.1.0
	 */
	public function export() {
		$file_content = $this->get_file();
		error_log( 'JSON Export: File content length for download: ' . strlen( $file_content ) );
		
		// Check if compression is requested
		if ( $this->should_compress() ) {
			$file_content = $this->create_zip_file();
			$this->filename = str_replace( array( '.json', '.csv' ), '.zip', $this->filename );
		}
		
		$this->send_headers();
		$this->send_content( $file_content );
		@unlink( $this->get_file_path() );
		if ( $this->should_compress() ) {
			@unlink( $this->get_zip_file_path() );
		}
		die();
	}
	
	/**
	 * Check if compression is requested.
	 *
	 * @return bool
	 */
	protected function should_compress() {
		return ! empty( $_GET['compress'] ) || ! empty( $_POST['compress'] );
	}
	
	/**
	 * Create ZIP file and return its content.
	 *
	 * @return string|false
	 */
	protected function create_zip_file() {
		if ( ! class_exists( 'ZipArchive' ) ) {
			error_log( 'JSON Export: ZipArchive class not available' );
			return $this->get_file();
		}
		
		$zip_file_path = $this->get_zip_file_path();
		$original_file_path = $this->get_file_path();
		
		$zip = new ZipArchive();
		if ( $zip->open( $zip_file_path, ZipArchive::CREATE ) !== TRUE ) {
			error_log( 'JSON Export: Could not create ZIP file: ' . $zip_file_path );
			return $this->get_file();
		}
		
		$original_filename = basename( $original_file_path );
		$zip->addFile( $original_file_path, $original_filename );
		$zip->close();
		
		if ( file_exists( $zip_file_path ) ) {
			$zip_content = file_get_contents( $zip_file_path );
			error_log( 'JSON Export: ZIP file created, size: ' . strlen( $zip_content ) . ' bytes' );
			return $zip_content;
		}
		
		error_log( 'JSON Export: ZIP file creation failed' );
		return $this->get_file();
	}
	
	/**
	 * Get ZIP file path.
	 *
	 * @return string
	 */
	protected function get_zip_file_path() {
		return str_replace( array( '.json', '.csv' ), '.zip', $this->get_file_path() );
	}

	/**
	 * Generate the JSON file.
	 *
	 * @since 3.1.0
	 */
	public function generate_file() {
		if ( 1 === $this->get_page() ) {
			@unlink( $this->get_file_path() );
			// Initialize file with opening bracket
			error_log( 'JSON Export: Initializing file for page 1' );
			$this->write_json_data( "[\n" );
		}
		
		$this->prepare_data_to_export();
		$json_data = $this->get_json_data();
		
		error_log( 'JSON Export: Page ' . $this->get_page() . ' raw data length: ' . strlen( $json_data ) );
		
		// Add comma separator for non-first pages
		if ( $this->get_page() > 1 && ! empty( $json_data ) ) {
			$json_data = ",\n" . $json_data;
		}
		
		error_log( 'JSON Export: Page ' . $this->get_page() . ' final data length: ' . strlen( $json_data ) );
		$this->write_json_data( $json_data );
		
		// Close JSON array when finished
		if ( 100 === $this->get_percent_complete() ) {
			$this->write_json_data( "\n]" );
		}
	}

	/**
	 * Write data to the file.
	 *
	 * @since 3.1.0
	 * @param string $data Data.
	 */
	protected function write_json_data( $data ) {
		$file_path = $this->get_file_path();
		error_log( 'JSON Export: Writing to file: ' . $file_path . ' (data length: ' . strlen( $data ) . ')' );
		
		// Create the file if it doesn't exist
		if ( ! file_exists( $file_path ) ) {
			$upload_dir = dirname( $file_path );
			if ( ! file_exists( $upload_dir ) ) {
				wp_mkdir_p( $upload_dir );
			}
			touch( $file_path );
			chmod( $file_path, 0664 );
		}
		
		// Check if writable
		if ( ! is_writeable( $file_path ) ) {
			wc_get_logger()->error(
				sprintf(
					/* translators: %s is file path. */
					__( 'Unable to write to %s during JSON export. Please check file permissions.', 'woocommerce' ),
					esc_html( $file_path )
				)
			);
			error_log( 'JSON Export: File is not writable: ' . $file_path );
			return false;
		}

		$fopen_mode = apply_filters( 'woocommerce_json_exporter_fopen_mode', 'a+' );
		$fp         = fopen( $file_path, $fopen_mode );

		if ( $fp ) {
			$bytes_written = fwrite( $fp, $data );
			fclose( $fp );
			error_log( 'JSON Export: Written ' . $bytes_written . ' bytes to file' );
		} else {
			error_log( 'JSON Export: Failed to open file for writing' );
		}
	}

	/**
	 * Get page.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_page() {
		return $this->page;
	}

	/**
	 * Set page.
	 *
	 * @since 3.1.0
	 * @param int $page Page Nr.
	 */
	public function set_page( $page ) {
		$this->page = absint( $page );
	}

	/**
	 * Get count of records exported.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_total_exported() {
		return ( ( $this->get_page() - 1 ) * $this->get_limit() ) + $this->exported_row_count;
	}

	/**
	 * Get total % complete.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_percent_complete() {
		return $this->total_rows ? (int) floor( ( $this->get_total_exported() / $this->total_rows ) * 100 ) : 100;
	}
}