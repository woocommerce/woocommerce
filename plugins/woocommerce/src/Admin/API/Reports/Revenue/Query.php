<?php
/**
 * Class for parameter-based Revenue Reports querying
 *
 * Example usage:
 * $args = array(
 *          'before' => '2018-07-19 00:00:00',
 *          'after'  => '2018-07-05 00:00:00',
 *          'interval' => 'week',
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Revenue\Query( $args );
 * $mydata = $report->get_data();
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Revenue;

defined( 'ABSPATH' ) || exit;

/**
 * API\Reports\Revenue\Query
 *
 * This query uses inconsistent names:
 *  - `report-revenue-stats` data store
 *  - `woocommerce_analytics_revenue_*` filters
 * So, for backward compatibility, we cannot use GenericQuery.
 */
class Query extends \WC_Object_Query {

	/**
	 * Valid fields for Revenue report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array(
			'per_page' => get_option( 'posts_per_page' ), // not sure if this should be the default.
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'date',
			'before'   => '',
			'after'    => '',
			'interval' => 'week',
			'fields'   => array(
				'orders_count',
				'num_items_sold',
				'total_sales',
				'coupons',
				'coupons_count',
				'refunds',
				'taxes',
				'shipping',
				'net_revenue',
				'gross_sales',
			),
		);
	}

	/**
	 * Get revenue data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$args = apply_filters( 'woocommerce_analytics_revenue_query_args', $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( 'report-revenue-stats' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_analytics_revenue_select_query', $results, $args );
	}
}
