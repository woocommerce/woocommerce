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
	 * TearDown for tests.
	 */
	public function tearDown() {
		parent::tearDown();
		WC()->cart->empty_cart();
	}

	/**
	 * Setup for tests.
	 */
	public function setUp() {
		parent::setUp();
		WC()->cart->empty_cart();
	}

	/**
	 * Test if order can be created when a coupon with usage limit is applied.
	 *
	 * @throws Exception When unable to create order.
	 */
	public function test_create_order_with_limited_coupon() {
		$coupon_code = 'coupon4one';
		$coupon_data_store = WC_Data_Store::load( 'coupon' );
		$coupon = WC_Helper_Coupon::create_coupon(
			$coupon_code,
			array( 'usage_limit' => 1 )
		);
		$product = WC_Helper_Product::create_simple_product( true );
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );
		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order(
			array(
				'billing_email' => 'a@b.com',
				'payment_method' => 'dummy_payment_gateway',
			)
		);
		$this->assertNotWPError( $order_id );
		$order = new WC_Order( $order_id );
		$coupon_held_key = $order->get_data_store()->get_coupon_held_keys( $order );
		$this->assertEquals( count( $coupon_held_key ), 1 );
		$this->assertEquals( array_keys( $coupon_held_key )[0], $coupon->get_id() );
		$this->assertEquals( strpos( $coupon_held_key[ $coupon->get_id() ], '_coupon_held_' ), 0 );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon->get_id() ), 1 );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );
		$order2_id = $checkout->create_order(
			array(
				'billing_email' => 'a@c.com',
				'payment_method' => 'dummy_payment_gateway',
			)
		);
		$this->assertWPError( $order2_id );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon->get_id() ), 1 );
	}

	/**
	 * Test when order is created with multiple coupon when usage limit for one is exhausted.
	 *
	 * @throws Exception When unable to create an order.
	 */
	public function test_create_order_with_multiple_limited_coupons() {
		$coupon_code1 = 'coupon1';
		$coupon_code2 = 'coupon2';
		$coupon_data_store = WC_Data_Store::load( 'coupon' );

		$coupon1 = WC_Helper_Coupon::create_coupon(
			$coupon_code1,
			array( 'usage_limit' => 2 )
		);
		$coupon2 = WC_Helper_Coupon::create_coupon(
			$coupon_code2,
			array( 'usage_limit' => 1 )
		);
		$product = WC_Helper_Product::create_simple_product( true );
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon_code1 );
		WC()->cart->add_discount( $coupon_code2 );
		$checkout = WC_Checkout::instance();
		$order_id1 = $checkout->create_order(
			array(
				'billing_email' => 'a@b.com',
				'payment_method' => 'dummy_payment_gateway',
			)
		);

		$this->assertNotWPError( $order_id1 );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon1->get_id() ), 1 );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon2->get_id() ), 1 );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon_code1 );
		WC()->cart->add_discount( $coupon_code2 );

		$order2_id = $checkout->create_order(
			array(
				'billing_email' => 'a@b.com',
				'payment_method' => 'dummy_payment_gateway',
			)
		);

		$this->assertWPError( $order2_id );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon1->get_id() ), 1 );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon2->get_id() ), 1 );
	}

	/**
	 * Helper function to return 0.01.
	 *
	 * @return float
	 */
	public function __return_0_01() {
		return 0.01;
	}

}
