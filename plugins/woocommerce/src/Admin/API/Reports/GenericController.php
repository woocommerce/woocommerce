<?php
namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_REST_Response;

/**
 * WC REST API Reports controller extended
 * to be shared as a generic base for all Analytics controllers.
 *
 * @internal
 * @extends WC_REST_Reports_Controller
 */
abstract class GenericController extends \WC_REST_Reports_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';


	/**
	 * Add pagination headers and links.
	 *
	 * @param WP_REST_Request        $request   Request data.
	 * @param WP_REST_Response|array $response  Response data.
	 * @param int                    $total     Total results.
	 * @param int                    $page      Current page.
	 * @param int                    $max_pages Total amount of pages.
	 * @return WP_REST_Response
	 */
	public function add_pagination_headers( $request, $response, int $total, int $page, int $max_pages ) {
		$response = rest_ensure_response( $response );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $max_pages );

		$base = add_query_arg(
			$request->get_query_params(),
			rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) )
		);

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
	 * Forwards a Query constructor,
	 * to be able to customize Query class for a specific report.
	 *
	 * By default it creates `GenericQuery` with the rest base as name.
	 *
	 * @param array $query_args Set of args to be forwarded to the constructor.
	 * @return GenericQuery
	 */
	protected function construct_query( $query_args ) {
		return new GenericQuery( $query_args, $this->rest_base );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                        = array();
		$params['context']             = $this->get_context_param( array( 'default' => 'view' ) );
		$params['page']                = array(
			'description'       => __( 'Current page of the collection.', 'woocommerce' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);
		$params['per_page']            = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['after']               = array(
			'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'woocommerce' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['before']              = array(
			'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'woocommerce' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order']               = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orderby']             = array(
			'description'       => __( 'Sort collection by object attribute.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'date',
			'enum'              => array(
				'date',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['force_cache_refresh'] = array(
			'description'       => __( 'Force retrieval of fresh data instead of from the cache.', 'woocommerce' ),
			'type'              => 'boolean',
			'sanitize_callback' => 'wp_validate_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}


	/**
	 * Get all reports.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$query_args  = $this->prepare_reports_query( $request );
		$query       = $this->construct_query( $query_args );
		$report_data = $query->get_data();

		if ( is_wp_error( $report_data ) ) {
			return $report_data;
		}

		if ( ! isset( $report_data->data ) || ! isset( $report_data->page_no ) || ! isset( $report_data->pages ) ) {
			return new \WP_Error( 'woocommerce_rest_reports_invalid_response', __( 'Invalid response from data store.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		$out_data = array();

		foreach ( $report_data->data as $datum ) {
			$item       = $this->prepare_item_for_response( $datum, $request );
			$out_data[] = $this->prepare_response_for_collection( $item );
		}

		return $this->add_pagination_headers(
			$request,
			$out_data,
			(int) $report_data->total,
			(int) $report_data->page_no,
			(int) $report_data->pages
		);
	}

	/**
	 * Prepare a report data item for serialization.
	 *
	 * @param mixed           $report_item Report data item as returned from Data Store.
	 * @param WP_REST_Request $request     Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report_item, $request ) {
		$data = $report_item;

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		return rest_ensure_response( $data );
	}

	/**
	 * Maps query arguments from the REST request, to be fed to Query.
	 *
	 * `WP_REST_Request` does not expose a method to return all params covering defaults,
	 * as it does for `$request['param']` accessor.
	 * Therefore, we re-implement defaults resolution.
	 *
	 * @param \WP_REST_Request $request Full request object.
	 * @return array Simplified array of params.
	 */
	protected function prepare_reports_query( $request ) {
		$args = wp_parse_args(
			array_intersect_key(
				$request->get_query_params(),
				$this->get_collection_params()
			),
			$request->get_default_params()
		);

		return $args;
	}
}
