<?php
/**
 * Class for parameter-based Products Stats Report querying
 *
 * Example usage:
 * $args = array(
 *          'before'       => '2018-07-19 00:00:00',
 *          'after'        => '2018-07-05 00:00:00',
 *          'page'         => 2,
 *          'categories'   => array(15, 18),
 *          'product_ids'  => array(1,2,3)
 *         );
 * $report = new WC_Reports_Products_Stats_Query( $args );
 * $mydata = $report->get_data();
 *
 * @package  WooCommerce/Classes
 * @version  3.5.0
 * @since    3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Reports_Products_Query
 *
 * @version  3.5.0
 */
class WC_Reports_Products_Stats_Query extends WC_Reports_Query {

	const REPORT_NAME = 'report-products-stats';

	/**
	 * Valid fields for Products report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array();
	}

	/**
	 * Get product data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$args    = apply_filters( 'woocommerce_reports_products_stats_query_args', $this->get_query_vars() );
		$results = WC_Data_Store::load( $this::REPORT_NAME )->get_data( $args );
		return apply_filters( 'woocommerce_reports_products_stats_select_query', $results, $args );
	}

}
