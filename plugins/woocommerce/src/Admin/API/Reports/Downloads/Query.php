<?php
/**
 * Class for parameter-based downloads report querying.
 *
 * Example usage:
 * $args = array(
 *      'before'       => '2018-07-19 00:00:00',
 *      'after'        => '2018-07-05 00:00:00',
 *      'page'         => 2,
 *      'products'     => array(1,2,3)
 * );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Downloads\Query( $args );
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Downloads;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Query as ReportsQuery;

/**
 * API\Reports\Downloads\Query
 *
 * @deprecated 9.3.0 Downloads\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
 */
class Query extends ReportsQuery {

	/**
	 * Valid fields for downloads report.
	 *
	 * @deprecated 9.3.0 Downloads\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.3.0', '`GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use' );

		return array();
	}

	/**
	 * Get downloads data based on the current query vars.
	 *
	 * @deprecated 9.3.0 Downloads\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
	 *
	 * @return array
	 */
	public function get_data() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.3.0', '`GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use' );

		$args = apply_filters( 'woocommerce_analytics_downloads_query_args', $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( 'report-downloads' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_analytics_downloads_select_query', $results, $args );
	}
}
