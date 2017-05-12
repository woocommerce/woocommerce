<?php
/**
 * Handles Batch CSV export.
 *
 * Based on https://pippinsplugins.com/batch-processing-for-big-data/
 *
 * @author   Automattic
 * @category Admin
 * @package  WooCommerce/Export
 * @version  3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_CSV_Exporter', false ) ) {
	include_once( WC_ABSPATH . 'includes/export/abstract-wc-csv-exporter.php' );
}

/**
 * WC_CSV_Exporter Class.
 */
abstract class WC_CSV_Batch_Exporter extends WC_CSV_Exporter {

	/**
	 * The file being exported to.
	 */
	protected $file;

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
		$upload_dir         = wp_upload_dir();
		$this->file         = trailingslashit( $upload_dir['basedir'] ) . 'wc-' . $this->export_type . '-export.csv';
		$this->column_names = $this->get_default_column_names();
	}

	/**
	 * Get the file contents.
	 *
	 * @return string
	 */
	public function get_file() {
		$file = '';
		if ( @file_exists( $this->file ) ) {
			$file = @file_get_contents( $this->file );
		} else {
			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );
		}
		return $file;
	}

	/**
	 * Do the export.
	 */
	public function export() {
		$this->send_headers();
		$this->send_content( $this->get_file() );
		@unlink( $this->file );
		die();
	}

	/**
	 * Generate the CSV file.
	 */
	public function generate_file() {
		if ( 1 === $this->get_page() ) {
			@unlink( $this->file );
		}
		$this->prepare_data_to_export();
		$this->write_csv_data( $this->get_csv_data() );
	}

	/**
	 * Write data to the file.
	 *
	 * @param  string $data
	 */
	protected function write_csv_data( $data ) {
		$file = $this->get_file();

		// Add columns when finished.
		if ( 100 === $this->get_percent_complete() ) {
			$file = $this->export_column_headers() . $file;
		}

		$file .= $data;
		@file_put_contents( $this->file, $file );
	}

	/**
	 * Get page.
	 *
	 * @return int
	 */
	public function get_page() {
		return $this->page;
	}

	/**
	 * Set page.
	 *
	 * @param int $page
	 */
	public function set_page( $page ) {
		$this->page = absint( $page );
	}

	/**
	 * Get count of records exported.
	 *
	 * @return int
	 */
	public function get_total_exported() {
		return ( $this->get_page() * $this->get_limit() ) + $this->exported_row_count;
	}

	/**
	 * Get total % complete.
	 *
	 * @return int
	 */
	public function get_percent_complete() {
		return $this->total_rows ? floor( ( $this->get_total_exported() / $this->total_rows ) * 100 ) : 100;
	}
}
