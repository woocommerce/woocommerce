<?php
/**
 * Class for parameter-based Customers Report Stats querying
 *
 * Example usage:
 * $args = array(
 *          'registered_before'   => '2018-07-19 00:00:00',
 *          'registered_after'    => '2018-07-05 00:00:00',
 *          'page'                => 2,
 *          'avg_order_value_min' => 100,
 *          'country'             => 'GB',
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Customers\Stats\Query( $args );
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Customers\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Query as ReportsQuery;

/**
 * API\Reports\Customers\Stats\Query
 *
 * @deprecated 9.3.0 Customers\Stats\Query class is deprecated, please use `Reports\Customers\Query` with a custom name, `GenericQuery`, `\WC_Object_Query`, or use `DataStore` directly.
 */
class Query extends ReportsQuery {

	/**
	 * Valid fields for Customers report.
	 *
	 * @deprecated 9.3.0 Customers\Stats\Query class is deprecated, please use `Reports\Customers\Query` with a custom name, `GenericQuery`, `\WC_Object_Query`, or use `DataStore` directly.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.3.0', '`Reports\Customers\Query` with a custom name, `GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use' );

		return array(
			'per_page' => get_option( 'posts_per_page' ), // not sure if this should be the default.
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'date_registered',
			'fields'   => '*', // @todo Needed?
		);
	}

	/**
	 * Get product data based on the current query vars.
	 *
	 * @deprecated 9.3.0 Customers\Stats\Query class is deprecated, please use `Reports\Customers\Query` with a custom name, `GenericQuery`, `\WC_Object_Query`, or use `DataStore` directly.
	 *
	 * @return array
	 */
	public function get_data() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', '`Reports\Customers\Query` with a custom name, `GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use' );

		$args = apply_filters( 'woocommerce_analytics_customers_stats_query_args', $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( 'report-customers-stats' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_analytics_customers_stats_select_query', $results, $args );
	}
}
