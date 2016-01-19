<?php

namespace WooCommerce\Tests\Account;

/**
 * Class Functions.
 * @package WooCommerce\Tests\Account
 */
class Functions extends \WC_Unit_Test_Case {

	/**
	 * Test wc_get_account_menu_items().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_get_account_menu_items() {
		$this->assertEquals( array(
			'orders'          => __( 'Orders', 'woocommerce' ),
			'downloads'       => __( 'Downloads', 'woocommerce' ),
			'edit-address'    => __( 'Addresses', 'woocommerce' ),
			'edit-account'    => __( 'Account Details', 'woocommerce' ),
			'customer-logout' => __( 'Logout', 'woocommerce' ),
		), wc_get_account_menu_items() );
	}

	/**
	 * Test wc_get_account_orders_columns().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_get_account_orders_columns() {
		$this->assertEquals( array(
			'order-number'  => __( 'Order', 'woocommerce' ),
			'order-date'    => __( 'Date', 'woocommerce' ),
			'order-status'  => __( 'Status', 'woocommerce' ),
			'order-total'   => __( 'Total', 'woocommerce' ),
			'order-actions' => '&nbsp;',
		), wc_get_account_orders_columns() );
	}

	/**
	 * Test wc_get_account_orders_query_args().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_get_account_orders_query_args() {
		$this->assertEquals( array(
			'posts_per_page' => 15,
			'meta_key'       => '_customer_user',
			'meta_value'     => get_current_user_id(),
			'post_type'      => wc_get_order_types( 'view-orders' ),
			'post_status'    => array_keys( wc_get_order_statuses() ),
		), wc_get_account_orders_query_args() );
	}

	/**
	 * Test wc_get_account_downloads_columns().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_get_account_downloads_columns() {
		$this->assertEquals( array(
			'download-file'      => __( 'File', 'woocommerce' ),
			'download-remaining' => __( 'Remaining', 'woocommerce' ),
			'download-expires'   => __( 'Expires', 'woocommerce' ),
			'download-actions'   => '&nbsp;',
		), wc_get_account_downloads_columns() );
	}
}
