<?php
/**
 * REST API Reports taxes stats controller
 *
 * Handles requests to the /reports/taxes/stats endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\GenericStatsController;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports taxes stats controller class.
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
	protected $rest_base = 'reports/taxes/stats';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_analytics_taxes_stats_select_query', array( $this, 'set_default_report_data' ) );
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
			$results->totals->tax_codes    = 0;
			$results->totals->total_tax    = 0;
			$results->totals->order_tax    = 0;
			$results->totals->shipping_tax = 0;
			$results->totals->orders       = 0;
			$results->intervals            = array();
			$results->pages                = 1;
			$results->page_no              = 1;
		}
		return $results;
	}

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param array $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args                        = array();
		$args['before']              = $request['before'];
		$args['after']               = $request['after'];
		$args['interval']            = $request['interval'];
		$args['page']                = $request['page'];
		$args['per_page']            = $request['per_page'];
		$args['orderby']             = $request['orderby'];
		$args['order']               = $request['order'];
		$args['taxes']               = (array) $request['taxes'];
		$args['segmentby']           = $request['segmentby'];
		$args['fields']              = $request['fields'];
		$args['force_cache_refresh'] = $request['force_cache_refresh'];

		return $args;
	}

	/**
	 * Get data from `'taxes-stats'` Query.
	 *
	 * @override GenericController::get_datastore_data()
	 *
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$query = new GenericQuery( $query_args, 'taxes-stats' );
		return $query->get_data();
	}

	/**
	 * Prepare a report data item for serialization.
	 *
	 * @param mixed           $report  Report data item as returned from Data Store.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		$response = parent::prepare_item_for_response( $report, $request );

		// Map to `object` for backwards compatibility.
		$report = (object) $report;
		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_taxes_stats', $response, $report, $request );
	}

	/**
	 * Get the Report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	protected function get_item_properties_schema() {
		return array(
			'total_tax'    => array(
				'description' => __( 'Total tax.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'order_tax'    => array(
				'description' => __( 'Order tax.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'shipping_tax' => array(
				'description' => __( 'Shipping tax.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'orders_count' => array(
				'description' => __( 'Number of orders.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'tax_codes'    => array(
				'description' => __( 'Amount of tax codes.', 'woocommerce' ),
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
		$schema['title'] = 'report_taxes_stats';

		return $this->add_additional_fields_schema( $schema );
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
			'items_sold',
			'total_sales',
			'orders_count',
			'products_count',
		);
		$params['taxes']           = array(
			'description'       => __( 'Limit result set to all items that have the specified term assigned in the taxes taxonomy.', 'woocommerce' ),
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
				'tax_rate_id',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
