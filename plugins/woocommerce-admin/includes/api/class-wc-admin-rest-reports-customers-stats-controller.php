<?php
/**
 * REST API Reports customers stats controller
 *
 * Handles requests to the /reports/customers/stats endpoint.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports customers stats controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Reports_Controller
 */
class WC_Admin_REST_Reports_Customers_Stats_Controller extends WC_REST_Reports_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/customers/stats';

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param array $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args                        = array();
		$args['registered_before']   = $request['registered_before'];
		$args['registered_after']    = $request['registered_after'];
		$args['match']               = $request['match'];
		$args['name']                = $request['name'];
		$args['username']            = $request['username'];
		$args['email']               = $request['email'];
		$args['country']             = $request['country'];
		$args['last_active_before']  = $request['last_active_before'];
		$args['last_active_after']   = $request['last_active_after'];
		$args['orders_count_min']    = $request['orders_count_min'];
		$args['orders_count_max']    = $request['orders_count_max'];
		$args['total_spend_min']     = $request['total_spend_min'];
		$args['total_spend_max']     = $request['total_spend_max'];
		$args['avg_order_value_min'] = $request['avg_order_value_min'];
		$args['avg_order_value_max'] = $request['avg_order_value_max'];
		$args['last_order_before']   = $request['last_order_before'];
		$args['last_order_after']    = $request['last_order_after'];

		$between_params_numeric    = array( 'orders_count', 'total_spend', 'avg_order_value' );
		$normalized_numeric_params = WC_Admin_Reports_Interval::normalize_between_params( $request, $between_params_numeric, false );
		$between_params_date       = array( 'last_active', 'registered' );
		$normalized_date_params    = WC_Admin_Reports_Interval::normalize_between_params( $request, $between_params_date, true );
		$args                      = array_merge( $args, $normalized_numeric_params, $normalized_date_params );

		return $args;
	}

	/**
	 * Get all reports.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$query_args      = $this->prepare_reports_query( $request );
		$customers_query = new WC_Admin_Reports_Customers_Stats_Query( $query_args );
		$report_data     = $customers_query->get_data();
		$out_data        = array(
			'totals'    => $report_data,
			// TODO: is this needed? the single element array tricks the isReportDataEmpty() selector.
			'intervals' => array( (object) array() ),
		);

		return rest_ensure_response( $out_data );
	}

	/**
	 * Prepare a report object for serialization.
	 *
	 * @param Array           $report  Report data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		$data = $report;

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_customers_stats', $response, $report, $request );
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		// TODO: should any of these be 'indicator's?
		$totals = array(
			'customers_count'     => array(
				'description' => __( 'Number of customers.', 'wc-admin' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'avg_orders_count'    => array(
				'description' => __( 'Average number of orders.', 'wc-admin' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'avg_total_spend'     => array(
				'description' => __( 'Average total spend per customer.', 'wc-admin' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'format'      => 'currency',
			),
			'avg_avg_order_value' => array(
				'description' => __( 'Average AOV per customer.', 'wc-admin' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'format'      => 'currency',
			),
		);

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_customers_stats',
			'type'       => 'object',
			'properties' => array(
				'totals'    => array(
					'description' => __( 'Totals data.', 'wc-admin' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => $totals,
				),
				'intervals' => array( // TODO: remove this?
					'description' => __( 'Reports data grouped by intervals.', 'wc-admin' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'interval'       => array(
								'description' => __( 'Type of interval.', 'wc-admin' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'enum'        => array( 'day', 'week', 'month', 'year' ),
							),
							'date_start'     => array(
								'description' => __( "The date the report start, in the site's timezone.", 'wc-admin' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_start_gmt' => array(
								'description' => __( 'The date the report start, as GMT.', 'wc-admin' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_end'       => array(
								'description' => __( "The date the report end, in the site's timezone.", 'wc-admin' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_end_gmt'   => array(
								'description' => __( 'The date the report end, as GMT.', 'wc-admin' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'subtotals'      => array(
								'description' => __( 'Interval subtotals.', 'wc-admin' ),
								'type'        => 'object',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'properties'  => $totals,
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                            = array();
		$params['context']                 = $this->get_context_param( array( 'default' => 'view' ) );
		$params['registered_before']       = array(
			'description'       => __( 'Limit response to objects registered before (or at) a given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['registered_after']        = array(
			'description'       => __( 'Limit response to objects registered after (or at) a given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['match']                   = array(
			'description'       => __( 'Indicates whether all the conditions should be true for the resulting set, or if any one of them is sufficient. Match affects the following parameters: status_is, status_is_not, product_includes, product_excludes, coupon_includes, coupon_excludes, customer, categories', 'wc-admin' ),
			'type'              => 'string',
			'default'           => 'all',
			'enum'              => array(
				'all',
				'any',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['name']                    = array(
			'description'       => __( 'Limit response to objects with a specfic customer name.', 'wc-admin' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['username']                = array(
			'description'       => __( 'Limit response to objects with a specfic username.', 'wc-admin' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['email']                   = array(
			'description'       => __( 'Limit response to objects equal to an email.', 'wc-admin' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['country']                 = array(
			'description'       => __( 'Limit response to objects with a specfic country.', 'wc-admin' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['last_active_before']      = array(
			'description'       => __( 'Limit response to objects last active before (or at) a given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['last_active_after']       = array(
			'description'       => __( 'Limit response to objects last active after (or at) a given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['last_active_between']     = array(
			'description'       => __( 'Limit response to objects last active between two given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'array',
			'validate_callback' => array( 'WC_Admin_Reports_Interval', 'rest_validate_between_date_arg' ),
		);
		$params['registered_before']       = array(
			'description'       => __( 'Limit response to objects registered before (or at) a given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['registered_after']        = array(
			'description'       => __( 'Limit response to objects registered after (or at) a given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['registered_between']      = array(
			'description'       => __( 'Limit response to objects last active between two given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'array',
			'validate_callback' => array( 'WC_Admin_Reports_Interval', 'rest_validate_between_date_arg' ),
		);
		$params['orders_count_min']        = array(
			'description'       => __( 'Limit response to objects with an order count greater than or equal to given integer.', 'wc-admin' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orders_count_max']        = array(
			'description'       => __( 'Limit response to objects with an order count less than or equal to given integer.', 'wc-admin' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orders_count_between']    = array(
			'description'       => __( 'Limit response to objects with an order count between two given integers.', 'wc-admin' ),
			'type'              => 'array',
			'validate_callback' => array( 'WC_Admin_Reports_Interval', 'rest_validate_between_numeric_arg' ),
		);
		$params['total_spend_min']         = array(
			'description'       => __( 'Limit response to objects with a total order spend greater than or equal to given number.', 'wc-admin' ),
			'type'              => 'number',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['total_spend_max']         = array(
			'description'       => __( 'Limit response to objects with a total order spend less than or equal to given number.', 'wc-admin' ),
			'type'              => 'number',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['total_spend_between']     = array(
			'description'       => __( 'Limit response to objects with a total order spend between two given numbers.', 'wc-admin' ),
			'type'              => 'array',
			'validate_callback' => array( 'WC_Admin_Reports_Interval', 'rest_validate_between_numeric_arg' ),
		);
		$params['avg_order_value_min']     = array(
			'description'       => __( 'Limit response to objects with an average order spend greater than or equal to given number.', 'wc-admin' ),
			'type'              => 'number',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['avg_order_value_max']     = array(
			'description'       => __( 'Limit response to objects with an average order spend less than or equal to given number.', 'wc-admin' ),
			'type'              => 'number',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['avg_order_value_between'] = array(
			'description'       => __( 'Limit response to objects with an average order spend between two given numbers.', 'wc-admin' ),
			'type'              => 'array',
			'validate_callback' => array( 'WC_Admin_Reports_Interval', 'rest_validate_between_numeric_arg' ),
		);
		$params['last_order_before']       = array(
			'description'       => __( 'Limit response to objects with last order before (or at) a given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['last_order_after']        = array(
			'description'       => __( 'Limit response to objects with last order after (or at) a given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
