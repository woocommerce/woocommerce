<?php
/**
 * REST API Performance indicators controller
 *
 * Handles requests to the /reports/store-performance endpoint.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports Performance indicators controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Reports_Controller
 */
class WC_Admin_REST_Reports_Performance_Indicators_Controller extends WC_REST_Reports_Controller {

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
	protected $rest_base = 'reports/performance-indicators';

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param array $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args           = array();
		$args['before'] = $request['before'];
		$args['after']  = $request['after'];
		$args['stats']  = $request['stats'];
		return $args;
	}

	/**
	 * Get all allowed stats that can be returned from this endpoint.
	 *
	 * @return array
	 */
	public function get_allowed_stats() {
		global $wp_rest_server;

		$request       = new WP_REST_Request( 'GET', '/wc/v4/reports' );
		$response      = rest_do_request( $request );
		$endpoints     = $response->get_data();
		$allowed_stats = array();
		if ( 200 !== $response->get_status() ) {
			return new WP_Error( 'woocommerce_reports_performance_indicators_result_failed', __( 'Sorry, fetching performance indicators failed.', 'wc-admin' ) );
		}

		foreach ( $endpoints as $endpoint ) {
			if ( '/stats' === substr( $endpoint['slug'], -6 ) ) {
				$request  = new WP_REST_Request( 'OPTIONS', '/wc/v4/reports/' . $endpoint['slug'] );
				$response = rest_do_request( $request );
				$data     = $response->get_data();
				$prefix   = substr( $endpoint['slug'], 0, -6 );

				if ( empty( $data['schema']['properties']['totals']['properties'] ) ) {
					continue;
				}

				foreach ( $data['schema']['properties']['totals']['properties'] as $property_key => $schema_info ) {
					$allowed_stats[] = $prefix . '/' . $property_key;
				}
			}
		}

		/**
		 * Filter the list of allowed stats that can be returned via the performance indiciator endpoint.
		 *
		 * @param array $allowed_stats The list of allowed stats.
		 */
		return apply_filters( 'woocommerce_admin_performance_indicators_allowed_stats', $allowed_stats );
	}

	/**
	 * Get all reports.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$allowed_stats = $this->get_allowed_stats();

		if ( is_wp_error( $allowed_stats ) ) {
			return $allowed_stats;
		}

		$query_args = $this->prepare_reports_query( $request );
		if ( empty( $query_args['stats'] ) ) {
			return new WP_Error( 'woocommerce_reports_performance_indicators_empty_query', __( 'A list of stats to query must be provided.', 'wc-admin' ), 400 );
		}

		$stats = array();
		foreach ( $query_args['stats'] as $stat_request ) {
			$is_error = false;

			$pieces   = explode( '/', $stat_request );
			$endpoint = $pieces[0];
			$stat     = $pieces[1];

			if ( ! in_array( $stat_request, $allowed_stats ) ) {
				continue;
			}

			/**
			 * Filter the list of allowed endpoints, so that data can be loaded from extensions rather than core.
			 * These should be in the format of slug => path. Example: `bookings` => `/wc-bookings/v1/reports/bookings/stats`.
			 *
			 * @param array $endpoints The list of allowed endpoints.
			 */
			$stats_endpoints = apply_filters( 'woocommerce_admin_performance_indicators_stats_endpoints', array() );
			if ( ! empty( $stats_endpoints [ $endpoint ] ) ) {
				$request_url = $stats_endpoints [ $endpoint ];
			} else {
				$request_url = '/wc/v4/reports/' . $endpoint . '/stats';
			}

			$request = new WP_REST_Request( 'GET', $request_url );
			$request->set_param( 'before', $query_args['before'] );
			$request->set_param( 'after', $query_args['after'] );

			$response = rest_do_request( $request );
			$data     = $response->get_data();

			if ( 200 !== $response->get_status() || empty( $data['totals'][ $stat ] ) ) {
				$stats[] = (object) array(
					'stat'  => $stat_request,
					'value' => null,
				);
				continue;
			}

			$stats[] = (object) array(
				'stat'  => $stat_request,
				'value' => $data['totals'][ $stat ],
			);
		}

		$objects = array();
		foreach ( $stats as $stat ) {
			$data      = $this->prepare_item_for_response( $stat, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', count( $stats ) );
		$response->header( 'X-WP-TotalPages', 1 );

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );

		return $response;
	}

	/**
	 * Prepare a report object for serialization.
	 *
	 * @param stdClass        $stat_data    Report data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $stat_data, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $stat_data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $data ) );

		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_performance_indicators', $response, $stat_data, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Admin_Reports_Query $object Object data.
	 * @return array
	 */
	protected function prepare_links( $object ) {
		$pieces   = explode( '/', $object->stat );
		$endpoint = $pieces[0];
		$stat     = $pieces[1];

		$links = array(
			'report' => array(
				'href' => rest_url( sprintf( '/%s/reports/%s/stats', $this->namespace, $endpoint ) ),
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
			'title'      => 'report_performance_indicator',
			'type'       => 'object',
			'properties' => array(
				'stat'             => array(
					'description' => __( 'Unique identifier for the resource.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'value'      => array(
					'description' => __( 'Value of the stat. Returns null if the stat does not exist or cannot be loaded.', 'wc-admin' ),
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
		$allowed_stats     = $this->get_allowed_stats();
		if ( is_wp_error( $allowed_stats ) ) {
			$allowed_stats = __( 'There was an issue loading the report endpoints', 'wc-admin' );
		} else {
			$allowed_stats = implode( ', ', $allowed_stats );
		}

		$params            = array();
		$params['context'] = $this->get_context_param( array( 'default' => 'view' ) );
		$params['stats']   = array(
			'description'       => sprintf(
				/* translators: Allowed values is a list of stat endpoints. */
				__( 'Limit response to specific report stats. Allowed values: %s.', 'wc-admin' ),
				$allowed_stats
			),
			'type'              => 'array',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type'   => 'string',
			),
		);
		$params['after']   = array(
			'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['before'] = array(
			'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'wc-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $params;
	}
}
