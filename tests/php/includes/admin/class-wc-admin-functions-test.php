<?php
/**
 * Unit tests for the WC_Admin_Functions_Test class
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * Class WC_Admin_Functions_Test_Test
 */
class WC_Admin_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp() {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/admin/wc-admin-functions.php';
	}

	/**
	 * Test wc_get_current_admin_url() function.
	 */
	public function test_wc_get_current_admin_url() {
		// Since REQUEST_URI is empty on unit tests it should return an empty string.
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			$this->assertEquals( '', wc_get_current_admin_url() );
		}

		// Test with REQUEST_URI.
		$default_uri            = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=wc-admin&foo=bar';
		$this->assertEquals( admin_url( 'admin.php?page=wc-admin&foo=bar' ), wc_get_current_admin_url() );

		// Test if nonce gets removed.
		$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=wc-admin&_wpnonce=xxxxxxxxxxxx';
		$this->assertEquals( admin_url( 'admin.php?page=wc-admin' ), wc_get_current_admin_url() );

		// Restore REQUEST_URI.
		$_SERVER['REQUEST_URI'] = $default_uri;
	}

	/**
	 * Test adjust line item function when order does not have meta `_reduced_stock` already.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/27445.
	 */
	public function test_wc_maybe_adjust_line_item_product_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1000 );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'processing' );
		$order_item_id = $order->add_product( $product, 10 );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 1000, $product->get_stock_quantity() );

		$order_item = new WC_Order_Item_Product( $order_item_id );
		wc_maybe_adjust_line_item_product_stock( $order_item );

		// Stocks should have been reduced now.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 990, $product->get_stock_quantity() );
	}

}
