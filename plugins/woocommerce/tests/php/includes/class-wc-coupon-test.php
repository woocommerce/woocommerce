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
}
