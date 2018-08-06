<?php
/**
 * Class for parameter-based Revenue Reports querying
 *
 * Example usage:
 * $args = array(
 *          'before'       => '2018-07-19 00:00:00',
 *          'after'        => '2018-07-05 00:00:00',
 *          'interval'     => 'week',
 *          'categories'   => array(15, 18),
 *          'coupons'      => array(138),
 *          'order_status' => 'completed',
 *         );
 * $report = new WC_Reports_Orders_Stats_Query( $args );
 * $mydata = $report->get_data();
 *
 * @package  WooCommerce/Classes
 * @version  3.5.0
 * @since    3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Reports_Orders_Stats_Query
 *
 * @version  3.5.0
 */
class WC_Reports_Orders_Stats_Query extends WC_Reports_Query {

	const REPORT_NAME = 'report-orders-stats';

	/**
	 * Valid query vars for Revenue report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array(
			'fields' => array(
				'net_revenue',
				'avg_order_value',
				'orders_count',
				'avg_items_per_order',
			),
		);
	}

	/**
	 * Get revenue data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$args    = apply_filters( 'woocommerce_reports_orders_stats_query_args', $this->get_query_vars() );
		$results = WC_Data_Store::load( $this::REPORT_NAME )->get_data( $args );
		return apply_filters( 'woocommerce_reports_orders_stats_select_query', $results, $args );
	}

}
