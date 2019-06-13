<?php
/**
 * REST API Reports coupons controller
 *
 * Handles requests to the /reports/coupons endpoint.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports coupons controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Reports_Controller
 */
class WC_Admin_REST_Reports_Coupons_Controller extends WC_REST_Reports_Controller {

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
	protected $rest_base = 'reports/coupons';

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param array $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args                  = array();
		$args['before']        = $request['before'];
		$args['after']         = $request['after'];
		$args['page']          = $request['page'];
		$args['per_page']      = $request['per_page'];
		$args['orderby']       = $request['orderby'];
		$args['order']         = $request['order'];
		$args['coupons']       = (array) $request['coupons'];
		$args['extended_info'] = $request['extended_info'];
		return $args;
	}

	/**
	 * Get all reports.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$query_args    = $this->prepare_reports_query( $request );
		$coupons_query = new WC_Admin_Reports_Coupons_Query( $query_args );
		$report_data   = $coupons_query->get_data();

		$data = array();

		foreach ( $report_data->data as $coupons_data ) {
			$item   = $this->prepare_item_for_response( $coupons_data, $request );
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
	 * @param stdClass        $report  Report data.
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
		return apply_filters( 'woocommerce_rest_prepare_report_coupons', $response, $report, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Reports_Query $object Object data.
	 * @return array
	 */
	protected function prepare_links( $object ) {
		$links = array(
			'coupon' => array(
				'href' => rest_url( sprintf( '/%s/coupons/%d', $this->namespace, $object['coupon_id'] ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_coupons',
			'type'       => 'object',
			'properties' => array(
				'coupon_id'     => array(
					'description' => __( 'Coupon ID.', 'woocommerce-admin' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'amount'        => array(
					'description' => __( 'Net discount amount.', 'woocommerce-admin' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'orders_count'  => array(
					'description' => __( 'Number of orders.', 'woocommerce-admin' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'extended_info' => array(
					'code'             => array(
						'type'        => 'string',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Coupon code.', 'woocommerce-admin' ),
					),
					'date_created'     => array(
						'type'        => 'date-time',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Coupon creation date.', 'woocommerce-admin' ),
					),
					'date_created_gmt' => array(
						'type'        => 'date-time',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Coupon creation date in GMT.', 'woocommerce-admin' ),
					),
					'date_expires'     => array(
						'type'        => 'date-time',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Coupon expiration date.', 'woocommerce-admin' ),
					),
					'date_expires_gmt' => array(
						'type'        => 'date-time',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Coupon expiration date in GMT.', 'woocommerce-admin' ),
					),
					'discount_type'    => array(
						'type'        => 'string',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'enum'        => array_keys( wc_get_coupon_types() ),
						'description' => __( 'Coupon discount type.', 'woocommerce-admin' ),
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
		$params                  = array();
		$params['context']       = $this->get_context_param( array( 'default' => 'view' ) );
		$params['page']          = array(
			'description'       => __( 'Current page of the collection.', 'woocommerce-admin' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);
		$params['per_page']      = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce-admin' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['after']         = array(
			'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'woocommerce-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['before']        = array(
			'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'woocommerce-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order']         = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce-admin' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orderby']       = array(
			'description'       => __( 'Sort collection by object attribute.', 'woocommerce-admin' ),
			'type'              => 'string',
			'default'           => 'coupon_id',
			'enum'              => array(
				'coupon_id',
				'code',
				'amount',
				'orders_count',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['coupons']       = array(
			'description'       => __( 'Limit result set to coupons assigned specific coupon IDs.', 'woocommerce-admin' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['extended_info'] = array(
			'description'       => __( 'Add additional piece of info about each coupon to the report.', 'woocommerce-admin' ),
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'wc_string_to_bool',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
