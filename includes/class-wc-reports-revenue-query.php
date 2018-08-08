<?php
/**
 * Class for parameter-based Revenue Reports querying
 *
 * Example usage:
 * $args = array(
 *          'before' => '2018-07-19 00:00:00',
 *          'after'  => '2018-07-05 00:00:00',
 *          'interval' => 'week',
 *         );
 * $report = new WC_Reports_Revenue_Query( $args );
 * $mydata = $report->get_data();
 *
 * @package  WooCommerce/Classes
 * @version  3.5.0
 * @since    3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Reports_Revenue_Query
 *
 * @version  3.5.0
 */
class WC_Reports_Revenue_Query extends WC_Reports_Query {

	const REPORT_NAME = 'report-revenue-stats';

	/**
	 * Valid fields for Revenue report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array(
			'per_page' => get_option( 'posts_per_page' ), // not sure if this should be the default.
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'date',
			'before'   => '',
			'after'    => '',
			'interval' => 'week',
			'fields' => array(
				'orders_count',
				'num_items_sold',
				'gross_revenue',
				'coupons',
				'refunds',
				'taxes',
				'shipping',
				'net_revenue',
			),
		);
	}

	/**
	 * Get revenue data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$args    = apply_filters( 'woocommerce_reports_revenue_query_args', $this->get_query_vars() );
		$results = WC_Data_Store::load( $this::REPORT_NAME )->get_data( $args );
		return apply_filters( 'woocommerce_reports_revenue_select_query', $results, $args );
	}

}
