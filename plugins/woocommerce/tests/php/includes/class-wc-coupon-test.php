<?php

/**
 * Tests for WC_Coupon.
 *
 * See also ../../legacy/unit-tests/coupon/coupon.php for other related tests.
 */
class WC_Coupon_Tests extends WC_Unit_Test_Case {
	/**
	 * If a coupon is applied to an order where one or more products have been deleted, the operation should still
	 * succeed.
	 *
	 * However, the coupon will have no impact on any line items referencing the deleted product(s), since in most cases
	 * the product's eligibility can no longer be assessed (therefore, it is up to the merchant to manually adjust if
	 * this is problematic).
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/27077
	 *
	 * @return void
	 */
	public function test_deleted_products_do_not_prevent_application_of_coupons(): void {
		// Test order will have one product added already (price: 10, quantity: 4).
		$order         = WC_Helper_Order::create_order();
		$extra_product = WC_Helper_Product::create_simple_product();
		$coupon        = WC_Helper_Coupon::create_coupon(
			'look_after_the_pennies',
			array(
				'discount_type' => 'percent',
				'coupon_amount' => 10,
			)
		);

		// Add our further product to the order, but then delete the product itself.
		$order->add_product( $extra_product );
		$order->save();
		wp_delete_post( $extra_product->get_id(), true );

		$this->assertTrue(
			$order->apply_coupon( $coupon ),
			'The coupon was successfully applied to an order containing a deleted product, without triggering an error.'
		);

		// Both products have a cost of $10. The first item has a quantity of 4 units ($40). So, the 10% discount
		// should give an actual discount total of $4 (the second line item is excluded from the calculation, because
		// its product was deleted).
		$this->assertEquals(
			4,
			$order->get_discount_total(),
			'Line items associated with deleted products are not included in the discount calculation.'
		);
	}

