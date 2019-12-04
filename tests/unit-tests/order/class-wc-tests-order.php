<?php
/**
 * Class WC_Tests_Order file.
 *
 * @package WooCommerce/Tests
 */

/**
 * Class Functions.
 *
 * @package WooCommerce/Tests/Order
 * @since 3.9.0
 */
class WC_Tests_Order extends WC_Unit_Test_Case {
	/**
	 * TearDown.
	 */
	public function tearDown() {
		parent::tearDown();
		WC()->cart->empty_cart();
	}

	/**
	 * Test pending stock and `release_held_stock` and `record_held_stock` methods as well.
	 * @throws Exception When unable to create an order.
	 */
	public function test_pending_stock_for_order_with_multiple_product() {
		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_props( array( 'manage_stock' => true ) );
		$product1->set_stock_quantity( 10 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_props( array( 'manage_stock' => true ) );
		$product2->set_stock_quantity( 10 );
		$product2->save();

		WC()->cart->add_to_cart( $product1->get_id(), 9 );
		WC()->cart->add_to_cart( $product2->get_id(), 5 );
		$this->assertEquals( true, WC()->cart->check_cart_items() );

		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order(
			array(
				'payment_method' => 'cod',
				'billing_email' => 'a@b.com',
			)
		);

		$this->assertNotWPError( $order_id );
		$order_held_stock_keys = get_post_meta( $order_id, '_stock_held_keys', true );

		$product1_id = $product1->get_stock_managed_by_id();
		$product2_id = $product2->get_stock_managed_by_id();

		$this->assertEquals( true, in_array( $product1_id, array_keys( $order_held_stock_keys ) ) );
		$this->assertEquals( true, in_array( $product2_id, array_keys( $order_held_stock_keys ) ) );

		$this->assertEquals( 9, wc_get_held_stock_quantity( $product1 ) );
		$this->assertEquals( 5, wc_get_held_stock_quantity( $product2 ) );

		$order = wc_get_order( $order_id );
		$order->release_held_stock();

		$this->assertEquals( 0, wc_get_held_stock_quantity( $product1 ) );
		$this->assertEquals( 0, wc_get_held_stock_quantity( $product2 ) );
	}

	/**
	 * Test `release_held_stock` function of unmanaged product.
	 * @throws Exception When unable to create an order.
	 */
	public function test_release_held_of_unmanaged_product() {
		$product = WC_Helper_Product::create_simple_product();

		WC()->cart->add_to_cart( $product->get_id(), 9 );
		$this->assertEquals( true, WC()->cart->check_cart_items() );
		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order(
			array(
				'payment_method' => 'cod',
				'billing_email' => 'a@b.com',
			)
		);

		$this->assertNotWPError( $order_id );
		$order = wc_get_order( $order_id );
		$this->assertEquals( 0, wc_get_held_stock_quantity( $product ) );

		$order->release_held_stock();
		$this->assertEquals( 0, wc_get_held_stock_quantity( $product ) );
	}
}
