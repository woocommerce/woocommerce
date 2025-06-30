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
 * @deprecated 9.3.0 Downloads\Stats\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
 */
class Query extends ReportsQuery {

	/**
	 * Valid fields for Orders report.
	 *
	 * @deprecated 9.3.0 Downloads\Stats\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.3.0', '`GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use' );

		return array();
	}

	/**
	 * Get revenue data based on the current query vars.
	 *
	 * @deprecated 9.3.0 Downloads\Stats\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
	 *
	 * @return array
	 */
	public function get_data() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.3.0', '`GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use' );

		$args = apply_filters( 'woocommerce_analytics_downloads_stats_query_args', $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( 'report-downloads-stats' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_analytics_downloads_stats_select_query', $results, $args );
	}
}
