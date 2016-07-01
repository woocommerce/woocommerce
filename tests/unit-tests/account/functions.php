<?php

/**
 * Class Functions.
 * @package WooCommerce\Tests\Account
 */
class WC_Tests_Account_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_get_account_menu_items().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_get_account_menu_items() {
		$this->assertEquals( array(
			'dashboard'       => __( 'Dashboard', 'woocommerce' ),
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
