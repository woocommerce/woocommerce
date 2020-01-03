<?php
/**
 * REST API Reports revenue stats controller
 *
 * Handles requests to the /reports/revenue/stats endpoint.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Revenue\Stats;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\Revenue\Query as RevenueQuery;
use \Automattic\WooCommerce\Admin\API\Reports\ExportableInterface;
use \Automattic\WooCommerce\Admin\API\Reports\ExportableTraits;
use \Automattic\WooCommerce\Admin\API\Reports\ParameterException;

/**
 * REST API Reports revenue stats controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Reports_Controller
 */
class Controller extends \WC_REST_Reports_Controller implements ExportableInterface {
	/**
	 * Exportable traits.
	 */
	use ExportableTraits;

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
	protected $rest_base = 'reports/revenue/stats';

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param array $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args              = array();
		$args['before']    = $request['before'];
		$args['after']     = $request['after'];
		$args['interval']  = $request['interval'];
		$args['page']      = $request['page'];
		$args['per_page']  = $request['per_page'];
		$args['orderby']   = $request['orderby'];
		$args['order']     = $request['order'];
		$args['segmentby'] = $request['segmentby'];

		return $args;
	}

	/**
	 * Get all reports.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$query_args      = $this->prepare_reports_query( $request );
		$reports_revenue = new RevenueQuery( $query_args );
		try {
			$report_data = $reports_revenue->get_data();
		} catch ( ParameterException $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		$out_data = array(
			'totals'    => get_object_vars( $report_data->totals ),
			'intervals' => array(),
		);

		foreach ( $report_data->intervals as $interval_data ) {
			$item                    = $this->prepare_item_for_response( $interval_data, $request );
			$out_data['intervals'][] = $this->prepare_response_for_collection( $item );
		}

		$response = rest_ensure_response( $out_data );
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
	 * Prepare a report object for serialization.
	 *
	 * @param Array           $report  Report data.
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
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$data_values = array(
			'total_sales'    => array(
				'description' => __( 'Total Sales.', 'woocommerce-admin' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'net_revenue'    => array(
				'description' => __( 'Net Sales.', 'woocommerce-admin' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'coupons'        => array(
				'description' => __( 'Amount discounted by coupons.', 'woocommerce-admin' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'coupons_count'  => array(
				'description' => __( 'Unique coupons count.', 'woocommerce-admin' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'format'      => 'currency',
			),
			'shipping'       => array(
				'title'       => __( 'Shipping', 'woocommerce-admin' ),
				'description' => __( 'Total of shipping.', 'woocommerce-admin' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'taxes'          => array(
				'description' => __( 'Total of taxes.', 'woocommerce-admin' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'format'      => 'currency',
			),
			'refunds'        => array(
				'title'       => __( 'Returns', 'woocommerce-admin' ),
				'description' => __( 'Total of returns.', 'woocommerce-admin' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
			'orders_count'   => array(
				'description' => __( 'Number of orders.', 'woocommerce-admin' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'num_items_sold' => array(
				'description' => __( 'Items sold.', 'woocommerce-admin' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'products'       => array(
				'description' => __( 'Products sold.', 'woocommerce-admin' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'gross_sales'    => array(
				'description' => __( 'Gross Sales.', 'woocommerce-admin' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			),
		);

		$segments = array(
			'segments' => array(
				'description' => __( 'Reports data grouped by segment condition.', 'woocommerce-admin' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'segment_id' => array(
							'description' => __( 'Segment identificator.', 'woocommerce-admin' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'subtotals'  => array(
							'description' => __( 'Interval subtotals.', 'woocommerce-admin' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => $data_values,
						),
					),
				),
			),
		);

		$totals = array_merge( $data_values, $segments );

		// Products is not shown in intervals.
		unset( $data_values['products'] );

		$intervals = array_merge( $data_values, $segments );

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_revenue_stats',
			'type'       => 'object',
			'properties' => array(
				'totals'    => array(
					'description' => __( 'Totals data.', 'woocommerce-admin' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => $totals,
				),
				'intervals' => array(
					'description' => __( 'Reports data grouped by intervals.', 'woocommerce-admin' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'interval'       => array(
								'description' => __( 'Type of interval.', 'woocommerce-admin' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'enum'        => array( 'day', 'week', 'month', 'year' ),
							),
							'date_start'     => array(
								'description' => __( "The date the report start, in the site's timezone.", 'woocommerce-admin' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_start_gmt' => array(
								'description' => __( 'The date the report start, as GMT.', 'woocommerce-admin' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_end'       => array(
								'description' => __( "The date the report end, in the site's timezone.", 'woocommerce-admin' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_end_gmt'   => array(
								'description' => __( 'The date the report end, as GMT.', 'woocommerce-admin' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'subtotals'      => array(
								'description' => __( 'Interval subtotals.', 'woocommerce-admin' ),
								'type'        => 'object',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'properties'  => $intervals,
							),
						),
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
		$params              = array();
		$params['context']   = $this->get_context_param( array( 'default' => 'view' ) );
		$params['page']      = array(
			'description'       => __( 'Current page of the collection.', 'woocommerce-admin' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);
		$params['per_page']  = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce-admin' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['after']     = array(
			'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'woocommerce-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['before']    = array(
			'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'woocommerce-admin' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order']     = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce-admin' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orderby']   = array(
			'description'       => __( 'Sort collection by object attribute.', 'woocommerce-admin' ),
			'type'              => 'string',
			'default'           => 'date',
			'enum'              => array(
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
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['interval']  = array(
			'description'       => __( 'Time interval to use for buckets in the returned data.', 'woocommerce-admin' ),
			'type'              => 'string',
			'default'           => 'week',
			'enum'              => array(
				'hour',
				'day',
				'week',
				'month',
				'quarter',
				'year',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['segmentby'] = array(
			'description'       => __( 'Segment the response by additional constraint.', 'woocommerce-admin' ),
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

		return $params;
	}

	/**
	 * Get the column names for export.
	 *
	 * @return array Key value pair of Column ID => Label.
	 */
	public function get_export_columns() {
		return array(
			'date'         => __( 'Date', 'woocommerce-admin' ),
			'orders_count' => __( 'Orders', 'woocommerce-admin' ),
			'total_sales'  => __( 'Total Sales', 'woocommerce-admin' ),
			'refunds'      => __( 'Returns', 'woocommerce-admin' ),
			'coupons'      => __( 'Coupons', 'woocommerce-admin' ),
			'taxes'        => __( 'Taxes', 'woocommerce-admin' ),
			'shipping'     => __( 'Shipping', 'woocommerce-admin' ),
			'net_revenue'  => __( 'Net Revenue', 'woocommerce-admin' ),
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
			'total_sales'  => self::csv_number_format( $subtotals['total_sales'] ),
			'refunds'      => self::csv_number_format( $subtotals['refunds'] ),
			'coupons'      => self::csv_number_format( $subtotals['coupons'] ),
			'taxes'        => self::csv_number_format( $subtotals['taxes'] ),
			'shipping'     => self::csv_number_format( $subtotals['shipping'] ),
			'net_revenue'  => self::csv_number_format( $subtotals['net_revenue'] ),
		);
	}
}
