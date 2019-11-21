<?php
/**
 * Checkout tests.
 *
 * @package WooCommerce|Tests|Checkout
 */

/**
 * Class WC_Checkout
 */
class WC_Tests_Checkout extends WC_Unit_Test_Case {
	/**
	 * TearDown.
	 */
	public function tearDown() {
		parent::tearDown();
		WC()->cart->empty_cart();
	}

	/**
	 * Helper method to create a managed product and a order for that product.
	 *
	 * @return array( WC_Product, WC_Order ) array
	 * @throws Exception When unable to create an order.
	 */
	protected function create_order_for_managed_inventory_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_props( array( 'manage_stock' => true ) );
		$product->set_stock_quantity( 10 );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id(), 9 );
		$this->assertEquals( true, WC()->cart->check_cart_items() );

		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order(
			array(
				'payment_method' => 'cod',
				'billing_email' => 'a@b.com',
			)
		);

		// Assertions whether the order was created successfully.
		$this->assertNotWPError( $order_id );
		$order = new WC_Order( $order_id );

		return array( $product, $order );
	}

	/**
	 * Test when order is out stock because it is held by an order in pending status.
	 * @throws Exception When unable to create order.
	 */
	public function test_create_order_when_out_of_stock() {
		list( $product, $order ) = $this->create_order_for_managed_inventory_product();

		$this->assertEquals( 9, $order->get_item_count() );
		$this->assertEquals( 'pending', $order->get_status() );
		$this->assertEquals( 9, wc_get_held_stock_quantity( $product ) );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_stock_managed_by_id(), 2 );

		$this->assertEquals( false, WC()->cart->check_cart_items() );
	}

	/**
	 * Legacy version for test `test_create_order_when_out_of_stock` above.
	 * @throws Exception When unable to create order.
	 */
	public function test_create_order_when_out_of_stock_legacy() {
		add_filter( 'enable_hold_stock_3_9', '__return_false' );
		$this->test_create_order_when_out_of_stock();
	}

	/**
	 * Test if pending stock is cleared when order is cancelled.
	 *
	 * @throws Exception When unable to create order.
	 */
	public function test_pending_is_cleared_when_order_is_cancelled() {
		list( $product, $order ) = $this->create_order_for_managed_inventory_product();

		$this->assertEquals( 9, wc_get_held_stock_quantity( $product ) );
		$order->set_status( 'cancelled' );
		$order->save();

		$this->assertEquals( 0, wc_get_held_stock_quantity( $product ) );
		$this->assertEquals( 10, $product->get_stock_quantity() );

	}

	/**
	 * Test if pending stock is cleared when order is processing.
	 *
	 * @throws Exception When unable to create order.
	 */
	public function test_pending_is_cleared_when_order_processed() {
		list( $product, $order ) = $this->create_order_for_managed_inventory_product();

		$this->assertEquals( 9, wc_get_held_stock_quantity( $product ) );
		$order->set_status( 'processing' );
		$order->save();

		$this->assertEquals( 0, wc_get_held_stock_quantity( $product ) );
	}

	/**
	 * Test creating order from managed stock for variable product.
	 * @throws Exception When unable to create an order.
	 */
	public function test_create_order_for_variation_product() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variation = $parent_product->get_available_variations()[0];
		$variation = wc_get_product( $variation['variation_id'] );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 10 );
		$variation->save();
		WC()->cart->add_to_cart( $variation->get_id(), 9 );
		$this->assertEquals( true, WC()->cart->check_cart_items() );

		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order(
			array(
				'payment_method' => 'cod',
				'billing_email' => 'a@b.com',
			)
		);

		// Assertions whether the first order was created successfully.
		$this->assertNotWPError( $order_id );
		$order = new WC_Order( $order_id );

		$this->assertEquals( 9, $order->get_item_count() );
		$this->assertEquals( 'pending', $order->get_status() );
		$this->assertEquals( 9, wc_get_held_stock_quantity( $variation ) );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $variation->get_stock_managed_by_id(), 2 );

		$this->assertEquals( false, WC()->cart->check_cart_items() );
	}
}
