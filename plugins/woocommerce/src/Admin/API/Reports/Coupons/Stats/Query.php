<?php
/**
 * Class for parameter-based Products Report querying
 *
 * Example usage:
 * $args = array(
 *          'before'  => '2018-07-19 00:00:00',
 *          'after'   => '2018-07-05 00:00:00',
 *          'page'    => 2,
 *          'coupons' => array(5, 120),
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats\Query( $args );
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Query as ReportsQuery;

/**
 * API\Reports\Coupons\Stats\Query
 *
 * @deprecated x.x.x Coupons\Stats\The Query class is deprecated. Please get data from the store directly in your Controller..
 */
class Query extends ReportsQuery {

	/**
	 * Valid fields for Products report.
	 *
	 * @deprecated x.x.x Coupons\Stats\The Query class is deprecated. Please get data from the store directly in your Controller..
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', 'The Query class is deprecated. Please get data from the store directly in your Controller.' );

		return array();
	}

	/**
	 * Get product data based on the current query vars.
	 *
	 * @deprecated x.x.x Coupons\Stats\The Query class is deprecated. Please get data from the store directly in your Controller..
	 *
	 * @return array
	 */
	public function get_data() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', 'The Query class is deprecated. Please get data from the store directly in your Controller.' );

		$args = apply_filters( 'woocommerce_analytics_coupons_stats_query_args', $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( 'report-coupons-stats' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_analytics_coupons_select_query', $results, $args );
	}

}
