<?php
/**
 * REST API Reports variations stats controller
 *
 * Handles requests to the /reports/variations/stats endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Variations\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\GenericStatsController;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports variations stats controller class.
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
	protected $rest_base = 'reports/variations/stats';

	/**
	 * Mapping between external parameter name and name used in query class.
	 *
	 * @var array
	 */
	protected $param_mapping = array(
		'variations' => 'variation_includes',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_analytics_variations_stats_select_query', array( $this, 'set_default_report_data' ) );
	}

	/**
	 * Get data from `'variations-stats'` Query.
	 *
	 * @override GenericController::get_datastore_data()
	 *
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$query = new GenericQuery( $query_args, 'variations-stats' );
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
				'variations_count',
			),
		);
		/**
		 * Experimental: Filter the list of parameters provided when querying data from the data store.
		 *
		 * @ignore
		 *
		 * @param array $collection_params List of parameters.
		 */
		$collection_params = apply_filters( 'experimental_woocommerce_analytics_variations_stats_collection_params', $this->get_collection_params() );
		$registered        = array_keys( $collection_params );
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
		return apply_filters( 'woocommerce_rest_prepare_report_variations_stats', $response, $report, $request );
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
				'title'       => __( 'Variations Sold', 'woocommerce' ),
				'description' => __( 'Number of variation items sold.', 'woocommerce' ),
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
		$schema['title'] = 'report_variations_stats';

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
		$params                      = parent::get_collection_params();
		$params['match']             = array(
			'description'       => __( 'Indicates whether all the conditions should be true for the resulting set, or if any one of them is sufficient. Match affects the following parameters: status_is, status_is_not, product_includes, product_excludes, coupon_includes, coupon_excludes, customer, categories', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'all',
			'enum'              => array(
				'all',
				'any',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orderby']['enum']   = array(
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
		$params['category_includes'] = array(
			'description'       => __( 'Limit result to items from the specified categories.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['category_excludes'] = array(
			'description'       => __( 'Limit result set to variations not in the specified categories.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['product_includes']  = array(
			'description'       => __( 'Limit result set to items that have the specified parent product(s).', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['product_excludes']  = array(
			'description'       => __( 'Limit result set to items that don\'t have the specified parent product(s).', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'validate_callback' => 'rest_validate_request_arg',
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['variations']        = array(
			'description'       => __( 'Limit result to items with specified variation ids.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['segmentby']         = array(
			'description'       => __( 'Segment the response by additional constraint.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array(
				'product',
				'category',
				'variation',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['attribute_is']      = array(
			'description'       => __( 'Limit result set to orders that include products with the specified attributes.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'array',
			),
			'default'           => array(),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['attribute_is_not']  = array(
			'description'       => __( 'Limit result set to orders that don\'t include products with the specified attributes.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'array',
			),
			'default'           => array(),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
