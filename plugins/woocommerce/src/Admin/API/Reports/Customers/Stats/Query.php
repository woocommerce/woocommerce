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
use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;

defined( 'ABSPATH' ) || exit;

/**
 * API\Reports\Customers\Stats\Query
 */
class Query extends GenericQuery {

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
			'fields'   => '*', // @todo Needed?
		);
	}
}
