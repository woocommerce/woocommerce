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
 * $report = new WC_Reports_Categories_Query( $args );
 * $mydata = $report->get_data();
 *
 * @package  WooCommerce/Classes
 * @version  3.5.0
 * @since    3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Reports_Categories_Query
 *
 * @version  3.5.0
 */
class WC_Reports_Categories_Query extends WC_Reports_Query {

	const REPORT_NAME = 'report-categories';

	/**
	 * Valid fields for Categories report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array();
	}

	/**
	 * Get categories data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$args    = apply_filters( 'woocommerce_reports_categories_query_args', $this->get_query_vars() );
		$results = WC_Data_Store::load( self::REPORT_NAME )->get_data( $args );
		return apply_filters( 'woocommerce_reports_categories_select_query', $results, $args );
	}

}
