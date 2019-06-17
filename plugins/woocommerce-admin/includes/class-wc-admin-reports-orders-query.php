<?php
/**
 * Class for parameter-based Orders Reports querying
 *
 * Example usage:
 * $args = array(
 *          'before'        => '2018-07-19 00:00:00',
 *          'after'         => '2018-07-05 00:00:00',
 *          'interval'      => 'week',
 *          'products'      => array(15, 18),
 *          'coupons'       => array(138),
 *          'status_is'     => array('completed'),
 *          'status_is_not' => array('failed'),
 *          'new_customers' => false,
 *         );
 * $report = new WC_Admin_Reports_Orders_Query( $args );
 * $mydata = $report->get_data();
 *
 * @package  WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Orders_Query
 */
class WC_Admin_Reports_Orders_Query extends WC_Admin_Reports_Query {

	/**
	 * Get order data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$args       = apply_filters( 'woocommerce_reports_orders_query_args', $this->get_query_vars() );
		$data_store = WC_Data_Store::load( 'report-orders' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_reports_orders_select_query', $results, $args );
	}

}
