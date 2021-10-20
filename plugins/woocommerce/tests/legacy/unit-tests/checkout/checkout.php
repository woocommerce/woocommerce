<?php
/**
 * Checkout tests.
 *
 * @package WooCommerce\Tests\Checkout
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
	 * Setup.
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
		$coupon_code       = 'coupon4one';
		$coupon_data_store = WC_Data_Store::load( 'coupon' );
		$coupon            = WC_Helper_Coupon::create_coupon(
			$coupon_code,
			array( 'usage_limit' => 1 )
		);
		$product           = WC_Helper_Product::create_simple_product( true );
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );
		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order(
			array(
				'billing_email'  => 'a@b.com',
				'payment_method' => 'dummy_payment_gateway',
			)
		);
		$this->assertNotWPError( $order_id );
		$order           = new WC_Order( $order_id );
		$coupon_held_key = $order->get_data_store()->get_coupon_held_keys( $order );
		$this->assertEquals( count( $coupon_held_key ), 1 );
		$this->assertEquals( array_keys( $coupon_held_key )[0], $coupon->get_id() );
		$this->assertEquals( strpos( $coupon_held_key[ $coupon->get_id() ], '_coupon_held_' ), 0 );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon->get_id() ), 1 );

		$order2_id = $checkout->create_order(
			array(
				'billing_email'  => 'a@c.com',
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
		$coupon_code1      = 'coupon1';
		$coupon_code2      = 'coupon2';
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
		$checkout  = WC_Checkout::instance();
		$order_id1 = $checkout->create_order(
			array(
				'billing_email'  => 'a@b.com',
				'payment_method' => 'dummy_payment_gateway',
			)
		);

		$this->assertNotWPError( $order_id1 );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon1->get_id() ), 1 );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon2->get_id() ), 1 );

		$order2_id = $checkout->create_order(
			array(
				'billing_email'  => 'a@b.com',
				'payment_method' => 'dummy_payment_gateway',
			)
		);

		$this->assertWPError( $order2_id );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon1->get_id() ), 1 );
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon2->get_id() ), 1 );
	}

	/**
	 * Test when `usage_count` meta is deleted for some reason.
	 *
	 * @throws Exception When unable to create order.
	 */
	public function test_create_order_with_usage_limit_deleted() {
		$coupon_code = 'coupon4one';
		$coupon_data_store = WC_Data_Store::load( 'coupon' );
		$coupon = WC_Helper_Coupon::create_coupon(
			$coupon_code,
			array( 'usage_limit' => 1 )
		);

		delete_post_meta( $coupon->get_id(), 'usage_count' );

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
		$this->assertEquals( $coupon_data_store->get_tentative_usage_count( $coupon->get_id() ), 1 );
	}

	/**
	 * Test usage limit for guest users usage limit per user is set.
	 *
	 * @throws Exception When unable to create order.
	 */
	public function test_usage_limit_per_user_for_guest() {
		wp_set_current_user( 0 );
		wc_clear_notices();
		$coupon_code = 'coupon4one';
		$coupon = WC_Helper_Coupon::create_coupon(
			$coupon_code,
			array( 'usage_limit_per_user' => 1 )
		);
		$product = WC_Helper_Product::create_simple_product( true );
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );
		$checkout = WC_Checkout::instance();
		$posted_data = array(
			'billing_email' => 'a@b.com',
			'payment_method' => 'dummy_payment_gateway',
		);
		$order_id = $checkout->create_order( $posted_data );
		$this->assertNotWPError( $order_id );

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );
		WC()->cart->check_customer_coupons( $posted_data );
		$this->assertTrue( wc_has_notice( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK_GUEST ), 'error' ) );
	}

	/**
	 * Helper function to return 0.01.
	 *
	 * @return float
	 */
	public function __return_0_01() {
		return 0.01;
	}

	/**
	 * Helper method to create a managed product and a order for that product.
	 *
	 * @return array
	 * @throws Exception When unable to create an order .
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
				'billing_email'  => 'a@b.com',
			)
		);

		// Assertions whether the order was created successfully.
		$this->assertNotWPError( $order_id );
		$order = wc_get_order( $order_id );

		return array( $product, $order );
	}

	/**
	 * Test when order is out stock because it is held by an order in pending status.
	 *
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
	 *
	 * @throws Exception When unable to create an order.
	 */
	public function test_create_order_for_variation_product() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variation      = $parent_product->get_available_variations()[0];
		$variation      = wc_get_product( $variation['variation_id'] );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 10 );
		$variation->save();
		WC()->cart->add_to_cart(
			$variation->get_id(),
			9,
			0,
			array(
				'attribute_pa_colour' => 'red', // Set a value since this is an 'any' attribute.
				'attribute_pa_number' => '2', // Set a value since this is an 'any' attribute.
			)
		);
		$this->assertEquals( true, WC()->cart->check_cart_items() );

		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order(
			array(
				'payment_method' => 'cod',
				'billing_email'  => 'a@b.com',
			)
		);

		// Assertions whether the first order was created successfully.
		$this->assertNotWPError( $order_id );
		$order = wc_get_order( $order_id );

		$this->assertEquals( 9, $order->get_item_count() );
		$this->assertEquals( 'pending', $order->get_status() );
		$this->assertEquals( 9, wc_get_held_stock_quantity( $variation ) );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart(
			$variation->get_stock_managed_by_id(),
			2,
			0,
			array(
				'attribute_pa_colour' => 'red',
				'attribute_pa_number' => '2',
			)
		);

		$this->assertEquals( false, WC()->cart->check_cart_items() );
	}
}
