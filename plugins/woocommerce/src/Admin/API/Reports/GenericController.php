<?php
namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_REST_Response;

/**
 * {@see WC_REST_Reports_Controller WC REST API Reports Controller} extended to be shared as a generic base for all Analytics reports controllers.
 *
 * Handles pagination HTTP headers and links, basic, conventional params.
 * Does all the REST API plumbing as `WC_REST_Controller`.
 *
 *
 * Minimalistic example:
 * <pre><code class="language-php">class MyController extends GenericController {
 *     /** Route of your new REST endpoint. &ast;/
 *     protected $rest_base = 'reports/my-thing';
 *     /**
 *      * Provide JSON schema for the response item.
 *      * @override WC_REST_Reports_Controller::get_item_schema()
 *      &ast;/
 *     public function get_item_schema() {
 *         $schema = array(
 *             '$schema'    => 'http://json-schema.org/draft-04/schema#',
 *             'title'      => 'report_my_thing',
 *             'type'       => 'object',
 *             'properties' => array(
 *                 'product_id' => array(
 *                     'type'        => 'integer',
 *                     'readonly'    => true,
 *                     'context'     => array( 'view', 'edit' ),
 *                 'description' => __( 'Product ID.', 'my_extension' ),
 *                 ),
 *             ),
 *         );
 *         // Add additional fields from `get_additional_fields` method and apply `woocommerce_rest_' . $schema['title'] . '_schema` filter.
 *         return $this->add_additional_fields_schema( $schema );
 *     }
 * }
 * </code></pre>
 *
 * The above Controller will get the data from a {@see DataStore data store} registered as `$rest_base` (`reports/my-thing`).
 * (To change this behavior, override the `get_datastore_data()` method).
 *
 * To use the controller, please register it with the filter `woocommerce_admin_rest_controllers` filter.
 *
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
	 * @param \WP_REST_Request        $request   Request data.
	 * @param \WP_REST_Response|array $response  Response data.
	 * @param int                     $total     Total results.
	 * @param int                     $page      Current page.
	 * @param int                     $max_pages Total amount of pages.
	 * @return \WP_REST_Response
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
	 * Get data from `{$this->rest_base}` store, based on the given query vars.
	 *
	 * @throws Exception When the data store is not found {@see WC_Data_Store WC_Data_Store}.
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$data_store = \WC_Data_Store::load( $this->rest_base );
		return $data_store->get_data( $query_args );
	}

	/**
	 * Get the query params definition for collections.
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
	 * Get the report data.
	 *
	 * Prepares query params, fetches the report data from the Query object,
	 * prepares it for the response, and packs it into the convention-conforming response object.
	 *
	 * @throws \WP_Error When the queried data is invalid.
	 * @param \WP_REST_Request $request Request data.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$query_args  = $this->prepare_reports_query( $request );
		$report_data = $this->get_datastore_data( $query_args );

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
	 * This method is called by `get_items` to prepare a single report data item for serialization.
	 * Calls `add_additional_fields_to_object` and `filter_response_by_context`,
	 * then wpraps the data with `rest_ensure_response`.
	 *
	 * You can extend it to add or filter some fields.
	 *
	 * @override WP_REST_Posts_Controller::prepare_item_for_response()
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
