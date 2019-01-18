<?php
/**
 * REST API Reports customers controller
 *
 * Handles requests to the /reports/customers endpoint.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports customers controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Reports_Controller
 */
class WC_Admin_REST_Reports_Customers_Controller extends WC_REST_Reports_Controller {

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
	protected $rest_base = 'reports/customers';

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
		$args['page']                = $request['page'];
		$args['per_page']            = $request['per_page'];
		$args['order']               = $request['order'];
		$args['orderby']             = $request['orderby'];
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

		$between_params = array( 'orders_count', 'total_spend', 'avg_order_value' );
		$normalized     = WC_Admin_Reports_Interval::normalize_between_params( $request, $between_params );
		$args           = array_merge( $args, $normalized );

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
		$customers_query = new WC_Admin_Reports_Customers_Query( $query_args );
		$report_data     = $customers_query->get_data();

		$data = array();

		foreach ( $report_data->data as $customer_data ) {
			$item   = $this->prepare_item_for_response( $customer_data, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', (int) $report_data->total );
		$response->header( 'X-WP-TotalPages', (int) $report_data->pages );

		$page      = $report_data->page_no;
		$max_pages = $report_data->pages;
		$base      = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );
		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Prepare a report object for serialization.
	 *
	 * @param array           $report  Report data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		$context                      = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data                         = $this->add_additional_fields_to_object( $report, $request );
		$data['date_registered_gmt']  = wc_rest_prepare_date_response( $data['date_registered'] );
		$data['date_registered']      = wc_rest_prepare_date_response( $data['date_registered'], false );
		$data['date_last_active_gmt'] = wc_rest_prepare_date_response( $data['date_last_active'] );
		$data['date_last_active']     = wc_rest_prepare_date_response( $data['date_last_active'], false );
		$data                         = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $report ) );
		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_customers', $response, $report, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param array $object Object data.
	 * @return array
	 */
	protected function prepare_links( $object ) {
		if ( empty( $object['user_id'] ) ) {
			return array();
		}

		return array(
			'customer' => array(
				'href' => rest_url( sprintf( '/%s/customers/%d', $this->namespace, $object['user_id'] ) ),
			),
		);
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_customers',
			'type'       => 'object',
			'properties' => array(
				'customer_id'          => array(
					'description' => __( 'Customer ID.', 'wc-admin' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'user_id'              => array(
					'description' => __( 'User ID.', 'wc-admin' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'                 => array(
					'description' => __( 'Name.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'username'             => array(
					'description' => __( 'Username.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'country'              => array(
					'description' => __( 'Country.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'city'                 => array(
					'description' => __( 'City.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'postcode'             => array(
					'description' => __( 'Postal code.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_registered'      => array(
					'description' => __( 'Date registered.', 'wc-admin' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_registered_gmt'  => array(
					'description' => __( 'Date registered GMT.', 'wc-admin' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_last_active'     => array(
					'description' => __( 'Date last active.', 'wc-admin' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_last_active_gmt' => array(
					'description' => __( 'Date last active GMT.', 'wc-admin' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'orders_count'         => array(
					'description' => __( 'Order count.', 'wc-admin' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_spend'          => array(
					'description' => __( 'Total spend.', 'wc-admin' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'avg_order_value'      => array(
					'description' => __( 'Avg order value.', 'wc-admin' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
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
		$params['page']                    = array(
			'description'       => __( 'Current page of the collection.', 'wc-admin' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);
		$params['per_page']                = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'wc-admin' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order']               = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'wc-admin' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orderby']             = array(
			'description'       => __( 'Sort collection by object attribute.', 'wc-admin' ),
			'type'              => 'string',
			'default'           => 'date_registered',
			'enum'              => array(
				'username',
				'name',
				'country',
				'city',
				'postcode',
				'date_registered',
				'date_last_active',
				'orders_count',
				'total_spend',
				'avg_order_value',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['match']               = array(
			'description'       => __( 'Indicates whether all the conditions should be true for the resulting set, or if any one of them is sufficient. Match affects the following parameters: status_is, status_is_not, product_includes, product_excludes, coupon_includes, coupon_excludes, customer, categories', 'wc-admin' ),
			'type'              => 'string',
			'default'           => 'all',
			'enum'              => array(
				'all',
				'any',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['name']                = array(
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
		$params['registered_before']  = array(
			'description'       => __( 'Limit response to objects registered before (or at) a given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['registered_after']   = array(
			'description'       => __( 'Limit response to objects registered after (or at) a given ISO8601 compliant datetime.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orders_count_min']    = array(
			'description'       => __( 'Limit response to objects with an order count greater than or equal to given integer.', 'wc-admin' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orders_count_max']    = array(
			'description'       => __( 'Limit response to objects with an order count less than or equal to given integer.', 'wc-admin' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orders_count_between']     = array(
			'description'       => __( 'Limit response to objects with an order count between two given integers.', 'wc-admin' ),
			'type'              => 'array',
			'validate_callback' => array( 'WC_Admin_Reports_Interval', 'rest_validate_between_arg' ),
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
			'validate_callback' => array( 'WC_Admin_Reports_Interval', 'rest_validate_between_arg' ),
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
			'validate_callback' => array( 'WC_Admin_Reports_Interval', 'rest_validate_between_arg' ),
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
