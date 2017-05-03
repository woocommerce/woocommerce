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
			'dashboard'       => 'Dashboard',
			'orders'          => 'Orders',
			'downloads'       => 'Downloads',
			'edit-address'    => 'Addresses',
			'edit-account'    => 'Account details',
			'customer-logout' => 'Logout',
		), wc_get_account_menu_items() );
	}

	/**
	 * Test wc_get_account_orders_columns().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_get_account_orders_columns() {
		$this->assertEquals( array(
			'order-number'  => 'Order',
			'order-date'    => 'Date',
			'order-status'  => 'Status',
			'order-total'   => 'Total',
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
			'download-file'      => 'File',
			'download-remaining' => 'Downloads remaining',
			'download-expires'   => 'Expires',
			'download-product'   => 'Product',
		), wc_get_account_downloads_columns() );
	}
}
