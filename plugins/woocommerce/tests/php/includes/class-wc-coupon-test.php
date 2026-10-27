<?php

/**
 * Tests for WC_Coupon.
 *
 * See also ../../legacy/unit-tests/coupon/coupon.php for other related tests.
 */
class WC_Coupon_Tests extends WC_Unit_Test_Case {

	/**
	 * @testdox Amount validation uses the stored discount type when getters are filtered.
	 * @testWith ["fixed_cart", "percent", "150"]
	 *           ["fixed_product", "percent", "150"]
	 *           ["percent", "fixed_cart", "100"]
	 * @param string $stored_type Stored discount type.
	 * @param string $view_type Filtered discount type.
	 * @param string $amount Valid amount for the stored type.
	 */
	public function test_set_amount_with_filtered_discount_type( string $stored_type, string $view_type, string $amount ): void {
		$sut = new WC_Coupon();
		$sut->set_discount_type( $stored_type );
		add_filter(
			'woocommerce_coupon_get_discount_type',
			static function () use ( $view_type ) {
				return $view_type;
			}
		);

		$sut->set_amount( $amount );

		$this->assertSame( $amount, $sut->get_amount( 'edit' ) );
		$this->assertSame( $stored_type, $sut->get_discount_type( 'edit' ) );
		$this->assertSame( $view_type, $sut->get_discount_type() );
	}

	/**
	 * @testdox A filtered discount type cannot bypass the stored percentage limit.
	 */
	public function test_set_amount_rejects_percentage_above_100_with_filtered_discount_type(): void {
		$sut = new WC_Coupon();
		$sut->set_discount_type( 'percent' );
		add_filter(
			'woocommerce_coupon_get_discount_type',
			static function () {
				return 'fixed_cart';
			}
		);

		$this->expectException( WC_Data_Exception::class );
		$this->expectExceptionMessage( 'Invalid discount amount.' );

		$sut->set_amount( '150' );
	}

	/**
	 * @testdox Loading coupons preserves fixed amounts when type and amount getters are filtered.
	 * @testWith [false]
	 *           [true]
	 * @param bool $manual Whether to load a manual coupon.
	 */
	public function test_load_amount_with_filtered_discount_type( bool $manual ): void {
		$props = array(
			'discount_type' => 'fixed_cart',
			'amount'        => '150',
		);
		$sut   = new WC_Coupon();
		$sut->set_code( 'filtered-discount-type' );
		$sut->set_props( $props );
		$id = $sut->save();

		if ( $manual ) {
			add_filter(
				'woocommerce_get_shop_coupon_data',
				static function () use ( $props ) {
					return $props;
				}
			);
		}
		add_filter(
			'woocommerce_coupon_get_discount_type',
			static function () {
				return 'percent';
			}
		);
		add_filter(
			'woocommerce_coupon_get_amount',
			static function () {
				return '25';
			}
		);

		$sut = new WC_Coupon( $id );

		$this->assertSame( '150', $sut->get_amount( 'edit' ) );
		$this->assertSame( 'fixed_cart', $sut->get_discount_type( 'edit' ) );
		$this->assertSame( '25', $sut->get_amount() );
		$this->assertSame( 'percent', $sut->get_discount_type() );
	}

	/**
	 * @testdox Spend limit setters compare stored amounts when getters are filtered.
	 * @testWith [2, false, "60"]
	 *           [0.5, true, "60"]
	 *           [2, false, "50"]
	 *           [2, false, "0.00"]
	 *           [0.5, true, "0.00"]
	 * @param float  $rate Conversion rate.
	 * @param bool   $maximum_first Whether to set the maximum first.
	 * @param string $maximum Maximum spend, with zero meaning unlimited.
	 */
	public function test_spend_limit_setters_with_filtered_amounts( float $rate, bool $maximum_first, string $maximum ): void {
		$sut     = new WC_Coupon();
		$convert = static function ( $amount ) use ( $rate ) {
			return (float) $amount * $rate;
		};
		add_filter( 'woocommerce_coupon_get_minimum_amount', $convert );
		add_filter( 'woocommerce_coupon_get_maximum_amount', $convert );

		$limits = array(
			'minimum_amount' => '50',
			'maximum_amount' => $maximum,
		);
		if ( $maximum_first ) {
			$limits = array_reverse( $limits, true );
		}
		foreach ( $limits as $property => $amount ) {
			$sut->set_props( array( $property => $amount ) );
		}

		$this->assertSame( array( '50', $maximum ), array( $sut->get_minimum_amount( 'edit' ), $sut->get_maximum_amount( 'edit' ) ) );
		$this->assertSame( (string) ( 50 * $rate ), $sut->get_minimum_amount() );
		$this->assertSame( (string) ( (float) $maximum * $rate ), $sut->get_maximum_amount() );
	}

