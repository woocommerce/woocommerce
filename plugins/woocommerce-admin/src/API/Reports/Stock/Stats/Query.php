<?php
/**
 * Class for stock stats report querying
 *
 * $report = new WC_Admin_Reports_Stock__Stats_Query();
 * $mydata = $report->get_data();
 *
 * @package  WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Stock\Stats;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\Query as ReportsQuery;

/**
 * WC_Admin_Reports_Stock_Stats_Query
 */
class Query extends ReportsQuery {

	/**
	 * Get product data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$data_store = \WC_Data_Store::load( 'report-stock-stats' );
		$results    = $data_store->get_data();
		return apply_filters( 'woocommerce_reports_stock_stats_query', $results );
	}

}
