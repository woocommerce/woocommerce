<?php
/**
 * REST API Performance indicators controller
 *
 * Handles requests to the /reports/store-performance endpoint.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API\Reports\PerformanceIndicators;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports Performance indicators controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Reports_Controller
 */
class Controller extends \WC_REST_Reports_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/performance-indicators';

	/**
	 * Contains a list of endpoints by report slug.
	 *
	 * @var array
	 */
	protected $endpoints = array();

	/**
	 * Contains a list of allowed stats.
	 *
	 * @var array
	 */
	protected $allowed_stats = array();

	/**
	 * Contains a list of stat labels.
	 *
	 * @var array
	 */
	protected $labels = array();

	/**
	 * Contains a list of endpoints by url.
	 *
	 * @var array
	 */
	protected $urls = array();

	/**
	 * Register the routes for reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/allowed',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_allowed_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_allowed_item_schema' ),
			)
		);
	}

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
	 * Get information such as allowed stats, stat labels, and endpoint data from stats reports.
	 *
	 * @return WP_Error|True
	 */
	private function get_indicator_data() {
		// Data already retrieved.
		if ( ! empty( $this->endpoints ) && ! empty( $this->labels ) && ! empty( $this->allowed_stats ) ) {
			return true;
		}

		$request  = new \WP_REST_Request( 'GET', '/wc-analytics/reports' );
		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== $response->get_status() ) {
			return new \WP_Error( 'woocommerce_analytics_performance_indicators_result_failed', __( 'Sorry, fetching performance indicators failed.', 'woocommerce' ) );
		}

		$endpoints     = $response->get_data();
		$allowed_stats = array();

		foreach ( $endpoints as $endpoint ) {
			if ( '/stats' === substr( $endpoint['slug'], -6 ) ) {
				$request  = new \WP_REST_Request( 'OPTIONS', $endpoint['path'] );
				$response = rest_do_request( $request );

				if ( is_wp_error( $response ) ) {
					return $response;
				}

				$data = $response->get_data();

				$prefix = substr( $endpoint['slug'], 0, -6 );

				if ( empty( $data['schema']['properties']['totals']['properties'] ) ) {
					continue;
				}

				foreach ( $data['schema']['properties']['totals']['properties'] as $property_key => $schema_info ) {
					if ( empty( $schema_info['indicator'] ) || ! $schema_info['indicator'] ) {
						continue;
					}

					$stat            = $prefix . '/' . $property_key;
					$allowed_stats[] = $stat;
					$stat_label      = empty( $schema_info['title'] ) ? $schema_info['description'] : $schema_info['title'];

					$this->labels[ $stat ]  = trim( preg_replace( '/\W+/', ' ', $stat_label ) );
					$this->formats[ $stat ] = isset( $schema_info['format'] ) ? $schema_info['format'] : 'number';
				}

				$this->endpoints[ $prefix ] = $endpoint['path'];
				$this->urls[ $prefix ]      = $endpoint['_links']['report'][0]['href'];
			}
		}

		$this->allowed_stats = $allowed_stats;
		return true;
	}

	/**
	 * Returns a list of allowed performance indicators.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return array|WP_Error
	 */
	public function get_allowed_items( $request ) {
		$indicator_data = $this->get_indicator_data();
		if ( is_wp_error( $indicator_data ) ) {
			return $indicator_data;
		}

		$data = array();
		foreach ( $this->allowed_stats as $stat ) {
			$pieces = $this->get_stats_parts( $stat );
			$report = $pieces[0];
			$chart  = $pieces[1];
			$data[] = (object) array(
				'stat'  => $stat,
				'chart' => $chart,
				'label' => $this->labels[ $stat ],
			);
		}

		usort( $data, array( $this, 'sort' ) );

		$objects = array();
		foreach ( $data as $item ) {
			$prepared  = $this->prepare_item_for_response( $item, $request );
			$objects[] = $this->prepare_response_for_collection( $prepared );
		}

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', count( $data ) );
		$response->header( 'X-WP-TotalPages', 1 );

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );

		return $response;
	}

	/**
	 * Sorts the list of stats. Sorted by custom arrangement.
	 *
	 * @see https://github.com/woocommerce/woocommerce-admin/issues/1282
	 * @param object $a First item.
	 * @param object $b Second item.
	 * @return order
	 */
	public function sort( $a, $b ) {
		/**
		 * Custom ordering for store performance indicators.
		 *
		 * @see https://github.com/woocommerce/woocommerce-admin/issues/1282
		 * @param array $indicators A list of ordered indicators.
		 */
		$stat_order = apply_filters(
			'woocommerce_rest_report_sort_performance_indicators',
			array(
				'revenue/total_sales',
				'revenue/net_revenue',
				'orders/orders_count',
				'orders/avg_order_value',
				'products/items_sold',
				'revenue/refunds',
				'coupons/orders_count',
				'coupons/amount',
				'taxes/total_tax',
				'taxes/order_tax',
				'taxes/shipping_tax',
				'revenue/shipping',
				'downloads/download_count',
			)
		);

		$a = array_search( $a->stat, $stat_order );
		$b = array_search( $b->stat, $stat_order );

		if ( false === $a && false === $b ) {
			return 0;
		} elseif ( false === $a ) {
			return 1;
		} elseif ( false === $b ) {
			return -1;
		} else {
			return $a - $b;
		}
	}

	/**
	 * Get all reports.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$indicator_data = $this->get_indicator_data();
		if ( is_wp_error( $indicator_data ) ) {
			return $indicator_data;
		}

		$query_args = $this->prepare_reports_query( $request );
		if ( empty( $query_args['stats'] ) ) {
			return new \WP_Error( 'woocommerce_analytics_performance_indicators_empty_query', __( 'A list of stats to query must be provided.', 'woocommerce' ), 400 );
		}

		$stats = array();
		foreach ( $query_args['stats'] as $stat ) {
			$is_error = false;

			$pieces = $this->get_stats_parts( $stat );
			$report = $pieces[0];
			$chart  = $pieces[1];

			if ( ! in_array( $stat, $this->allowed_stats ) ) {
				continue;
			}

			$request_url = $this->endpoints[ $report ];
			$request     = new \WP_REST_Request( 'GET', $request_url );
			$request->set_param( 'before', $query_args['before'] );
			$request->set_param( 'after', $query_args['after'] );

			$response = rest_do_request( $request );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$data   = $response->get_data();
			$format = $this->formats[ $stat ];
			$label  = $this->labels[ $stat ];

			if ( 200 !== $response->get_status() || ! isset( $data['totals'][ $chart ] ) ) {
				$stats[] = (object) array(
					'stat'   => $stat,
					'chart'  => $chart,
					'label'  => $label,
					'format' => $format,
					'value'  => null,
				);
				continue;
			}

			$stats[] = (object) array(
				'stat'   => $stat,
				'chart'  => $chart,
				'label'  => $label,
				'format' => $format,
				'value'  => $data['totals'][ $chart ],
			);
		}

		usort( $stats, array( $this, 'sort' ) );

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
	 * @param \Automattic\WooCommerce\Admin\API\Reports\Query $object Object data.
	 * @return array
	 */
	protected function prepare_links( $object ) {
		$pieces   = $this->get_stats_parts( $object->stat );
		$endpoint = $pieces[0];
		$stat     = $pieces[1];
		$url      = $this->urls[ $endpoint ];

		$links = array(
			'api'    => array(
				'href' => rest_url( $this->endpoints[ $endpoint ] ),
			),
			'report' => array(
				'href' => ! empty( $url ) ? $url : '',
			),
		);

		return $links;
	}

	/**
	 * Returns the endpoint part of a stat request (prefix) and the actual stat total we want.
	 * To allow extensions to namespace (example: fue/emails/sent), we break on the last forward slash.
	 *
	 * @param string $full_stat A stat request string like orders/avg_order_value or fue/emails/sent.
	 * @return array Containing the prefix (endpoint) and suffix (stat).
	 */
	private function get_stats_parts( $full_stat ) {
		$endpoint = substr( $full_stat, 0, strrpos( $full_stat, '/' ) );
		$stat     = substr( $full_stat, ( strrpos( $full_stat, '/' ) + 1 ) );
		return array(
			$endpoint,
			$stat,
		);
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$indicator_data = $this->get_indicator_data();
		if ( is_wp_error( $indicator_data ) ) {
			$allowed_stats = array();
		} else {
			$allowed_stats = $this->allowed_stats;
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_performance_indicator',
			'type'       => 'object',
			'properties' => array(
				'stat'   => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'enum'        => $allowed_stats,
				),
				'chart'  => array(
					'description' => __( 'The specific chart this stat referrers to.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'label'  => array(
					'description' => __( 'Human readable label for the stat.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'format' => array(
					'description' => __( 'Format of the stat.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'enum'        => array( 'number', 'currency' ),
				),
				'value'  => array(
					'description' => __( 'Value of the stat. Returns null if the stat does not exist or cannot be loaded.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get schema for the list of allowed performance indicators.
	 *
	 * @return array $schema
	 */
	public function get_public_allowed_item_schema() {
		$schema = $this->get_public_item_schema();
		unset( $schema['properties']['value'] );
		unset( $schema['properties']['format'] );
		return $schema;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$indicator_data = $this->get_indicator_data();
		if ( is_wp_error( $indicator_data ) ) {
			$allowed_stats = __( 'There was an issue loading the report endpoints', 'woocommerce' );
		} else {
			$allowed_stats = implode( ', ', $this->allowed_stats );
		}

		$params            = array();
		$params['context'] = $this->get_context_param( array( 'default' => 'view' ) );
		$params['stats']   = array(
			'description'       => sprintf(
				/* translators: Allowed values is a list of stat endpoints. */
				__( 'Limit response to specific report stats. Allowed values: %s.', 'woocommerce' ),
				$allowed_stats
			),
			'type'              => 'array',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'string',
				'enum' => $this->allowed_stats,
			),
			'default'           => $this->allowed_stats,
		);
		$params['after']   = array(
			'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'woocommerce' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['before']  = array(
			'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'woocommerce' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $params;
	}
}
