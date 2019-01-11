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
 * $report = new WC_Admin_Reports_Customers_Query( $args );
 * $mydata = $report->get_data();
 *
 * @package  WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Customers_Query
 */
class WC_Admin_Reports_Customers_Query extends WC_Admin_Reports_Query {

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

	/**
	 * Get product data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$args = apply_filters( 'woocommerce_reports_customers_query_args', $this->get_query_vars() );

		$data_store = WC_Data_Store::load( 'report-customers' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_reports_customers_select_query', $results, $args );
	}

}
