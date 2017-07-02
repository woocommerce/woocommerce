<?php
/**
 * Handles CSV export.
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
 * WC_CSV_Exporter Class.
 */
abstract class WC_CSV_Exporter {

	/**
	 * Type of export used in filter names.
	 * @var string
	 */
	protected $export_type = '';

	/**
	 * Batch limit.
	 * @var integer
	 */
	protected $limit = 50;

	/**
	 * Number exported.
	 * @var integer
	 */
	protected $exported_row_count = 0;

	/**
	 * Raw data to export.
	 * @var array
	 */
	protected $row_data = array();

	/**
	 * Total rows to export.
	 * @var integer
	 */
	protected $total_rows = 0;

	/**
	 * Columns ids and names.
	 * @var array
	 */
	protected $column_names = array();

	/**
	 * List of columns to export, or empty for all.
	 * @var array
	 */
	protected $columns_to_export = array();

	/**
	 * Prepare data that will be exported.
	 */
	abstract function prepare_data_to_export();

	/**
	 * Return an array of supported column names and ids.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	public function get_column_names() {
		return apply_filters( "woocommerce_{$this->export_type}_export_column_names", $this->column_names, $this );
	}

	/**
	 * Set column names.
	 *
	 * @since 3.1.0
	 * @param array $column_names
	 */
	public function set_column_names( $column_names ) {
		$this->column_names = array();

		foreach ( $column_names as $column_id => $column_name ) {
			$this->column_names[ wc_clean( $column_id ) ] = wc_clean( $column_name );
		}
	}

	/**
	 * Return an array of columns to export.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	public function get_columns_to_export() {
		return $this->columns_to_export;
	}

	/**
	 * Set columns to export.
	 *
	 * @since 3.1.0
	 * @param array $column_names
	 */
	public function set_columns_to_export( $columns ) {
		$this->columns_to_export = array_map( 'wc_clean', $columns );
	}

	/**
	 * See if a column is to be exported or not.
	 *
	 * @since 3.1.0
	 * @param  string $column_id
	 * @return boolean
	 */
	public function is_column_exporting( $column_id ) {
		$column_id         = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
		$columns_to_export = $this->get_columns_to_export();

		if ( empty( $columns_to_export ) ) {
			return true;
		}

		if ( in_array( $column_id, $columns_to_export ) || 'meta' === $column_id ) {
			return true;
		}

		return false;
	}

	/**
	 * Return default columns.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	public function get_default_column_names() {
		return array();
	}

	/**
	 * Do the export.
	 *
	 * @since 3.1.0
	 */
	public function export() {
		$this->prepare_data_to_export();
		$this->send_headers();
		$this->send_content( chr( 239 ) . chr( 187 ) . chr( 191 ) . $this->export_column_headers() . $this->get_csv_data() );
		die();
	}

