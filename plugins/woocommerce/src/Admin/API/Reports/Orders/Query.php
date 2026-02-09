<?php
/**
 * Class for parameter-based Orders Reports querying
 *
 * Example usage:
 * $args = array(
 *          'before'        => '2018-07-19 00:00:00',
 *          'after'         => '2018-07-05 00:00:00',
 *          'interval'      => 'week',
 *          'products'      => array(15, 18),
 *          'coupons'       => array(138),
 *          'status_is'     => array('completed'),
 *          'status_is_not' => array('failed'),
 *          'new_customers' => false,
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Orders\Query( $args );
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Orders;

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;

defined( 'ABSPATH' ) || exit;


/**
 * API\Reports\Orders\Query
 */
class Query extends GenericQuery {

	/**
	 * Specific query name.
	 * Will be used to load the `report-{name}` data store,
	 * and to call `woocommerce_analytics_{snake_case(name)}_*` filters.
	 *
	 * @var string
	 */
	protected $name = 'orders';


	/**
	 * Get the default allowed query vars.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return \WC_Object_Query::get_default_query_vars();
	}
}
