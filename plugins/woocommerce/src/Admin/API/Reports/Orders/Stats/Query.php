<?php
/**
 * Class for parameter-based Order Stats Reports querying
 *
 * Example usage:
 * $args = array(
 *          'before'       => '2018-07-19 00:00:00',
 *          'after'        => '2018-07-05 00:00:00',
 *          'interval'     => 'week',
 *          'categories'   => array(15, 18),
 *          'coupons'      => array(138),
 *          'status_in'    => array('completed'),
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Query( $args );
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;

defined( 'ABSPATH' ) || exit;

/**
 * API\Reports\Orders\Stats\Query
 */
class Query extends GenericQuery {

	/**
	 * Specific query name.
	 * Will be used to load the `report-{name}` data store,
	 * and to call `woocommerce_analytics_{snake_case(name)}_*` filters.
	 *
	 * @var string
	 */
	protected $name = 'orders-stats';

	/**
	 * Valid fields for Orders report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array(
			'fields' => array(
				'net_revenue',
				'avg_order_value',
				'orders_count',
				'avg_items_per_order',
				'num_items_sold',
				'coupons',
				'coupons_count',
				'total_customers',
			),
		);
	}
}
