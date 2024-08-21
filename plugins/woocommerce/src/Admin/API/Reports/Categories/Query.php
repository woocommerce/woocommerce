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
 * @deprecated 9.3.0 Categories\Query class is deprecated, please use GenericQuery or \WC_Object_Query instead.
 */
class Query extends ReportsQuery {

	const REPORT_NAME = 'report-categories';

	/**
	 * Valid fields for Categories report.
	 *
	 * @deprecated 9.3.0 Categories\Query class is deprecated, please use GenericQuery or \WC_Object_Query instead.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array();
	}

	/**
	 * Get categories data based on the current query vars.
	 *
	 * @deprecated 9.3.0 Categories\Query class is deprecated, please use GenericQuery or \WC_Object_Query instead.
	 *
	 * @return array
	 */
	public function get_data() {
		$args    = apply_filters( 'woocommerce_analytics_categories_query_args', $this->get_query_vars() );
		$results = \WC_Data_Store::load( self::REPORT_NAME )->get_data( $args );
		return apply_filters( 'woocommerce_analytics_categories_select_query', $results, $args );
	}
}
