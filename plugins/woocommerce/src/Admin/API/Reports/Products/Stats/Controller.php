<?php
/**
 * REST API Reports products stats controller
 *
 * Handles requests to the /reports/products/stats endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Products\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\GenericStatsController;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports products stats controller class.
 *
 * @internal
 * @extends GenericStatsController
 */
class Controller extends GenericStatsController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/products/stats';

	/**
	 * Mapping between external parameter name and name used in query class.
	 *
	 * @var array
	 */
	protected $param_mapping = array(
		'categories' => 'category_includes',
		'products'   => 'product_includes',
		'variations' => 'variation_includes',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_analytics_products_stats_select_query', array( $this, 'set_default_report_data' ) );
	}

	/**
	 * Get data from `'products-stats'` Query.
	 *
	 * @override GenericController::get_datastore_data()
	 *
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$query = new GenericQuery( $query_args, 'products-stats' );
		return $query->get_data();
	}

	/**
	 * Maps query arguments from the REST request, to be fed to Query.
	 *
	 * @param \WP_REST_Request $request Full request object.
	 * @return array Simplified array of params.
	 */
	protected function prepare_reports_query( $request ) {
		$query_args = array(
			'fields' => array(
				'items_sold',
				'net_revenue',
				'orders_count',
				'products_count',
				'variations_count',
			),
		);

		$registered = array_keys( $this->get_collection_params() );
		foreach ( $registered as $param_name ) {
			if ( isset( $request[ $param_name ] ) ) {
				if ( isset( $this->param_mapping[ $param_name ] ) ) {
					$query_args[ $this->param_mapping[ $param_name ] ] = $request[ $param_name ];
				} else {
					$query_args[ $param_name ] = $request[ $param_name ];
				}
			}
		}

		return $query_args;
	}

	/**
	 * Prepare a report data item for serialization.
	 *
	 * @param array           $report  Report data item as returned from Data Store.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		$response = parent::prepare_item_for_response( $report, $request );

		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_products_stats', $response, $report, $request );
	}

	/**
	 * Get the Report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	protected function get_item_properties_schema() {
		return array(
			'items_sold'   => array(
				'title'       => __( 'Products sold', 'woocommerce' ),
				'description' => __( 'Number of product items sold.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
			),
			'net_revenue'  => array(
				'description' => __( 'Net sales.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'format'      => 'currency',
			),
			'orders_count' => array(
				'description' => __( 'Number of orders.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema          = parent::get_item_schema();
		$schema['title'] = 'report_products_stats';

		$segment_label = array(
			'description' => __( 'Human readable segment label, either product or variation name.', 'woocommerce' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
			'enum'        => array( 'day', 'week', 'month', 'year' ),
		);

		$schema['properties']['totals']['properties']['segments']['items']['properties']['segment_label']                                        = $segment_label;
		$schema['properties']['intervals']['items']['properties']['subtotals']['properties']['segments']['items']['properties']['segment_label'] = $segment_label;

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Set the default results to 0 if API returns an empty array
	 *
	 * @internal
	 * @param Mixed $results Report data.
	 * @return object
	 */
	public function set_default_report_data( $results ) {
		if ( empty( $results ) ) {
			$results                       = new \stdClass();
			$results->total                = 0;
			$results->totals               = new \stdClass();
			$results->totals->items_sold   = 0;
			$results->totals->net_revenue  = 0;
			$results->totals->orders_count = 0;
			$results->intervals            = array();
			$results->pages                = 1;
			$results->page_no              = 1;
		}
		return $results;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                    = parent::get_collection_params();
		$params['orderby']['enum'] = array(
			'date',
			'net_revenue',
			'coupons',
			'refunds',
			'shipping',
			'taxes',
			'net_revenue',
			'orders_count',
			'items_sold',
		);
		$params['categories']      = array(
			'description'       => __( 'Limit result to items from the specified categories.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['products']        = array(
			'description'       => __( 'Limit result to items with specified product ids.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['variations']      = array(
			'description'       => __( 'Limit result to items with specified variation ids.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['segmentby']       = array(
			'description'       => __( 'Segment the response by additional constraint.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array(
				'product',
				'category',
				'variation',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
