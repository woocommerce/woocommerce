<?php
/**
 * Class for stock stats report querying
 *
 * $report = new WC_Admin_Reports_Stock__Stats_Query();
 * $mydata = $report->get_data();
 *
 * @package  WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Stock_Stats_Query
 */
class WC_Admin_Reports_Stock_Stats_Query extends WC_Admin_Reports_Query {

	/**
	 * Get product data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$data_store = WC_Data_Store::load( 'report-stock-stats' );
		$results    = $data_store->get_data();
		return apply_filters( 'woocommerce_reports_stock_stats_query', $results );
	}

}
