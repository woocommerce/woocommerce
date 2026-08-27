<?php

/**
 * Tests for WC_Coupon.
 *
 * See also ../../legacy/unit-tests/coupon/coupon.php for other related tests.
 */
class WC_Coupon_Tests extends WC_Unit_Test_Case {

	/**
	 * @testdox set_short_info validates amount and throws exception for invalid values.
	 */
	public function test_set_short_info_validates_amount(): void {
		$coupon = new WC_Coupon();
		$info   = wp_json_encode( array( 1, 'CODE', 'percent', 150.0 ) );

		$this->expectException( \WC_Data_Exception::class );

		$coupon->set_short_info( $info );
	}

	/**
	 * @testdox from_order_item returns a coupon with correct data from coupon_info meta.
	 */
	public function test_from_order_item_with_coupon_info(): void {
		$order_item = $this->createMock( WC_Order_Item_Coupon::class );
		$order_item->method( 'get_meta' )
			->willReturnCallback(
				function ( $key ) {
					if ( 'coupon_info' === $key ) {
						return wp_json_encode( array( 123, 'TESTCODE', 'percent', 25.5, true ) );
					}
					return '';
				}
			);

		$coupon = WC_Coupon::from_order_item( $order_item );

		$this->assertSame( 123, $coupon->get_id() );
		$this->assertSame( 'testcode', $coupon->get_code() ); // WC_Coupon lowercases codes.
		$this->assertSame( 'percent', $coupon->get_discount_type() );
		$this->assertSame( 25.5, (float) $coupon->get_amount() );
		$this->assertTrue( $coupon->get_free_shipping() );
	}

	/**
	 * @testdox from_order_item returns fixed_cart as default discount type when type is null.
	 */
	public function test_from_order_item_uses_fixed_cart_as_default_discount_type(): void {
		$order_item = $this->createMock( WC_Order_Item_Coupon::class );
		$order_item->method( 'get_meta' )
			->willReturnCallback(
				function ( $key ) {
					if ( 'coupon_info' === $key ) {
						return wp_json_encode( array( 1, 'CODE', null, 10.0 ) );
					}
					return '';
				}
			);

		$coupon = WC_Coupon::from_order_item( $order_item );

		$this->assertSame( 'fixed_cart', $coupon->get_discount_type() );
	}

	/**
	 * @testdox from_order_item returns false for free_shipping when not present in JSON.
	 */
	public function test_from_order_item_defaults_free_shipping_to_false(): void {
		$order_item = $this->createMock( WC_Order_Item_Coupon::class );
		$order_item->method( 'get_meta' )
			->willReturnCallback(
				function ( $key ) {
					if ( 'coupon_info' === $key ) {
						return wp_json_encode( array( 1, 'CODE', 'percent', 10.0 ) );
					}
					return '';
				}
			);

		$coupon = WC_Coupon::from_order_item( $order_item );

		$this->assertFalse( $coupon->get_free_shipping() );
	}

	/**
	 * @testdox from_order_item returns a coupon with correct data from legacy coupon_data meta.
	 */
	public function test_from_order_item_with_legacy_coupon_data(): void {
		$order_item = $this->createMock( WC_Order_Item_Coupon::class );
		$order_item->method( 'get_meta' )
			->willReturnCallback(
				function ( $key ) {
					if ( 'coupon_info' === $key ) {
						return '';
					}
					if ( 'coupon_data' === $key ) {
						return (object) array(
							'discount_type' => 'fixed_cart',
							'amount'        => 10.0,
							'free_shipping' => false,
						);
					}
					return '';
				}
			);

		$coupon = WC_Coupon::from_order_item( $order_item );

		$this->assertSame( 'fixed_cart', $coupon->get_discount_type() );
		$this->assertSame( 10.0, (float) $coupon->get_amount() );
		$this->assertFalse( $coupon->get_free_shipping() );
	}

