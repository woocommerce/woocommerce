<?php
/**
 * REST API Reports revenue stats controller
 *
 * Handles requests to the /reports/revenue/stats endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Revenue\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericStatsController;
use Automattic\WooCommerce\Admin\API\Reports\Revenue\Query as RevenueQuery;
use Automattic\WooCommerce\Admin\API\Reports\ExportableInterface;
use Automattic\WooCommerce\Admin\API\Reports\ExportableTraits;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports revenue stats controller class.
 *
 * @internal
 * @extends GenericStatsController
 */
class Controller extends GenericStatsController implements ExportableInterface {
	/**
	 * Exportable traits.
	 */
	use ExportableTraits;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/revenue/stats';

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
		$args['segmentby']           = $request['segmentby'];
		$args['fields']              = $request['fields'];
		$args['force_cache_refresh'] = $request['force_cache_refresh'];
		$args['date_type']           = $request['date_type'];

		return $args;
	}

	/**
	 * Get data from RevenueQuery.
	 *
	 * @override GenericController::get_datastore_data()
	 *
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$query = new RevenueQuery( $query_args );
		return $query->get_data();
	}

	/**
	 * Get report items for export.
	 *
	 * Returns only the interval data.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response
	 */
	public function get_export_items( $request ) {
		$response  = $this->get_items( $request );
		$data      = $response->get_data();
		$intervals = $data['intervals'];

		$response->set_data( $intervals );

		return $response;
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
		return apply_filters( 'woocommerce_rest_prepare_report_revenue_stats', $response, $report, $request );
	}

	/**
	 * Get the Report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	protected function get_item_properties_schema() {
		return array(
			'total_sales'    => array(
				'description' => __( 'Total sales.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'net_revenue'    => array(
				'description' => __( 'Net sales.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'coupons'        => array(
				'description' => __( 'Amount discounted by coupons.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'coupons_count'  => array(
				'description' => __( 'Unique coupons count.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'format'      => 'currency',
			),
			'shipping'       => array(
				'title'       => __( 'Shipping', 'woocommerce' ),
				'description' => __( 'Total of shipping.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'taxes'          => array(
				'description' => __( 'Total of taxes.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'format'      => 'currency',
			),
			'refunds'        => array(
				'title'       => __( 'Returns', 'woocommerce' ),
				'description' => __( 'Total of returns.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'orders_count'   => array(
				'description' => __( 'Number of orders.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'num_items_sold' => array(
				'description' => __( 'Items sold.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'gross_sales'    => array(
				'description' => __( 'Gross sales.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
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
		$schema['title'] = 'report_revenue_stats';

		// Products is not shown in intervals, only in totals.
		$schema['properties']['totals']['properties']['products'] = array(
			'description' => __( 'Products sold.', 'woocommerce' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                    = parent::get_collection_params();
		$params['orderby']['enum'] = $this->apply_custom_orderby_filters(
			array(
				'date',
				'total_sales',
				'coupons',
				'refunds',
				'shipping',
				'taxes',
				'net_revenue',
				'orders_count',
				'items_sold',
				'gross_sales',
			)
		);
		$params['segmentby']       = array(
			'description'       => __( 'Segment the response by additional constraint.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array(
				'product',
				'category',
				'variation',
				'coupon',
				'customer_type', // new vs returning.
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['date_type']       = array(
			'description'       => __( 'Override the "woocommerce_date_type" option that is used for the database date field considered for revenue reports.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array(
				'date_paid',
				'date_created',
				'date_completed',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		unset( $params['fields'] );

		return $params;
	}

	/**
	 * Get the column names for export.
	 *
	 * @return array Key value pair of Column ID => Label.
	 */
	public function get_export_columns() {
		return array(
			'date'         => __( 'Date', 'woocommerce' ),
			'orders_count' => __( 'Orders', 'woocommerce' ),
			'gross_sales'  => __( 'Gross sales', 'woocommerce' ),
			'refunds'      => __( 'Returns', 'woocommerce' ),
			'coupons'      => __( 'Coupons', 'woocommerce' ),
			'net_revenue'  => __( 'Net sales', 'woocommerce' ),
			'taxes'        => __( 'Taxes', 'woocommerce' ),
			'shipping'     => __( 'Shipping', 'woocommerce' ),
			'total_sales'  => __( 'Total sales', 'woocommerce' ),
		);
	}

	/**
	 * Get the column values for export.
	 *
	 * @param array $item Single report item/row.
	 * @return array Key value pair of Column ID => Row Value.
	 */
	public function prepare_item_for_export( $item ) {
		$subtotals = (array) $item['subtotals'];

		return array(
			'date'         => $item['date_start'],
			'orders_count' => $subtotals['orders_count'],
			'gross_sales'  => self::csv_number_format( $subtotals['gross_sales'] ),
			'refunds'      => self::csv_number_format( $subtotals['refunds'] ),
			'coupons'      => self::csv_number_format( $subtotals['coupons'] ),
			'net_revenue'  => self::csv_number_format( $subtotals['net_revenue'] ),
			'taxes'        => self::csv_number_format( $subtotals['taxes'] ),
			'shipping'     => self::csv_number_format( $subtotals['shipping'] ),
			'total_sales'  => self::csv_number_format( $subtotals['total_sales'] ),
		);
	}
}
