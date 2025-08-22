<?php
/**
 * Unit tests for the WC_Admin_Functions_Test class
 *
 * @package WooCommerce\Tests\Admin
 */

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Class WC_Admin_Functions_Test
 */
class WC_Admin_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp(): void {
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
		$order->set_status( OrderStatus::PROCESSING );
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
		$order->set_status( OrderStatus::ON_HOLD );
		$order_item_id = $order->add_product( $product, 10 );
		$order_item    = new WC_Order_Item_Product( $order_item_id );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 100, $product->get_stock_quantity() );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 90, $product->get_stock_quantity() );

		$args = array(
			'amount'         => 10,
			'order_id'       => $order->get_id(),
			'line_items'     => array(
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
		$order->set_status( OrderStatus::ON_HOLD );
		$order_item_id = $order->add_product( $product, 10 );
		$order_item    = new WC_Order_Item_Product( $order_item_id );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 100, $product->get_stock_quantity() );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 90, $product->get_stock_quantity() );

		$args = array(
			'amount'         => 10,
			'order_id'       => $order->get_id(),
			'line_items'     => array(
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
		$order->set_status( OrderStatus::ON_HOLD );
		$order_item_id = $order->add_product( $product, 10 );
		$order_item    = new WC_Order_Item_Product( $order_item_id );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 100, $product->get_stock_quantity() );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 90, $product->get_stock_quantity() );

		$args = array(
			'amount'         => 10,
			'order_id'       => $order->get_id(),
			'line_items'     => array(
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
		$order->set_status( OrderStatus::ON_HOLD );
		$order_item_id = $order->add_product( $product, 10 );
		$order_item    = new WC_Order_Item_Product( $order_item_id );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 100, $product->get_stock_quantity() );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 90, $product->get_stock_quantity() );

		$args = array(
			'amount'         => 10,
			'order_id'       => $order->get_id(),
			'line_items'     => array(
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

		// Stocks should have been increased to original amount minus the partially refunded stock.
		$this->assertEquals( 95, $product->get_stock_quantity() );
	}

	/**
	 * Test adjust line item function when order item is refunded with restock and then update order.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/29502.
	 */
	public function test_admin_refund_with_restock_and_update_order() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 100 );
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->save();

		$order = WC_Helper_Order::create_order();

		$order->set_status( OrderStatus::ON_HOLD );
		$order_item_id = $order->add_product( $product, 10 );
		$order_item    = new WC_Order_Item_Product( $order_item_id );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 100, $product->get_stock_quantity() );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 90, $product->get_stock_quantity() );

		$args = array(
			'amount'         => 10,
			'order_id'       => $order->get_id(),
			'line_items'     => array(
				$order_item_id => array(
					'qty'          => 5,
					'refund_total' => 0,
				),
			),
			'refund_payment' => false,
			'restock_items'  => true,
		);

		wc_create_refund( $args );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );

		// Stocks should remain unchanged from after restocking via refund operation.
		$this->assertEquals( 95, $product->get_stock_quantity() );

		// Repeating steps above again to make sure nothing changes.
		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );

		// Stocks should remain unchanged from after restocking via refund operation.
		$this->assertEquals( 95, $product->get_stock_quantity() );
	}

	/**
	 * Test adjust line item function when order item is refunded without restock and then update order.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/29502.
	 */
	public function test_admin_refund_without_restock_and_update_order() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 100 );
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->save();

		$order = WC_Helper_Order::create_order();

		$order->set_status( OrderStatus::ON_HOLD );
		$order_item_id = $order->add_product( $product, 10 );
		$order_item    = new WC_Order_Item_Product( $order_item_id );

		// Stocks have not reduced yet.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 100, $product->get_stock_quantity() );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 90, $product->get_stock_quantity() );

		$args = array(
			'amount'         => 10,
			'order_id'       => $order->get_id(),
			'line_items'     => array(
				$order_item_id => array(
					'qty'          => 5,
					'refund_total' => 0,
				),
			),
			'refund_payment' => false,
			'restock_items'  => false,
		);

		wc_create_refund( $args );

		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );

		// Stocks should remain unchanged from the original order.
		$this->assertEquals( 90, $product->get_stock_quantity() );

		// Repeating steps above again to make sure nothing changes.
		wc_maybe_adjust_line_item_product_stock( $order_item );

		$product = wc_get_product( $product->get_id() );

		// Stocks should remain unchanged from the original order.
		$this->assertEquals( 90, $product->get_stock_quantity() );
	}

	/**
	 * Test adjust line item function that results in negative stock quantities.
	 * Ensures stock adjustments work correctly when inventory goes negative.
	 *
	 * Covers the scenario where:
	 * 1. Product starts with stock of 1
	 * 2. Order line item quantity is edited from 2 to 5 (increasing by 3)
	 * 3. Stock should go negative: 1 - 3 = -2
	 */
	public function test_wc_maybe_adjust_line_item_product_stock_negative_inventory() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );
		$product->set_backorders( 'yes' );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::PROCESSING );
		$order_item_id = $order->add_product( $product, 2 );
		$order_item    = new WC_Order_Item_Product( $order_item_id );

		// Verify initial stock.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 1, $product->get_stock_quantity() );

		// Actually reduce stock for this order item (simulating initial order processing).
		wc_maybe_adjust_line_item_product_stock( $order_item, 2 );

		// This should have made stock negative: 1 - 2 = -1.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( -1, $product->get_stock_quantity(), 'Stock should be -1 after reducing by 2 from initial stock of 1' );

		// Now edit the order item quantity from 2 to 5 (increasing by 3).
		$order_item->set_quantity( 5 );
		$result = wc_maybe_adjust_line_item_product_stock( $order_item, 5 );

		// Verify the function returned expected change data.
		$this->assertIsArray( $result, 'Stock adjustment should return change data array' );
		$this->assertArrayHasKey( 'from', $result, 'Result should contain from value' );
		$this->assertArrayHasKey( 'to', $result, 'Result should contain to value' );

		// Verify stock was decreased by 3 more (from -1 to -4).
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( -4, $product->get_stock_quantity(), 'Stock should be adjusted from -1 to -4 when increasing order quantity by 3' );

		// Verify the _reduced_stock meta was updated.
		$order_item = new WC_Order_Item_Product( $order_item_id );
		$this->assertEquals( 5, $order_item->get_meta( '_reduced_stock', true ), 'Reduced stock meta should be updated to new quantity' );
	}

	/**
	 * Test adjust line item function - decrease order quantity with already negative stock.
	 * Tests the scenario where we reduce order quantity and return stock to negative inventory.
	 */
	public function test_wc_maybe_adjust_line_item_product_stock_negative_inventory_decrease_quantity() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );
		$product->set_backorders( 'yes' );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::PROCESSING );
		$order_item_id = $order->add_product( $product, 5 );
		$order_item    = new WC_Order_Item_Product( $order_item_id );

		// Actually reduce stock for this order item (simulating initial order processing).
		wc_maybe_adjust_line_item_product_stock( $order_item, 5 );

		// This should have made stock negative: 1 - 5 = -4.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( -4, $product->get_stock_quantity(), 'Stock should be -4 after reducing by 5 from initial stock of 1' );

		// Now edit the order item quantity from 5 to 3 (decreasing by 2).
		$order_item->set_quantity( 3 );
		$result = wc_maybe_adjust_line_item_product_stock( $order_item, 3 );

		// Verify the function returned expected change data.
		$this->assertIsArray( $result, 'Stock adjustment should return change data array' );

		// Verify stock was increased by 2 (from -4 to -2).
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( -2, $product->get_stock_quantity(), 'Stock should be adjusted from -4 to -2 when decreasing order quantity by 2' );

		// Verify the _reduced_stock meta was updated.
		$order_item = new WC_Order_Item_Product( $order_item_id );
		$this->assertEquals( 3, $order_item->get_meta( '_reduced_stock', true ), 'Reduced stock meta should be updated to new quantity' );
	}

	/**
	 * Test adjust line item function when backorders are NOT enabled.
	 * Tests the scenario where stock adjustments are made when backorders are disabled.
	 * WooCommerce should still handle the stock adjustment calculations correctly.
	 */
	public function test_wc_maybe_adjust_line_item_product_stock_no_backorders() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );
		$product->set_backorders( 'no' );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::PROCESSING );
		$order_item_id = $order->add_product( $product, 2 );
		$order_item    = new WC_Order_Item_Product( $order_item_id );

		// Actually reduce stock for this order item (simulating initial order processing).
		wc_maybe_adjust_line_item_product_stock( $order_item, 2 );

		// This should have made stock negative: 1 - 2 = -1 (even with backorders disabled, stock can go negative).
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( -1, $product->get_stock_quantity(), 'Stock should be -1 after reducing by 2 from initial stock of 1, even with backorders disabled' );

		// Now edit the order item quantity from 2 to 4 (increasing by 2).
		$order_item->set_quantity( 4 );
		$result = wc_maybe_adjust_line_item_product_stock( $order_item, 4 );

		// Verify the function returned expected change data.
		$this->assertIsArray( $result, 'Stock adjustment should return change data array' );

		// Verify stock was decreased by 2 more (from -1 to -3).
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( -3, $product->get_stock_quantity(), 'Stock should be adjusted from -1 to -3 when increasing order quantity by 2, regardless of backorder setting' );

		// Verify the _reduced_stock meta was updated.
		$order_item = new WC_Order_Item_Product( $order_item_id );
		$this->assertEquals( 4, $order_item->get_meta( '_reduced_stock', true ), 'Reduced stock meta should be updated to new quantity' );
	}
}
