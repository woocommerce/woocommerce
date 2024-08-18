<?php
/**
 * Class for parameter-based Taxes Stats Report querying
 *
 * Example usage:
 * $args = array(
 *          'before'       => '2018-07-19 00:00:00',
 *          'after'        => '2018-07-05 00:00:00',
 *          'page'         => 2,
 *          'categories'   => array(15, 18),
 *          'product_ids'  => array(1,2,3)
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\Query( $args );
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Query as ReportsQuery;

/**
 * API\Reports\Taxes\Stats\Query
 *
 * @deprecated x.x.x Taxes\Stats\The Query class is deprecated. Please get data from the store directly in your Controller..
 */
class Query extends ReportsQuery {

	/**
	 * Valid fields for Taxes report.
	 *
	 * @deprecated x.x.x Taxes\Stats\The Query class is deprecated. Please get data from the store directly in your Controller..
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', 'The Query class is deprecated. Please get data from the store directly in your Controller.' );

		return array();
	}

	/**
	 * Get tax stats data based on the current query vars.
	 *
	 * @deprecated x.x.x Taxes\Stats\The Query class is deprecated. Please get data from the store directly in your Controller..
	 *
	 * @return array
	 */
	public function get_data() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', 'The Query class is deprecated. Please get data from the store directly in your Controller.' );

		$args = apply_filters( 'woocommerce_analytics_taxes_stats_query_args', $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( 'report-taxes-stats' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_analytics_taxes_stats_select_query', $results, $args );
	}
}