	/**
	 * Set the export headers.
	 *
	 * @since 3.1.0
	 */
	public function send_headers() {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable();
		}
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 );
		}
		@ini_set( 'zlib.output_compression', 'Off' );
		@ini_set( 'output_buffering', 'Off' );
		@ini_set( 'output_handler', '' );
		ignore_user_abort( true );
		wc_set_time_limit( 0 );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $this->get_filename() );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}

	/**
	 * Generate and return a filename.
	 *
	 * @return string
	 */
	public function get_filename() {
		return sanitize_file_name( 'wc-' . $this->export_type . '-export-' . date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) . '.csv' );
	}

	/**
	 * Set the export content.
	 *
	 * @since 3.1.0
	 */
	public function send_content( $csv_data ) {
		echo $csv_data;
	}

	/**
	 * Get CSV data for this export.
	 *
	 * @since 3.1.0
	 * @return string
	 */
	protected function get_csv_data() {
		return $this->export_rows();
	}

	/**
	 * Export column headers in CSV format.
	 *
	 * @since 3.1.0
	 * @return string
	 */
	protected function export_column_headers() {
		$columns    = $this->get_column_names();
		$export_row = array();
		$buffer     = fopen( 'php://output', 'w' );
		ob_start();

		foreach ( $columns as $column_id => $column_name ) {
			if ( ! $this->is_column_exporting( $column_id ) ) {
				continue;
			}
			$export_row[] = $column_name;
		}

		fputcsv( $buffer, $export_row );

		return ob_get_clean();
	}

	/**
	 * Get data that will be exported.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	protected function get_data_to_export() {
		return $this->row_data;
	}

	/**
	 * Export rows in CSV format.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	protected function export_rows() {
		$data   = $this->get_data_to_export();
		$buffer = fopen( 'php://output', 'w' );
		ob_start();

		array_walk( $data, array( $this, 'export_row' ), $buffer );

		return apply_filters( "woocommerce_{$this->export_type}_export_rows", ob_get_clean(), $this );
	}

	/**
	 * Export rows to an array ready for the CSV.
	 *
	 * @since 3.1.0
	 * @param array $row_data
	 */
	protected function export_row( $row_data, $key, $buffer ) {
		$columns    = $this->get_column_names();
		$export_row = array();

		foreach ( $columns as $column_id => $column_name ) {
			if ( ! $this->is_column_exporting( $column_id ) ) {
				continue;
			}
			if ( isset( $row_data[ $column_id ] ) ) {
				$export_row[] = $this->format_data( $row_data[ $column_id ] );
			} else {
				$export_row[] = '';
			}
		}

		fputcsv( $buffer, $export_row );
		++ $this->exported_row_count;
	}

	/**
	 * Get batch limit.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_limit() {
		return $this->limit;
	}

	/**
	 * Set batch limit.
	 *
	 * @since 3.1.0
	 * @param int $limit
	 */
	public function set_limit( $limit ) {
		$this->limit = absint( $limit );
	}

	/**
	 * Get count of records exported.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_total_exported() {
		return $this->exported_row_count;
	}

	/**
	 * Escape a string to be used in a CSV context
	 *
	 * Malicious input can inject formulas into CSV files, opening up the possibility
	 * for phishing attacks and disclosure of sensitive information.
	 *
	 * Additionally, Excel exposes the ability to launch arbitrary commands through
	 * the DDE protocol.
	 *
	 * @see http://www.contextis.com/resources/blog/comma-separated-vulnerabilities/
	 * @see https://hackerone.com/reports/72785
	 *
	 * @since 3.1.0
	 * @param string $field CSV field to escape
	 * @return string
	 */
	public function escape_data( $data ) {
		$active_content_triggers = array( '=', '+', '-', '@' );

		if ( in_array( mb_substr( $data, 0, 1 ), $active_content_triggers, true ) ) {
			$data = "'" . $data;
		}

		return $data;
	}

	/**
	 * Format and escape data ready for the CSV file.
	 *
	 * @since 3.1.0
	 * @param  string $data
	 * @return string
	 */
	public function format_data( $data ) {
		if ( ! is_scalar( $data ) ) {
			if ( is_a( $data, 'WC_Datetime' ) ) {
				$data = $data->date( 'Y-m-d G:i:s' );
			} else {
				$data = ''; // Not supported.
			}
		} elseif ( is_bool( $data ) ) {
			$data = $data ? 1 : 0;
		}

		$data     = (string) urldecode( $data );
		$encoding = mb_detect_encoding( $data, 'UTF-8, ISO-8859-1', true );
		$data     = 'UTF-8' === $encoding ? $data : utf8_encode( $data );
		return $this->escape_data( $data );
	}

	/**
	 * Format term ids to names.
	 *
	 * @since 3.1.0
	 * @param  array $term_ids
	 * @param  string $taxonomy
	 * @return array
	 */
	public function format_term_ids( $term_ids, $taxonomy ) {
		$term_ids = wp_parse_id_list( $term_ids );

		if ( ! count( $term_ids ) ) {
			return '';
		}

		$formatted_terms = array();

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			foreach ( $term_ids as $term_id ) {
				$formatted_term = array();
				$ancestor_ids   = array_reverse( get_ancestors( $term_id, $taxonomy ) );

				foreach ( $ancestor_ids as $ancestor_id ) {
					$term = get_term( $ancestor_id, $taxonomy );
					if ( $term && ! is_wp_error( $term ) ) {
						$formatted_term[] = $term->name;
					}
				}

				$term = get_term( $term_id, $taxonomy );

				if ( $term && ! is_wp_error( $term ) ) {
					$formatted_term[] = $term->name;
				}

				$formatted_terms[] = implode( ' > ', $formatted_term );
			}
		} else {
			foreach ( $term_ids as $term_id ) {
				$term = get_term( $term_id, $taxonomy );

				if ( $term && ! is_wp_error( $term ) ) {
					$formatted_terms[] = $term->name;
				}
			}
		}

		return implode( ', ', $formatted_terms );
	}
}
