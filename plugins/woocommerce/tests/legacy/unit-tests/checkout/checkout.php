<?php
/**
 * Checkout tests.
 *
 * @package WooCommerce\Tests\Checkout
 */

declare(strict_types=1);

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Class WC_Checkout
 */
class WC_Tests_Checkout extends WC_Unit_Test_Case {
	/**
	 * TearDown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->cart->empty_cart();
	}

	/**
	 * Setup.
	 */
	public function setUp(): void {
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
				'payment_method' => WC_Gateway_COD::ID,
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
		$this->assertEquals( OrderStatus::PENDING, $order->get_status() );
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
		$order->set_status( OrderStatus::CANCELLED );
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
		$order->set_status( OrderStatus::PROCESSING );
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
				'payment_method' => WC_Gateway_COD::ID,
				'billing_email'  => 'a@b.com',
			)
		);

		// Assertions whether the first order was created successfully.
		$this->assertNotWPError( $order_id );
		$order = wc_get_order( $order_id );

		$this->assertEquals( 9, $order->get_item_count() );
		$this->assertEquals( OrderStatus::PENDING, $order->get_status() );
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

	/**
	 * Test that a customer-chosen value for an "any" variation attribute is preserved on the order line item.
	 */
	public function test_create_order_preserves_customer_chosen_any_attribute_value(): void {
		$parent_product = WC_Helper_Product::create_variation_product();

		// Find the first variation that has pa_number as "any" (stored as '').
		$any_number_variation_id = 0;
		$any_number_variation    = null;
		foreach ( $parent_product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( '' === ( $child->get_variation_attributes()['attribute_pa_number'] ?? null ) ) {
				$any_number_variation_id = $child_id;
				$any_number_variation    = $child;
				break;
			}
		}
		$this->assertGreaterThan( 0, $any_number_variation_id, 'Expected a variation with an "any" pa_number attribute.' );

		// Build cart attributes: use the variation's fixed values for non-number attributes, supply an arbitrary value for
		// any remaining "any" attributes, and the customer's chosen value (1) for the "any" pa_number attribute under test.
		$cart_variation = array(
			'attribute_pa_size'   => 'small',
			'attribute_pa_colour' => 'red',
			'attribute_pa_number' => '1',
		);
		WC()->cart->add_to_cart( $parent_product->get_id(), 1, $any_number_variation_id, $cart_variation );

		$order_id = WC_Checkout::instance()->create_order(
			array(
				'payment_method' => WC_Gateway_COD::ID,
				'billing_email'  => 'a@b.com',
			)
		);
		$this->assertNotWPError( $order_id );

		// Re-read from storage to assert the persisted value.
		/** @var WC_Order_Item_Product[] $items */
		$items = wc_get_order( $order_id )->get_items();
		$this->assertCount( 1, $items );

		// The chosen value must survive; premature set_product() overwrites it with '' (empty).
		$this->assertSame(
			'1',
			array_values( $items )[0]->get_meta( 'pa_number' ),
			'The customer-chosen value for an "Any" variation attribute must be persisted on the order line item.'
		);
	}

	/**
	 * @testdox Should not save checkout shipping fields as customer meta.
	 *
	 * @throws ReflectionException When unable to reflect the checkout method.
	 */
	public function test_process_customer_does_not_save_checkout_shipping_fields_as_customer_meta(): void {
		$user_id = wp_create_user( 'checkout-shipping-fields-customer', 'password', 'checkout-shipping-fields-customer@example.com' );
		$this->assertNotWPError( $user_id );
		wp_set_current_user( $user_id );

		$process_customer = new ReflectionMethod( WC_Checkout::class, 'process_customer' );
		$process_customer->setAccessible( true );
		$process_customer->invoke(
			WC_Checkout::instance(),
			array(
				'billing_first_name' => 'Jane',
				'billing_last_name'  => 'Customer',
				'billing_email'      => 'checkout-shipping-fields-customer@example.com',
				'shipping_address_1' => '123 Test Street',
				'shipping_custom'    => 'custom shipping value',
				'shipping_method'    => array( 'flat_rate:1' ),
				'shipping_total'     => '5.00',
				'shipping_tax'       => '0.50',
			)
		);

		$this->assertSame(
			'123 Test Street',
			get_user_meta( $user_id, 'shipping_address_1', true ),
			'Regular shipping address fields should still be saved as customer meta.'
		);
		$this->assertSame(
			'custom shipping value',
			get_user_meta( $user_id, 'shipping_custom', true ),
			'Custom shipping fields should still be saved as customer meta.'
		);
		$this->assertSame(
			'',
			get_user_meta( $user_id, 'shipping_method', true ),
			'The selected checkout shipping method should not be saved as customer meta.'
		);
		$this->assertSame(
			'',
			get_user_meta( $user_id, 'shipping_total', true ),
			'Checkout shipping totals should not be saved as customer meta.'
		);
		$this->assertSame(
			'',
			get_user_meta( $user_id, 'shipping_tax', true ),
			'Checkout shipping taxes should not be saved as customer meta.'
		);

		wp_set_current_user( 0 );
	}
}
