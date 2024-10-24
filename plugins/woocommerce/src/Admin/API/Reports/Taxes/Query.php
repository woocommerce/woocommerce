<?php
/**
 * Class for parameter-based Taxes Report querying
 *
 * Example usage:
 * $args = array(
 *          'before'       => '2018-07-19 00:00:00',
 *          'after'        => '2018-07-05 00:00:00',
 *          'page'         => 2,
 *          'taxes'        => array(1,2,3)
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Taxes\Query( $args );
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Taxes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Query as ReportsQuery;

/**
 * API\Reports\Taxes\Query
 *
 * @deprecated 9.3.0 Taxes\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
 */
class Query extends ReportsQuery {

	/**
	 * Valid fields for Taxes report.
	 *
	 * @deprecated 9.3.0 Taxes\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.3.0', '`GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use' );

		return array();
	}

	/**
	 * Get product data based on the current query vars.
	 *
	 * @deprecated 9.3.0 Taxes\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
	 *
	 * @return array
	 */
	public function get_data() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.3.0', '`GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use' );

		$args = apply_filters( 'woocommerce_analytics_taxes_query_args', $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( 'report-taxes' );
		$results    = $data_store->get_data( $args );

		$extra_report_data = $this->get_extra_report_data( $data_store );

		return apply_filters( 'woocommerce_analytics_taxes_select_query', $results, $args );
	}

	public function get_extra_report_data( $data_store ) {
		global $wpdb;

		$query_data = array(
			'order_item_name'     => array(
				'type'     => 'order_item',
				'function' => '',
				'name'     => 'tax_rate',
			),
			'tax_amount'          => array(
				'type'            => 'order_item_meta',
				'order_item_type' => 'tax',
				'function'        => '',
				'name'            => 'tax_amount',
			),
			'shipping_tax_amount' => array(
				'type'            => 'order_item_meta',
				'order_item_type' => 'tax',
				'function'        => '',
				'name'            => 'shipping_tax_amount',
			),
			'rate_id'             => array(
				'type'            => 'order_item_meta',
				'order_item_type' => 'tax',
				'function'        => '',
				'name'            => 'rate_id',
			),
			'ID'                  => array(
				'type'     => 'post_data',
				'function' => '',
				'name'     => 'post_id',
			),
		);

		$query_where = array(
			array(
				'key'      => 'order_item_type',
				'value'    => 'tax',
				'operator' => '=',
			),
			array(
				'key'      => 'order_item_name',
				'value'    => '',
				'operator' => '!=',
			),
		);

		// We exclude on-hold orders as they are still pending payment.
		$tax_rows_orders = $data_store->get_order_report_data(
			array(
				'data'         => $query_data,
				'where'        => $query_where,
				'order_by'     => 'posts.post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true,
				'order_types'  => wc_get_order_types( 'sales-reports' ),
				'order_status' => array( 'completed', 'processing', 'refunded' ),
			)
		);

		$tax_rows_partial_refunds = $data_store->get_order_report_data(
			array(
				'data'                => $query_data,
				'where'               => $query_where,
				'order_by'            => 'posts.post_date ASC',
				'query_type'          => 'get_results',
				'filter_range'        => true,
				'order_types'         => array( 'shop_order_refund' ),
				'parent_order_status' => array( 'completed', 'processing' ), // Partial refunds inside refunded orders should be ignored.
			)
		);

		$tax_rows_full_refunds = $data_store->get_order_report_data(
			array(
				'data'                => $query_data,
				'where'               => $query_where,
				'order_by'            => 'posts.post_date ASC',
				'query_type'          => 'get_results',
				'filter_range'        => true,
				'order_types'         => array( 'shop_order_refund' ),
				'parent_order_status' => array( 'refunded' ),
			)
		);

		// Merge.
		$tax_rows = array();
		// Initialize an associative array to store unique post_ids.
		$unique_post_ids = array();

		foreach ( $tax_rows_orders + $tax_rows_partial_refunds as $tax_row ) {
			$key                                    = $tax_row->tax_rate;
			$tax_rows[ $key ]                       = isset( $tax_rows[ $key ] ) ? $tax_rows[ $key ] : (object) array(
				'tax_amount'          => 0,
				'shipping_tax_amount' => 0,
				'total_orders'        => 0,
			);
			$tax_rows[ $key ]->tax_rate             = $tax_row->tax_rate;
			$tax_rows[ $key ]->tax_amount          += wc_round_tax_total( $tax_row->tax_amount );
			$tax_rows[ $key ]->shipping_tax_amount += wc_round_tax_total( $tax_row->shipping_tax_amount );
			if ( ! isset( $unique_post_ids[ $key ] ) || ! in_array( $tax_row->post_id, $unique_post_ids[ $key ], true ) ) {
				$unique_post_ids[ $key ]         = isset( $unique_post_ids[ $key ] ) ? $unique_post_ids[ $key ] : array();
				$unique_post_ids[ $key ][]       = $tax_row->post_id;
				$tax_rows[ $key ]->total_orders += 1;
			}
		}

		foreach ( $tax_rows_full_refunds as $tax_row ) {
			$key                                    = $tax_row->tax_rate;
			$tax_rows[ $key ]                       = isset( $tax_rows[ $key ] ) ? $tax_rows[ $key ] : (object) array(
				'tax_amount'          => 0,
				'shipping_tax_amount' => 0,
				'total_orders'        => 0,
			);
			$tax_rows[ $key ]->tax_rate             = $tax_row->tax_rate;
			$tax_rows[ $key ]->tax_amount          += wc_round_tax_total( $tax_row->tax_amount );
			$tax_rows[ $key ]->shipping_tax_amount += wc_round_tax_total( $tax_row->shipping_tax_amount );
		}
		return $tax_rows;
	}
}
