<?php
/**
 * Class for parameter-based Coupons Report querying
 *
 * Example usage:
 * $args = array(
 *          'before'  => '2018-07-19 00:00:00',
 *          'after'   => '2018-07-05 00:00:00',
 *          'page'    => 2,
 *          'coupons' => array(5, 120),
 *         );
 * $report = new WC_Admin_Reports_Coupons_Query( $args );
 * $mydata = $report->get_data();
 *
 * @package  WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Products_Query
 */
class WC_Admin_Reports_Coupons_Query extends WC_Admin_Reports_Query {

	/**
	 * Valid fields for Products report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array();
	}

	/**
	 * Get product data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$args = apply_filters( 'woocommerce_reports_coupons_query_args', $this->get_query_vars() );

		$data_store = WC_Data_Store::load( 'report-coupons' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_reports_coupons_select_query', $results, $args );
	}

}