	/**
	 * Test that free shipping coupon is applied correctly to manual orders.
	 * 
	 * This test validates that free shipping coupons should reduce shipping costs 
	 * to zero when applied to manually created orders in the admin interface.
	 */
	public function test_free_shipping_coupon_applied_to_manual_order() {
		// Create a simple product for testing (use default $10 price)
		$product = WC_Helper_Product::create_simple_product();

		// Create a free shipping coupon
		$coupon = WC_Helper_Coupon::create_coupon( 'freeship' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( '0.00' );
		$coupon->set_free_shipping( true );
		$coupon->save();

		// Create a manual order using the helper which includes shipping
		$order = WC_Helper_Order::create_order( 1, $product );
		
		// Verify initial state - order should have shipping cost
		$initial_shipping_total = $order->get_shipping_total();
		$initial_order_total = $order->get_total();

		// The helper creates an order with $40 product total (4 x $10) + $10 shipping = $50 total
		$this->assertEquals( 10.00, floatval( $initial_shipping_total ), 'Initial shipping cost should be $10.00' );
		$this->assertEquals( 50.00, floatval( $initial_order_total ), 'Initial order total should be $50.00' );

		// Apply the free shipping coupon
		$result = $order->apply_coupon( $coupon );
		
		// Verify coupon was applied successfully
		$this->assertTrue( $result, 'Free shipping coupon should be applied successfully to manual order' );

		// Recalculate totals after coupon application
		$order->calculate_totals();
		
		// Get totals after coupon application
		$final_shipping_total = $order->get_shipping_total();
		$final_order_total = $order->get_total();

		// CRITICAL TEST: This assertion will FAIL if the bug exists
		// When free shipping coupon is applied, shipping total should become 0
		$this->assertEquals( 0.00, floatval( $final_shipping_total ), 'Shipping cost should be $0.00 after applying free shipping coupon' );

		// Order total should be reduced by shipping amount after free shipping is applied
		$this->assertEquals( 40.00, floatval( $final_order_total ), 'Order total should be $40.00 (product only) after free shipping coupon' );

		// Verify coupon is listed in order coupons
		$applied_coupons = $order->get_coupon_codes();
		$this->assertContains( 'freeship', $applied_coupons, 'Free shipping coupon should be listed in applied coupons' );

		// Cleanup
		WC_Helper_Order::delete_order( $order->get_id() );
		$coupon->delete( true );
	}

	/**
	 * Test free shipping coupon with manually added shipping item.
	 * 
	 * This simulates the exact scenario where shipping is manually added 
	 * to an order via the admin interface.
	 */
	public function test_free_shipping_coupon_with_manual_shipping() {
		// Setup shipping zone and method for testing
		WC_Helper_Shipping::create_simple_flat_rate();
		
		// Create a simple product
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 25.00 );
		$product->save();

		// Create a free shipping coupon
		$coupon = WC_Helper_Coupon::create_coupon( 'freeship' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( '0.00' );
		$coupon->set_free_shipping( true );
		$coupon->save();

		// Create a basic order without using the full helper (to manually control shipping)
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1'; // Required for order creation
		$order = wc_create_order( array( 'status' => 'pending' ) );

		// Add product to order manually
		$item = new WC_Order_Item_Product();
		$item->set_props( array(
			'product'  => $product,
			'quantity' => 1,
			'subtotal' => 25.00,
			'total'    => 25.00,
		) );
		$order->add_item( $item );

		// Set billing address
		$order->set_billing_first_name( 'John' );
		$order->set_billing_last_name( 'Doe' );
		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'NY' );
		$order->set_billing_city( 'New York' );
		$order->set_billing_postcode( '10001' );

		// Add shipping manually (as done in admin interface)
		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_props( array(
			'method_title' => 'Flat rate',
			'method_id'    => 'flat_rate',
			'total'        => '10.00',
		) );
		$order->add_item( $shipping_item );

		// Set totals manually
		$order->set_shipping_total( 10.00 );
		$order->set_total( 35.00 ); // product 25 + shipping 10
		$order->save();

		// Verify initial state
		$initial_shipping_total = $order->get_shipping_total();
		$initial_total = $order->get_total();
		
		$this->assertEquals( 10.00, floatval( $initial_shipping_total ), 'Initial shipping should be $10.00' );
		$this->assertEquals( 35.00, floatval( $initial_total ), 'Initial total should be $35.00' );

		// Apply the free shipping coupon
		$result = $order->apply_coupon( $coupon );
		$this->assertTrue( $result, 'Free shipping coupon should apply successfully' );

		// Recalculate totals
		$order->calculate_totals();

		// Check final state
		$final_shipping_total = $order->get_shipping_total();
		$final_total = $order->get_total();

		// CRITICAL ASSERTION: Free shipping coupon should make shipping $0
		// This will FAIL if the bug exists in manual orders
		$this->assertEquals( 0.00, floatval( $final_shipping_total ), 'Shipping should be $0.00 after free shipping coupon' );
		$this->assertEquals( 25.00, floatval( $final_total ), 'Total should be $25.00 (product only) after free shipping coupon' );

		// Verify coupon is applied
		$coupons = $order->get_coupon_codes();
		$this->assertContains( 'freeship', $coupons, 'Free shipping coupon should be in applied coupons' );

		// Cleanup
		$order->delete( true );
		$product->delete( true );
		$coupon->delete( true );
		WC_Helper_Shipping::delete_simple_flat_rate();
	}

	/**
	 * Test that regular coupons don't affect shipping costs.
	 * 
	 * Control test to ensure normal coupon behavior works correctly.
	 */
	public function test_regular_coupon_does_not_affect_shipping() {
		// Create product and regular (non-free-shipping) coupon
		$product = WC_Helper_Product::create_simple_product();

		$coupon = WC_Helper_Coupon::create_coupon( 'save5' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( '5.00' );
		$coupon->set_free_shipping( false );
		$coupon->save();

		// Create order using helper
		$order = WC_Helper_Order::create_order( 1, $product );
		
		$initial_shipping = $order->get_shipping_total();
		$this->assertEquals( 10.00, floatval( $initial_shipping ), 'Initial shipping should be $10.00' );

		// Apply regular coupon
		$result = $order->apply_coupon( $coupon );
		$this->assertTrue( $result, 'Regular coupon should apply successfully' );

		$order->calculate_totals();

		// Shipping should remain unchanged with regular coupon
		$final_shipping = $order->get_shipping_total();
		$this->assertEquals( 10.00, floatval( $final_shipping ), 'Shipping should remain $10.00 with regular coupon' );

		// But discount should be applied to order total
		$discount_total = $order->get_discount_total();
		$this->assertEquals( 5.00, floatval( $discount_total ), 'Discount should be $5.00' );

		// Cleanup
		WC_Helper_Order::delete_order( $order->get_id() );
		$coupon->delete( true );
	}
}
