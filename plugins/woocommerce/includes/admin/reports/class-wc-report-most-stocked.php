<?php
/**
 * WC_Report_Most_Stocked.
 *
 * @deprecated 9.5.0 Reports are deprecated and will be removed in future versions. Use Analytics instead.
 * @package WooCommerce\Admin\Reports
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Report_Stock' ) ) {
	require_once dirname( __FILE__ ) . '/class-wc-report-stock.php';
}

/**
 * WC_Report_Most_Stocked.
 *
 * @deprecated 9.5.0 Reports are deprecated and will be removed in future versions. Use Analytics instead.
 */
class WC_Report_Most_Stocked extends WC_Report_Stock {

	/**
	 * Get Products matching stock criteria.
	 *
	 * @deprecated 9.5.0 Reports are deprecated and will be removed in future versions. Use Analytics instead.
	 * @param int $current_page Current page number.
	 * @param int $per_page How many results to show per page.
	 */
	public function get_items( $current_page, $per_page ) {
		global $wpdb;

		$this->max_items = 0;
		$this->items     = array();

		$stock = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 0 ) );

		$query_from = apply_filters_deprecated(
			'woocommerce_report_most_stocked_query_from',
			array(
				$wpdb->prepare(
					"
					FROM {$wpdb->posts} as posts
					INNER JOIN {$wpdb->wc_product_meta_lookup} AS lookup ON posts.ID = lookup.product_id
					WHERE 1=1
					AND posts.post_type IN ( 'product', 'product_variation' )
					AND posts.post_status = 'publish'
					AND lookup.stock_quantity > %d
					",
					$stock
				),
			),
			'9.5.0',
			null,
			'Reports are deprecated and will be removed in future versions. Use Analytics instead.',
		);

		$this->items     = $wpdb->get_results( $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS posts.ID as id, posts.post_parent as parent {$query_from} ORDER BY lookup.stock_quantity DESC, id ASC LIMIT %d, %d;", ( $current_page - 1 ) * $per_page, $per_page ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->max_items = $wpdb->get_var( 'SELECT FOUND_ROWS();' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
