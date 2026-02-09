<?php
/**
 * Class for stock stats report querying
 *
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\Query();
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Stock\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * API\Reports\Stock\Stats\Query
 * This query takes no arguments, so we do not inherit from GenericQuery.
 */
class Query extends \WC_Object_Query {

	/**
	 * Get product data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$data_store = \WC_Data_Store::load( 'report-stock-stats' );
		$results    = $data_store->get_data();
		return apply_filters( 'woocommerce_analytics_stock_stats_query', $results );
	}
}
