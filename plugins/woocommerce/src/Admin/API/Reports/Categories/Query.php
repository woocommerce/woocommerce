<?php
/**
 * Class for parameter-based Categories Report querying
 *
 * Example usage:
 * $args = array(
 *          'before'       => '2018-07-19 00:00:00',
 *          'after'        => '2018-07-05 00:00:00',
 *          'page'         => 2,
 *          'order'        => 'desc',
 *          'orderby'      => 'items_sold',
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Categories\Query( $args );
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Categories;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Query as ReportsQuery;

/**
 * API\Reports\Categories\Query
 *
 * @deprecated x.x.x Categories\The Query class is deprecated. Please get data from the store directly in your Controller..
 */
class Query extends ReportsQuery {

	const REPORT_NAME = 'report-categories';

	/**
	 * Valid fields for Categories report.
	 *
	 * @deprecated x.x.x Categories\The Query class is deprecated. Please get data from the store directly in your Controller..
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', 'The Query class is deprecated. Please get data from the store directly in your Controller.' );

		return array();
	}

	/**
	 * Get categories data based on the current query vars.
	 *
	 * @deprecated x.x.x Categories\The Query class is deprecated. Please get data from the store directly in your Controller..
	 *
	 * @return array
	 */
	public function get_data() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', 'The Query class is deprecated. Please get data from the store directly in your Controller.' );

		$args    = apply_filters( 'woocommerce_analytics_categories_query_args', $this->get_query_vars() );
		$results = \WC_Data_Store::load( self::REPORT_NAME )->get_data( $args );
		return apply_filters( 'woocommerce_analytics_categories_select_query', $results, $args );
	}
}