	/**
	 * @testdox from_order_item returns a default coupon when no coupon meta exists.
	 */
	public function test_from_order_item_with_no_meta(): void {
		$order_item = $this->createMock( WC_Order_Item_Coupon::class );
		$order_item->method( 'get_meta' )->willReturn( '' );

		$coupon = WC_Coupon::from_order_item( $order_item );

		$this->assertSame( 'fixed_cart', $coupon->get_discount_type() );
		$this->assertSame( 0.0, (float) $coupon->get_amount() );
		$this->assertFalse( $coupon->get_free_shipping() );
	}

	/**
	 * @testdox from_order_item returns a default coupon when coupon_info contains malformed JSON.
	 */
	public function test_from_order_item_with_malformed_json(): void {
		$order_item = $this->createMock( WC_Order_Item_Coupon::class );
		$order_item->method( 'get_meta' )
			->willReturnCallback(
				function ( $key ) {
					if ( 'coupon_info' === $key ) {
						return 'not valid json';
					}
					return '';
				}
			);

		$coupon = WC_Coupon::from_order_item( $order_item );

		$this->assertSame( 'fixed_cart', $coupon->get_discount_type() );
		$this->assertSame( 0.0, (float) $coupon->get_amount() );
		$this->assertFalse( $coupon->get_free_shipping() );
	}

	/**
	 * @testdox from_order_item does not validate amount, allowing invalid percentages over 100.
	 */
	public function test_from_order_item_allows_invalid_percentage_amounts(): void {
		$order_item = $this->createMock( WC_Order_Item_Coupon::class );
		$order_item->method( 'get_meta' )
			->willReturnCallback(
				function ( $key ) {
					if ( 'coupon_info' === $key ) {
						return wp_json_encode( array( 1, 'CODE', 'percent', 150.0 ) );
					}
					return '';
				}
			);

		$coupon = WC_Coupon::from_order_item( $order_item );

		$this->assertSame( 150.0, (float) $coupon->get_amount(), 'from_order_item should not validate amount values' );
	}

