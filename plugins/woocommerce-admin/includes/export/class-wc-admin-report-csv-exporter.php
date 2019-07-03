<?php
/**
 * Handles reports CSV export batches.
 *
 * @package WooCommerce/Export
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_CSV_Batch_Exporter', false ) ) {
	include_once WC_ABSPATH . 'includes/export/abstract-wc-csv-batch-exporter.php';
}

/**
 * WC_Admin_Report_CSV_Exporter Class.
 */
class WC_Admin_Report_CSV_Exporter extends WC_CSV_Batch_Exporter {
	/**
	 * Type of report being exported.
	 *
	 * @var string
	 */
	protected $report_type;

	/**
	 * Parameters for the report query.
	 *
	 * @var array
	 */
	protected $report_args;

	/**
	 * REST controller for the report.
	 *
	 * @var WC_REST_Reports_Controller
	 */
	protected $controller;

	/**
	 * Constructor.
	 *
	 * @param string $type Report type. E.g. 'customers'.
	 * @param array  $args Report parameters.
	 */
	public function __construct( $type = false, $args = array() ) {
		parent::__construct();

		if ( ! empty( $type ) ) {
			$this->set_report_type( $type );
			$this->set_column_names( $this->get_report_columns() );
		}

		if ( ! empty( $args ) ) {
			$this->set_report_args( $args );
		}
	}

	/**
	 * Setter for report type.
	 *
	 * @param string $type The report type. E.g. customers.
	 */
	public function set_report_type( $type ) {
		$this->report_type = $type;
		$this->export_type = "admin_{$type}_report";
		$this->filename    = "wc-{$type}-report-export";
		$this->controller  = $this->map_report_controller();
	}

	/**
	 * Setter for report args.
	 *
	 * @param array $args The report args.
	 */
	public function set_report_args( $args ) {
		// Use our own internal limit and include all extended info.
		$report_args = array_merge(
			$args,
			array(
				'per_page'      => $this->get_limit(),
				'extended_info' => true,
			)
		);

		// Should this happen externally?
		if ( isset( $report_args['page'] ) ) {
			$this->set_page( $report_args['page'] );
		}

		$this->report_args = $report_args;
	}

	/**
	 * Get a REST controller instance for the report type.
	 *
	 * @return bool|WC_REST_Reports_Controller Report controller instance or boolean false on error.
	 */
	protected function map_report_controller() {
		$controller_map = array(
			'products'   => 'WC_Admin_REST_Reports_Products_Controller',
			'variations' => 'WC_Admin_REST_Reports_Variations_Controller',
			'orders'     => 'WC_Admin_REST_Reports_Orders_Controller',
			'categories' => 'WC_Admin_REST_Reports_Categories_Controller',
			'taxes'      => 'WC_Admin_REST_Reports_Taxes_Controller',
			'coupons'    => 'WC_Admin_REST_Reports_Coupons_Controller',
			'stock'      => 'WC_Admin_REST_Reports_Stock_Controller',
			'downloads'  => 'WC_Admin_REST_Reports_Downloads_Controller',
			'customers'  => 'WC_Admin_REST_Reports_Customers_Controller',
		);

		if ( isset( $controller_map[ $this->report_type ] ) ) {
			// Load the controllers if accessing outside the REST API.
			if ( ! did_action( 'rest_api_init' ) ) {
				do_action( 'rest_api_init' );
			}

			return new $controller_map[ $this->report_type ]();
		}

		// Should this do something else?
		return false;
	}

	/**
	 * Get the report columns from the schema.
	 *
	 * @return array Array of report column names.
	 */
	protected function get_report_columns() {
		$report_columns = array();
		$report_schema  = $this->controller->get_item_schema();

		if ( isset( $report_schema['properties'] ) ) {
			foreach ( $report_schema['properties'] as $column_name => $column_info ) {
				// Expand extended info columns into export.
				if ( 'extended_info' === $column_name ) {
					// Remove columns with questionable CSV values, like markup.
					$extended_info  = array_diff( array_keys( $column_info ), array( 'image' ) );
					$report_columns = array_merge( $report_columns, $extended_info );
				} else {
					$report_columns[] = $column_name;
				}
			}
		}

		return $report_columns;
	}

	/**
	 * Get total % complete.
	 *
	 * Forces an int from parent::get_percent_complete(), which can return a float.
	 *
	 * @return int Percent complete.
	 */
	public function get_percent_complete() {
		return intval( parent::get_percent_complete() );
	}

	/**
	 * Get total number of rows in export.
	 *
	 * @return int Number of rows to export.
	 */
	public function get_total_rows() {
		return $this->total_rows;
	}

	/**
	 * Prepare data for export.
	 */
	public function prepare_data_to_export() {
		$request  = new WP_REST_Request( 'GET', "/wc/v4/reports/{$this->report_type}" );
		$params   = $this->controller->get_collection_params();
		$defaults = array();

		foreach ( $params as $arg => $options ) {
			if ( isset( $options['default'] ) ) {
				$defaults[ $arg ] = $options['default'];
			}
		}

		$request->set_default_params( $defaults );
		$request->set_query_params( $this->report_args );

		$response         = $this->controller->get_items( $request );
		$report_meta      = $response->get_headers();
		$report_data      = $response->get_data();
		$this->total_rows = $report_meta['X-WP-Total'];
		$this->row_data   = array_map( array( $this, 'generate_row_data' ), $report_data );
	}

	/**
	 * Take a report item and generate row data from it for export.
	 *
	 * @param object $item Report item data.
	 * @return array CSV row data.
	 */
	protected function generate_row_data( $item ) {
		$columns = $this->get_column_names();
		$row     = array();

		// Expand extended info.
		if ( isset( $item['extended_info'] ) ) {
			// Pull extended info property from report item object.
			$extended_info = (array) $item['extended_info'];
			unset( $item['extended_info'] );

			// Merge extended info columns into report item object.
			$item = array_merge( $item, $extended_info );
		}

		foreach ( $columns as $column_id => $column_name ) {
			$value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : null;

			if ( has_filter( "woocommerce_export_{$this->export_type}_column_{$column_name}" ) ) {
				// Filter for 3rd parties.
				$value = apply_filters( "woocommerce_export_{$this->export_type}_column_{$column_name}", '', $item );

			} elseif ( is_callable( array( $this, "get_column_value_{$column_name}" ) ) ) {
				// Handle special columns which don't map 1:1 to item data.
				$value = $this->{"get_column_value_{$column_name}"}( $item, $this->export_type );

			} elseif ( ! is_scalar( $value ) ) {
				// Ensure that the value is somewhat readable in CSV.
				$value = wp_json_encode( $value );
			}

			$row[ $column_id ] = $value;
		}

		return apply_filters( "woocommerce_export_{$this->export_type}_row_data", $row, $item );
	}
}
