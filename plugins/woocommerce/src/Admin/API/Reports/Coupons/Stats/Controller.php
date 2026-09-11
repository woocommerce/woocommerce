<?php
/**
 * REST API Reports coupons stats controller
 *
 * Handles requests to the /reports/coupons/stats endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericStatsController;
use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports coupons stats controller class.
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
	protected $rest_base = 'reports/coupons/stats';


	/**
	 * Validate the optional selector before the variation label lookup uses it.
	 *
	 * @param mixed                                  $value Parent IDs supplied by the client.
	 * @param \WP_REST_Request<array<string, mixed>> $request REST request.
	 * @param string                                 $parameter Parameter name.
	 * @return bool|\WP_Error Validation result.
	 */
	public function validate_variation_parent( $value, $request, $parameter ) {
		$valid = rest_validate_request_arg( $value, $request, $parameter );
		if ( is_wp_error( $valid ) || 'variation' !== $request['segmentby'] ) {
			return $valid;
		}
		$parents = wp_parse_id_list( $value );
		// The segmenter handles empty and ambiguous selectors with its existing error.
		if ( 1 === count( $parents ) && ! wc_get_product( $parents[0] ) ) {
			return new \WP_Error( 'rest_invalid_param', __( 'The parent product for the variation breakdown does not exist.', 'woocommerce' ), array( 'status' => 400 ) );
		}
		return true;
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
		$args['coupons']             = (array) $request['coupons'];
		$args['segmentby']           = $request['segmentby'];
		$args['fields']              = $request['fields'];
		$args['force_cache_refresh'] = $request['force_cache_refresh'];
		if ( 'variation' === $request['segmentby'] ) {
			$args['product_includes'] = (array) $request['product_includes'];
		}

		return $args;
	}

	/**
	 * Get data from `'coupons-stats'` GenericQuery.
	 *
	 * @override GenericController::get_datastore_data()
	 *
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$query = new GenericQuery( $query_args, 'coupons-stats' );
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
		return apply_filters( 'woocommerce_rest_prepare_report_coupons_stats', $response, $report, $request );
	}

	/**
	 * Get the Report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	protected function get_item_properties_schema() {
		return array(
			'allocation_missing_orders' => array(
				'description' => __( 'Number of orders whose selected coupons cannot be historically allocated to this product breakdown.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'reporting_missing_orders'  => array(
				'description' => __( 'Number of orders with missing historical currency data.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'amount'                    => array(
				'description' => __( 'Net discount amount. Null when the historical product allocation is unavailable.', 'woocommerce' ),
				'type'        => array( 'number', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'coupons_count'             => array(
				'description' => __( 'Number of coupons.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'orders_count'              => array(
				'title'       => __( 'Discounted orders', 'woocommerce' ),
				'description' => __( 'Number of discounted orders.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
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
		$schema['title'] = 'report_coupons_stats';

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                     = parent::get_collection_params();
		$params['orderby']['enum']  = $this->apply_custom_orderby_filters(
			array(
				'date',
				'amount',
				'coupons_count',
				'orders_count',
			)
		);
		$params['coupons']          = array(
			'description'       => __( 'Limit result set to coupons assigned specific coupon IDs.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['product_includes'] = array(
			'description'       => __( 'Parent product for a variation breakdown. Specify exactly one product ID when segmenting by variation.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => array( $this, 'validate_variation_parent' ),
		);
		$params['segmentby']        = array(
			'description'       => __( 'Segment the response by additional constraint.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array(
				'product',
				'variation',
				'category',
				'coupon',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
