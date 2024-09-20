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
 * @deprecated 9.3.0 Downloads\Stats\Query class is deprecated, please use GenericQuery or \WC_Object_Query instead.
 */
class Query extends ReportsQuery {

	/**
	 * Valid fields for Orders report.
	 *
	 * @deprecated 9.3.0 Downloads\Stats\Query class is deprecated, please use GenericQuery or \WC_Object_Query instead.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array();
	}

	/**
	 * Get revenue data based on the current query vars.
	 *
	 * @deprecated 9.3.0 Downloads\Stats\Query class is deprecated, please use GenericQuery or \WC_Object_Query instead.
	 *
	 * @return array
	 */
	public function get_data() {
		$args = apply_filters( 'woocommerce_analytics_downloads_stats_query_args', $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( 'report-downloads-stats' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_analytics_downloads_stats_select_query', $results, $args );
	}
}