	/**
	 * @testdox from_order_item does not validate amount, allowing negative amounts.
	 */
	public function test_from_order_item_allows_negative_amounts(): void {
		$order_item = $this->createMock( WC_Order_Item_Coupon::class );
		$order_item->method( 'get_meta' )
			->willReturnCallback(
				function ( $key ) {
					if ( 'coupon_info' === $key ) {
						return wp_json_encode( array( 1, 'CODE', 'fixed_cart', -10.0 ) );
					}
					return '';
				}
			);

		$coupon = WC_Coupon::from_order_item( $order_item );

		$this->assertSame( -10.0, (float) $coupon->get_amount(), 'from_order_item should not validate amount values' );
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

	/**
	 * @testdox set_amount removes leading zeros from numeric strings for clean display.
	 *
	 * @dataProvider data_provider_for_amount_leading_zeros
	 * @param mixed $input    The input amount.
	 * @param mixed $expected The expected stored amount.
	 */
	public function test_set_amount_removes_leading_zeros_from_coupon_amount(
		$input,
		$expected
	): void {
		$coupon = new WC_Coupon();
		$coupon->set_amount( $input );

		$this->assertSame( $expected, $coupon->get_amount() );
	}

	/**
	 * Data provider for leading zero trimming tests.
	 *
	 * @return array
	 */
	public function data_provider_for_amount_leading_zeros() {
		return array(
			'leading zeros like 050'      => array( '050', '50' ),
			'just zero'                   => array( '0', '0' ),
			'decimal with leading zero'   => array( '0.50', '0.50' ),
			'multiple leading zeros'      => array( '00.50', '0.50' ),
			'normal number without zeros' => array( '20', '20' ),
			'over 100 for non-percent'    => array( 150, '150' ),
			'empty string becomes zero'   => array( '', '0' ),
			'string 0.0'                  => array( '0.0', '0.0' ),
		);
	}

	/**
	 * @testdox set_amount throws exception for negative amounts.
	 */
	public function test_set_amount_throws_exception_for_negative_amounts(): void {
		$coupon = new WC_Coupon();

		$this->expectException( \WC_Data_Exception::class );

		$coupon->set_amount( -10.0 );
	}

	// -------------------------------------------------------------------------
	// Direct setter validation (single-property guards).
	// -------------------------------------------------------------------------

	/**
	 * @testdox set_minimum_amount throws exception when minimum exceeds existing maximum.
	 */
	public function test_set_minimum_amount_throws_when_exceeds_maximum(): void {
		$coupon = new WC_Coupon();
		$coupon->set_maximum_amount( '100.00' );

		try {
			$coupon->set_minimum_amount( '200.00' );
			$this->fail( 'Expected WC_Data_Exception was not thrown.' );
		} catch ( \WC_Data_Exception $e ) {
			$this->assertSame( 'coupon_invalid_minimum_amount', $e->getErrorCode() );
		}
	}

	/**
	 * @testdox set_maximum_amount throws exception when maximum is below existing minimum.
	 */
	public function test_set_maximum_amount_throws_when_below_existing_minimum(): void {
		$coupon = new WC_Coupon();
		$coupon->set_minimum_amount( '100.00' );

		try {
			$coupon->set_maximum_amount( '50.00' );
			$this->fail( 'Expected WC_Data_Exception was not thrown.' );
		} catch ( \WC_Data_Exception $e ) {
			$this->assertSame( 'coupon_invalid_maximum_amount', $e->getErrorCode() );
		}
	}

	/**
	 * @testdox set_minimum_amount succeeds when no maximum amount is set.
	 */
	public function test_set_minimum_amount_succeeds_when_no_maximum_set(): void {
		$coupon = new WC_Coupon();

		$coupon->set_minimum_amount( '200.00' );

		$this->assertSame( '200.00', $coupon->get_minimum_amount() );
	}

	/**
	 * @testdox set_minimum_amount succeeds when minimum is less than existing maximum.
	 */
	public function test_set_minimum_amount_succeeds_when_less_than_maximum(): void {
		$coupon = new WC_Coupon();
		$coupon->set_maximum_amount( '100.00' );

		$coupon->set_minimum_amount( '50.00' );

		$this->assertSame( '50.00', $coupon->get_minimum_amount() );
	}

	/**
	 * @testdox set_minimum_amount succeeds when maximum amount is zero (no upper limit).
	 */
	public function test_set_minimum_amount_succeeds_when_maximum_is_zero(): void {
		$coupon = new WC_Coupon();
		$coupon->set_maximum_amount( '0' );

		$coupon->set_minimum_amount( '999.00' );

		$this->assertSame( '999.00', $coupon->get_minimum_amount() );
	}

	/**
	 * @testdox set_minimum_amount succeeds when minimum equals maximum (boundary is inclusive).
	 */
	public function test_set_minimum_amount_succeeds_when_equal_to_maximum(): void {
		$coupon = new WC_Coupon();
		$coupon->set_maximum_amount( '100.00' );

		$coupon->set_minimum_amount( '100.00' );

		$this->assertSame( '100.00', $coupon->get_minimum_amount() );
	}

	// -------------------------------------------------------------------------
	// Atomic set_props() validation (both amounts supplied together).
	// -------------------------------------------------------------------------

	/**
	 * @testdox set_props allows raising both minimum and maximum when new minimum exceeds old maximum.
	 */
	public function test_set_props_allows_raising_both_minimum_and_maximum_together(): void {
		$coupon = new WC_Coupon();
		$coupon->set_minimum_amount( '100.00' );
		$coupon->set_maximum_amount( '200.00' );

		$result = $coupon->set_props(
			array(
				'minimum_amount' => '250.00',
				'maximum_amount' => '300.00',
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '250.00', $coupon->get_minimum_amount() );
		$this->assertSame( '300.00', $coupon->get_maximum_amount() );
	}

	/**
	 * @testdox set_props allows lowering both minimum and maximum together.
	 */
	public function test_set_props_allows_lowering_both_minimum_and_maximum_together(): void {
		$coupon = new WC_Coupon();
		$coupon->set_minimum_amount( '100.00' );
		$coupon->set_maximum_amount( '200.00' );

		$result = $coupon->set_props(
			array(
				'minimum_amount' => '50.00',
				'maximum_amount' => '75.00',
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '50.00', $coupon->get_minimum_amount() );
		$this->assertSame( '75.00', $coupon->get_maximum_amount() );
	}

	/**
	 * @testdox set_props rejects an invalid min/max pair and leaves both properties unchanged.
	 */
	public function test_set_props_rejects_invalid_pair_without_mutating_either_property(): void {
		$coupon = new WC_Coupon();
		$coupon->set_minimum_amount( '100.00' );
		$coupon->set_maximum_amount( '200.00' );

		$result = $coupon->set_props(
			array(
				'minimum_amount' => '300.00',
				'maximum_amount' => '150.00',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'coupon_invalid_minimum_amount', $result->get_error_code() );
		$this->assertSame( '100.00', $coupon->get_minimum_amount(), 'minimum_amount must not be mutated on failure' );
		$this->assertSame( '200.00', $coupon->get_maximum_amount(), 'maximum_amount must not be mutated on failure' );
	}

	/**
	 * @testdox set_props rejects a minimum-only update that would exceed the existing maximum.
	 */
	public function test_set_props_rejects_minimum_only_invalid_update(): void {
		$coupon = new WC_Coupon();
		$coupon->set_minimum_amount( '100.00' );
		$coupon->set_maximum_amount( '200.00' );

		$result = $coupon->set_props( array( 'minimum_amount' => '300.00' ) );

		$this->assertWPError( $result );
		$this->assertSame( '100.00', $coupon->get_minimum_amount(), 'minimum_amount must not be mutated on failure' );
	}

	/**
	 * @testdox set_props rejects a maximum-only update that would fall below the existing minimum.
	 */
	public function test_set_props_rejects_maximum_only_invalid_update(): void {
		$coupon = new WC_Coupon();
		$coupon->set_minimum_amount( '100.00' );
		$coupon->set_maximum_amount( '200.00' );

		$result = $coupon->set_props( array( 'maximum_amount' => '50.00' ) );

		$this->assertWPError( $result );
		$this->assertSame( '200.00', $coupon->get_maximum_amount(), 'maximum_amount must not be mutated on failure' );
	}

	/**
	 * @testdox set_props treats a zero maximum as no upper limit and allows any minimum.
	 */
	public function test_set_props_allows_any_minimum_when_maximum_is_zero(): void {
		$coupon = new WC_Coupon();

		$result = $coupon->set_props(
			array(
				'minimum_amount' => '999.00',
				'maximum_amount' => '0',
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '999.00', $coupon->get_minimum_amount() );
		$this->assertSame( '0', $coupon->get_maximum_amount() );
	}

	/**
	 * @testdox set_props allows equal minimum and maximum (boundary is inclusive).
	 */
	public function test_set_props_allows_equal_minimum_and_maximum(): void {
		$coupon = new WC_Coupon();

		$result = $coupon->set_props(
			array(
				'minimum_amount' => '100.00',
				'maximum_amount' => '100.00',
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '100.00', $coupon->get_minimum_amount() );
		$this->assertSame( '100.00', $coupon->get_maximum_amount() );
	}

	/**
	 * @testdox set_props applies a valid min/max pair and still returns errors from other properties.
	 */
	public function test_set_props_applies_valid_amounts_and_aggregates_other_errors(): void {
		$coupon = new WC_Coupon();

		$result = $coupon->set_props(
			array(
				'minimum_amount' => '50.00',
				'maximum_amount' => '100.00',
				'amount'         => '-10',
			)
		);

		$this->assertWPError( $result );
		// The valid min/max pair should still be applied.
		$this->assertSame( '50.00', $coupon->get_minimum_amount() );
		$this->assertSame( '100.00', $coupon->get_maximum_amount() );
	}
}
