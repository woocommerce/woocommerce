<?php
/**
 * Handles JSON export.
 *
 * @package  WooCommerce\Export
 * @version  3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_JSON_Exporter Class.
 */
abstract class WC_JSON_Exporter {

	/**
	 * Type of export used in filter names.
	 *
	 * @var string
	 */
	protected $export_type = '';

	/**
	 * Filename to export to.
	 *
	 * @var string
	 */
	protected $filename = 'wc-export.json';

	/**
	 * Batch limit.
	 *
	 * @var integer
	 */
	protected $limit = 1000;

	/**
	 * Number exported.
	 *
	 * @var integer
	 */
	protected $exported_row_count = 0;

	/**
	 * Raw data to export.
	 *
	 * @var array
	 */
	protected $row_data = array();

	/**
	 * Total rows to export.
	 *
	 * @var integer
	 */
	protected $total_rows = 0;

	/**
	 * Columns ids and names.
	 *
	 * @var array
	 */
	protected $column_names = array();

	/**
	 * List of columns to export, or empty for all.
	 *
	 * @var array
	 */
	protected $columns_to_export = array();

	/**
	 * Prepare data that will be exported.
	 */
	abstract public function prepare_data_to_export();

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
	 * @param array $column_names Column names array.
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
	 * @param array $columns Columns array.
	 */
	public function set_columns_to_export( $columns ) {
		$this->columns_to_export = array_map( 'wc_clean', $columns );
	}

	/**
	 * See if a column is to be exported or not.
	 *
	 * @since 3.1.0
	 * @param  string $column_id ID of the column being exported.
	 * @return boolean
	 */
	public function is_column_exporting( $column_id ) {
		$column_id         = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
		$columns_to_export = $this->get_columns_to_export();

		if ( empty( $columns_to_export ) ) {
			return true;
		}

		if ( in_array( $column_id, $columns_to_export, true ) || 'meta' === $column_id ) {
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
		$this->send_content( $this->get_json_data() );
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
		wc_nocache_headers();
		
		$filename = $this->get_filename();
		if ( strpos( $filename, '.zip' ) !== false ) {
			header( 'Content-Type: application/zip' );
		} else {
			header( 'Content-Type: application/json; charset=utf-8' );
		}
		
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}

	/**
	 * Set filename to export to.
	 *
	 * @param  string $filename Filename to export to.
	 */
	public function set_filename( $filename ) {
		$this->filename = sanitize_file_name( str_replace( '.json', '', $filename ) . '.json' );
	}

	/**
	 * Generate and return a filename.
	 *
	 * @return string
	 */
	public function get_filename() {
		return sanitize_file_name( apply_filters( "woocommerce_{$this->export_type}_export_get_filename", $this->filename ) );
	}

	/**
	 * Set the export content.
	 *
	 * @since 3.1.0
	 * @param string $json_data All JSON content.
	 */
	public function send_content( $json_data ) {
		echo $json_data;
	}

	/**
	 * Get JSON data for this export.
	 *
	 * @since 3.1.0
	 * @return string
	 */
	protected function get_json_data() {
		$data = $this->get_data_to_export();
		error_log( 'JSON Export: Data count: ' . count( $data ) );
		$json_objects = array();
		
		foreach ( $data as $row_data ) {
			$json_object = array();
			$columns = $this->get_column_names();
			
			foreach ( $columns as $column_id => $column_name ) {
				if ( ! $this->is_column_exporting( $column_id ) ) {
					continue;
				}
				
				if ( isset( $row_data[ $column_id ] ) ) {
					$json_object[ $column_id ] = $this->format_data( $row_data[ $column_id ] );
				} else {
					$json_object[ $column_id ] = null;
				}
			}
			
			$json_objects[] = $json_object;
		}
		
		if ( method_exists( $this, 'get_page' ) ) {
			// For batch export, return individual objects without array wrapper
			$result = implode( ",\n", array_map( array( $this, 'json_encode_object' ), $json_objects ) );
			error_log( 'JSON Export: Page ' . $this->get_page() . ' result length: ' . strlen( $result ) );
			return $result;
		}
		
		// For non-batch export, return complete JSON array
		$result = wp_json_encode( $json_objects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		error_log( 'JSON Export: Non-batch result length: ' . strlen( $result ) );
		return $result;
	}

	/**
	 * JSON encode a single object.
	 *
	 * @param array $object Object to encode.
	 * @return string
	 */
	protected function json_encode_object( $object ) {
		return wp_json_encode( $object, JSON_UNESCAPED_UNICODE );
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
	 * Get batch limit.
	 *
	 * @since 3.1.0
	 * @return int
	 */
	public function get_limit() {
		return apply_filters( "woocommerce_{$this->export_type}_export_batch_limit", $this->limit, $this );
	}

	/**
	 * Set batch limit.
	 *
	 * @since 3.1.0
	 * @param int $limit Limit to export.
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
	 * Format data for JSON output.
	 *
	 * @since 3.1.0
	 * @param  mixed $data Data to format.
	 * @return mixed
	 */
	public function format_data( $data ) {
		if ( is_a( $data, 'WC_Datetime' ) ) {
			return $data->date( 'Y-m-d H:i:s' );
		} elseif ( is_bool( $data ) ) {
			return $data;
		} elseif ( is_numeric( $data ) ) {
			return is_float( $data ) ? (float) $data : (int) $data;
		} elseif ( is_string( $data ) ) {
			// Convert comma-separated values to arrays
			if ( strpos( $data, ', ' ) !== false ) {
				return array_map( 'trim', explode( ', ', $data ) );
			}
			return $data;
		}
		
		return $data;
	}

	/**
	 * Format term ids to names and return as array.
	 *
	 * @since 3.1.0
	 * @param  array  $term_ids Term IDs to format.
	 * @param  string $taxonomy Taxonomy name.
	 * @return array
	 */
	public function format_term_ids( $term_ids, $taxonomy ) {
		$term_ids = wp_parse_id_list( $term_ids );

		if ( ! count( $term_ids ) ) {
			return array();
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

		return $formatted_terms;
	}
}