	/**
	 * @testdox Loading coupons preserves stored spend limits when getters are filtered.
	 * @testWith [false]
	 *           [true]
	 * @param bool $manual Whether to load a manual coupon.
	 */
	public function test_load_spend_limits_with_filtered_amounts( bool $manual ): void {
		$sut = new WC_Coupon();
		$sut->set_code( 'filtered-spend-limits' );
		$sut->set_minimum_amount( '50' );
		$sut->set_maximum_amount( '60' );
		$id = $sut->save();

		if ( $manual ) {
			add_filter(
				'woocommerce_get_shop_coupon_data',
				static function () {
					return array(
						'minimum_amount' => '50',
						'maximum_amount' => '60',
					);
				}
			);
		}
		$convert = static function ( $amount ) {
			return (float) $amount * 2;
		};
		add_filter( 'woocommerce_coupon_get_minimum_amount', $convert );
		add_filter( 'woocommerce_coupon_get_maximum_amount', $convert );

		$sut = new WC_Coupon( $id );

		$this->assertSame( array( '50', '60' ), array( $sut->get_minimum_amount( 'edit' ), $sut->get_maximum_amount( 'edit' ) ) );
		$this->assertSame( '100', $sut->get_minimum_amount() );
		$this->assertSame( '120', $sut->get_maximum_amount() );
	}

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
	// Locale-formatted amounts (comma decimal separator).
	// -------------------------------------------------------------------------

	/**
	 * @testdox set_minimum_amount throws exception when a comma-decimal minimum exceeds existing maximum.
	 */
	public function test_set_minimum_amount_throws_when_comma_decimal_exceeds_maximum(): void {
		$original_decimal_separator = get_option( 'woocommerce_price_decimal_sep' );
		update_option( 'woocommerce_price_decimal_sep', ',' );

		try {
			$coupon = new WC_Coupon();
			$coupon->set_maximum_amount( '100,00' );

			try {
				$coupon->set_minimum_amount( '100,50' );
				$this->fail( 'Expected WC_Data_Exception was not thrown.' );
			} catch ( \WC_Data_Exception $e ) {
				$this->assertSame( 'coupon_invalid_minimum_amount', $e->getErrorCode() );
			}
		} finally {
			update_option( 'woocommerce_price_decimal_sep', $original_decimal_separator );
		}
	}

	/**
	 * @testdox set_maximum_amount succeeds and stores the normalized value when a comma-decimal maximum satisfies the existing minimum.
	 */
	public function test_set_maximum_amount_succeeds_when_comma_decimal_satisfies_minimum(): void {
		$original_decimal_separator = get_option( 'woocommerce_price_decimal_sep' );
		update_option( 'woocommerce_price_decimal_sep', ',' );

		try {
			$coupon = new WC_Coupon();
			$coupon->set_minimum_amount( '100.50' );

			$coupon->set_maximum_amount( '100,75' );

			$this->assertSame( '100.75', $coupon->get_maximum_amount() );
		} finally {
			update_option( 'woocommerce_price_decimal_sep', $original_decimal_separator );
		}
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

	/**
	 * @testdox Loading a coupon whose stored minimum exceeds its stored maximum keeps the minimum and drops the maximum.
	 */
	public function test_loading_inverted_stored_amounts_keeps_minimum_spend(): void {
		$coupon = WC_Helper_Coupon::create_coupon(
			'inverted-spend-limits',
			array(
				'minimum_amount' => '150',
				'maximum_amount' => '100',
			)
		);

		$this->assertSame( '150', $coupon->get_minimum_amount() );
		$this->assertSame( '0', $coupon->get_maximum_amount() );
	}

	/**
	 * @testdox set_props still rejects an inverted min/max pair on a coupon loaded from the database.
	 */
	public function test_set_props_rejects_inverted_pair_on_loaded_coupon(): void {
		$coupon = WC_Helper_Coupon::create_coupon(
			'loaded-spend-limits',
			array(
				'minimum_amount' => '50',
				'maximum_amount' => '100',
			)
		);

		$result = $coupon->set_props(
			array(
				'minimum_amount' => '150',
				'maximum_amount' => '100',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( '50', $coupon->get_minimum_amount() );
		$this->assertSame( '100', $coupon->get_maximum_amount() );
	}
}
