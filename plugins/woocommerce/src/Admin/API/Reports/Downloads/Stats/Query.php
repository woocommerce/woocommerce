<?php
/**
 * Class for parameter-based downloads Reports querying
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Downloads\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Query as ReportsQuery;

/**
 * API\Reports\Downloads\Stats\Query
 *
 * @deprecated x.x.x Downloads\Stats\The Query class is deprecated. Please get data from the store directly in your Controller..
 */
class Query extends ReportsQuery {

	/**
	 * Valid fields for Orders report.
	 *
	 * @deprecated x.x.x Downloads\Stats\The Query class is deprecated. Please get data from the store directly in your Controller..
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', 'The Query class is deprecated. Please get data from the store directly in your Controller.' );

		return array();
	}

	/**
	 * Get revenue data based on the current query vars.
	 *
	 * @deprecated x.x.x Downloads\Stats\The Query class is deprecated. Please get data from the store directly in your Controller..
	 *
	 * @return array
	 */
	public function get_data() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', 'The Query class is deprecated. Please get data from the store directly in your Controller.' );

		$args = apply_filters( 'woocommerce_analytics_downloads_stats_query_args', $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( 'report-downloads-stats' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_analytics_downloads_stats_select_query', $results, $args );
	}
}
