<?php
/**
 * Class for parameter-based Customers Report querying
 *
 * Example usage:
 * $args = array(
 *          'registered_before'   => '2018-07-19 00:00:00',
 *          'registered_after'    => '2018-07-05 00:00:00',
 *          'page'                => 2,
 *          'avg_order_value_min' => 100,
 *          'country'             => 'GB',
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Customers\Query( $args );
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Customers;

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;

defined( 'ABSPATH' ) || exit;

/**
 * API\Reports\Customers\Query
 */
class Query extends GenericQuery {

	/**
	 * Specific query name.
	 * Will be used to load the `report-{name}` data store,
	 * and to call `woocommerce_analytics_{snake_case(name)}_*` filters.
	 *
	 * @var string
	 */
	protected $name = 'customers';

	/**
	 * Valid fields for Customers report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array(
			'per_page' => get_option( 'posts_per_page' ), // not sure if this should be the default.
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'date_registered',
			'fields'   => '*',
		);
	}
}
