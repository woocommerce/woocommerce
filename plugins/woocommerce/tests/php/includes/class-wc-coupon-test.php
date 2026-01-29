<?php

/**
 * Tests for WC_Coupon.
 *
 * See also ../../legacy/unit-tests/coupon/coupon.php for other related tests.
 */
class WC_Coupon_Tests extends WC_Unit_Test_Case {

	/**
	 * @testdox parse_short_info returns correct values for valid JSON with all fields.
	 */
	public function test_parse_short_info_returns_correct_values_for_valid_json_with_all_fields(): void {
		$info = wp_json_encode( array( 123, 'TESTCODE', 'percent', 15.5, true ) );

		$result = WC_Coupon::parse_short_info( $info );

		$this->assertSame( 123, $result['id'] );
		$this->assertSame( 'TESTCODE', $result['code'] );
		$this->assertSame( 'percent', $result['discount_type'] );
		$this->assertSame( 15.5, $result['amount'] );
		$this->assertTrue( $result['free_shipping'] );
	}

	/**
	 * @testdox parse_short_info returns fixed_cart as default discount type when type is null.
	 */
	public function test_parse_short_info_returns_fixed_cart_as_default_discount_type(): void {
		$info = wp_json_encode( array( 1, 'CODE', null, 10.0 ) );

		$result = WC_Coupon::parse_short_info( $info );

		$this->assertSame( 'fixed_cart', $result['discount_type'] );
	}

	/**
	 * @testdox parse_short_info returns false for free_shipping when not present in JSON.
	 */
	public function test_parse_short_info_returns_false_for_free_shipping_when_not_present(): void {
		$info = wp_json_encode( array( 1, 'CODE', 'percent', 10.0 ) );

		$result = WC_Coupon::parse_short_info( $info );

		$this->assertFalse( $result['free_shipping'] );
	}

	/**
	 * @testdox parse_short_info returns default values for malformed JSON when return_defaults_on_error is true.
	 */
	public function test_parse_short_info_returns_defaults_for_malformed_json(): void {
		$result = WC_Coupon::parse_short_info( 'not valid json', true );

		$this->assertSame( 0, $result['id'] );
		$this->assertSame( '', $result['code'] );
		$this->assertSame( 'fixed_cart', $result['discount_type'] );
		$this->assertSame( 0.0, $result['amount'] );
		$this->assertFalse( $result['free_shipping'] );
	}

	/**
	 * @testdox parse_short_info throws exception for malformed JSON when return_defaults_on_error is false.
	 */
	public function test_parse_short_info_throws_exception_for_malformed_json(): void {
		$this->expectException( \InvalidArgumentException::class );

		WC_Coupon::parse_short_info( 'not valid json', false );
	}

	/**
	 * @testdox parse_short_info does not validate amount values, allowing values over 100 for percent type.
	 */
	public function test_parse_short_info_does_not_validate_amount(): void {
		$info = wp_json_encode( array( 1, 'CODE', 'percent', 150.0 ) );

		$result = WC_Coupon::parse_short_info( $info );

		$this->assertSame( 150.0, $result['amount'], 'parse_short_info should not validate amount values' );
	}

	/**
	 * @testdox parse_short_info does not validate amount values, allowing negative amounts.
	 */
	public function test_parse_short_info_allows_negative_amounts(): void {
		$info = wp_json_encode( array( 1, 'CODE', 'fixed_cart', -10.0 ) );

		$result = WC_Coupon::parse_short_info( $info );

		$this->assertSame( -10.0, $result['amount'], 'parse_short_info should not validate amount values' );
	}

	/**
	 * @testdox set_short_info uses parse_short_info internally and applies validation.
	 */
	public function test_set_short_info_validates_amount(): void {
		$coupon = new WC_Coupon();
		$info   = wp_json_encode( array( 1, 'CODE', 'percent', 150.0 ) );

		$this->expectException( \WC_Data_Exception::class );

		$coupon->set_short_info( $info );
	}
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
}
