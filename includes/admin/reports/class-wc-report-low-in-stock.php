<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Report_Stock' ) ) {
	require_once dirname( __FILE__ ) . '/class-wc-report-stock.php';
}

/**
 * WC_Report_Low_In_Stock.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Reports
 * @version     2.1.0
 */
class WC_Report_Low_In_Stock extends WC_Report_Stock {

	/**
	 * No items found text.
	 */
	public function no_items() {
		_e( 'No low in stock products found.', 'woocommerce' );
	}

	/**
	 * Get Products matching stock criteria.
	 *
	 * @param int $current_page
	 * @param int $per_page
	 */
	public function get_items( $current_page, $per_page ) {
		global $wpdb;

		$this->max_items = 0;
		$this->items     = array();

		// Get products using a query - this is too advanced for get_posts :(
		$stock   = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );
		$nostock = absint( max( get_option( 'woocommerce_notify_no_stock_amount' ), 0 ) );

		$query_from = apply_filters(
			'woocommerce_report_low_in_stock_query_from',
			$wpdb->prepare(
				"
				FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->wc_product_meta_lookup} AS lookup ON posts.ID = lookup.product_id
				WHERE 1=1
				AND posts.post_type IN ( 'product', 'product_variation' )
				AND posts.post_status = 'publish'
				AND lookup.stock_quantity <= %d
				AND lookup.stock_quantity > %d
				",
				$stock,
				$nostock
			)
		);

		$this->items     = $wpdb->get_results( $wpdb->prepare( "SELECT posts.ID as id, posts.post_parent as parent {$query_from} GROUP BY posts.ID ORDER BY posts.post_title DESC LIMIT %d, %d;", ( $current_page - 1 ) * $per_page, $per_page ) );
		$this->max_items = $wpdb->get_var( "SELECT COUNT( DISTINCT posts.ID ) {$query_from};" );
	}
}
