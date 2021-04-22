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

	/**
	 * Test adjust line item function when order item is deleted after a full refund with restock.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/27504.
	 */
	public function test_admin_delete_order_item_after_full_refund_restock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 100 );
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'on-hold' );
		$order_item_id = $order->add_product( $product, 10 );
		$order_item = new WC_Order_Item_Product( $order_item_id );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 100, $product->get_stock_quantity() );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 90, $product->get_stock_quantity() );

		$args = array(
			'amount'     => 10,
			'order_id'   => $order->get_id(),
			'line_items' => array(
				$order_item_id => array(
					'qty'          => 10,
					'refund_total' => 0,
				),
			),
			'refund_payment' => false,
			'restock_items'  => true,
		);

		wc_create_refund( $args );

		$order->remove_item( $order_item_id );
		$order->save();

		$order_item->delete_meta_data( '_reduced_stock' );

		wc_maybe_adjust_line_item_product_stock( $order_item, 0 );

		$product = wc_get_product( $product->get_id() );

		// Stocks should have been increased back to original level.
		$this->assertEquals( 100, $product->get_stock_quantity() );
	}

	/**
	 * Test adjust line item function when order item is deleted after a full refund with no restock.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/27504.
	 */
	public function test_admin_delete_order_item_after_full_refund_no_restock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 100 );
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'on-hold' );
		$order_item_id = $order->add_product( $product, 10 );
		$order_item = new WC_Order_Item_Product( $order_item_id );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 100, $product->get_stock_quantity() );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 90, $product->get_stock_quantity() );

		$args = array(
			'amount'     => 10,
			'order_id'   => $order->get_id(),
			'line_items' => array(
				$order_item_id => array(
					'qty'          => 10,
					'refund_total' => 0,
				),
			),
			'refund_payment' => false,
			'restock_items'  => false,
		);

		wc_create_refund( $args );

		$order->remove_item( $order_item_id );
		$order->save();

		wc_maybe_adjust_line_item_product_stock( $order_item, 0 );

		$product = wc_get_product( $product->get_id() );

		// Stocks should have been increased back to original level.
		$this->assertEquals( 100, $product->get_stock_quantity() );
	}

	/**
	 * Test adjust line item function when order item is deleted after a partial refund with restock.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/27504.
	 */
	public function test_admin_delete_order_item_after_partial_refund_restock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 100 );
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'on-hold' );
		$order_item_id = $order->add_product( $product, 10 );
		$order_item = new WC_Order_Item_Product( $order_item_id );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 100, $product->get_stock_quantity() );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 90, $product->get_stock_quantity() );

		$args = array(
			'amount'     => 10,
			'order_id'   => $order->get_id(),
			'line_items' => array(
				$order_item_id => array(
					'qty'          => 5,
					'refund_total' => 0,
				),
			),
			'refund_payment' => false,
			'restock_items'  => true,
		);

		wc_create_refund( $args );

		$order->remove_item( $order_item_id );
		$order->save();

		$order_item->update_meta_data( '_reduced_stock', 5 );

		wc_maybe_adjust_line_item_product_stock( $order_item, 0 );

		$product = wc_get_product( $product->get_id() );

		// Stocks should have been increased back to original level.
		$this->assertEquals( 100, $product->get_stock_quantity() );
	}

	/**
	 * Test adjust line item function when order item is deleted after a partial refund with no restock.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/27504.
	 */
	public function test_admin_delete_order_item_after_partial_refund_no_restock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 100 );
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'on-hold' );
		$order_item_id = $order->add_product( $product, 10 );
		$order_item = new WC_Order_Item_Product( $order_item_id );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 100, $product->get_stock_quantity() );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 90, $product->get_stock_quantity() );

		$args = array(
			'amount'     => 10,
			'order_id'   => $order->get_id(),
			'line_items' => array(
				$order_item_id => array(
					'qty'          => 5,
					'refund_total' => 0,
				),
			),
			'refund_payment' => false,
			'restock_items'  => false,
		);

		wc_create_refund( $args );

		$order->remove_item( $order_item_id );
		$order->save();

		$order_item->update_meta_data( '_reduced_stock', 5 );

		wc_maybe_adjust_line_item_product_stock( $order_item, 0 );

		$product = wc_get_product( $product->get_id() );

		// Stocks should have been increased to orignal amount minus the partially refunded stock.
		$this->assertEquals( 95, $product->get_stock_quantity() );
	}
}
