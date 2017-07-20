<?php

/**
 * Test for the discounts class.
 * @package WooCommerce\Tests\Discounts
 */
class WC_Tests_Discounts extends WC_Unit_Test_Case {

	/**
	 * Test get and set items.
	 */
	public function test_get_set_items() {
		// Create dummy product - price will be 10
		$product = WC_Helper_Product::create_simple_product();

		// Add product to the cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Add product to a dummy order.
		$order = new WC_Order();
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();

		// Test setting items to the cart.
		$discounts = new WC_Discounts();
		$discounts->set_items( WC()->cart->get_cart() );
		$this->assertEquals( 1, count( $discounts->get_items() ) );

		// Test setting items to an order.
		$discounts = new WC_Discounts();
		$discounts->set_items( $order->get_items() );
		$this->assertEquals( 1, count( $discounts->get_items() ) );

		// Empty array of items.
		$discounts = new WC_Discounts();
		$discounts->set_items( array() );
		$this->assertEquals( array(), $discounts->get_items() );

		// Invalid items.
		$discounts = new WC_Discounts();
		$discounts->set_items( false );
		$this->assertEquals( array(), $discounts->get_items() );

		// Cleanup.
		WC()->cart->empty_cart();
		$product->delete( true );
		$order->delete( true );
	}

	public function test_apply_discount() {
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$tax_rate2 = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => 'reduced-rate',
		);
		$tax_rate_id  = WC_Tax::_insert_tax_rate( $tax_rate );
		$tax_rate_id2 = WC_Tax::_insert_tax_rate( $tax_rate2 );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$product  = WC_Helper_Product::create_simple_product();
		$product2 = WC_Helper_Product::create_simple_product();

		$product->set_tax_class( '' );
		$product2->set_tax_class( 'reduced-rate' );

		$product->save();
		$product2->save();

		// Add product to the cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_to_cart( $product2->get_id(), 1 );

		// Test.
		$discounts = new WC_Discounts();
		$discounts->set_items( WC()->cart->get_cart() );

		$discounts->apply_discount( '50%' );
		$all_discounts = $discounts->get_discounts();
		$this->assertEquals( $all_discounts['discount-50%'], 10 );

		$discounts->apply_discount( '50%' );
		$all_discounts = $discounts->get_discounts();
		$this->assertEquals( $all_discounts['discount-50%'], 10, print_r( $all_discounts, true ) );
		$this->assertEquals( $all_discounts['discount-50%-2'], 5, print_r( $all_discounts, true ) );

		// Test fixed discounts.
		$discounts = new WC_Discounts();
		$discounts->set_items( WC()->cart->get_cart() );

		$discounts->apply_discount( '5' );
		$all_discounts = $discounts->get_discounts();
		$this->assertEquals( $all_discounts['discount-5'], 5 );

		$discounts->apply_discount( '5' );
		$all_discounts = $discounts->get_discounts();
		$this->assertEquals( $all_discounts['discount-5'], 5, print_r( $all_discounts, true ) );
		$this->assertEquals( $all_discounts['discount-5-2'], 5, print_r( $all_discounts, true ) );

		$discounts->apply_discount( '15' );
		$all_discounts = $discounts->get_discounts();
		$this->assertEquals( $all_discounts['discount-5'], 5, print_r( $all_discounts, true ) );
		$this->assertEquals( $all_discounts['discount-5-2'], 5, print_r( $all_discounts, true ) );
		$this->assertEquals( $all_discounts['discount-15'], 10, print_r( $all_discounts, true ) );

		// Cleanup.
		WC()->cart->empty_cart();
		$product->delete( true );
		$product2->delete( true );
		WC_Tax::_delete_tax_rate( $tax_rate_id );
		WC_Tax::_delete_tax_rate( $tax_rate_id2 );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * test get_applied_coupons
	 */
	public function test_get_applied_coupons() {
		$discounts = new WC_Discounts();
		$product   = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$discounts->set_items( WC()->cart->get_cart() );

		// Test applying multiple coupons and getting totals.
		$coupon = new WC_Coupon;
		$coupon->set_code( 'test' );
		$coupon->set_amount( 50 );
		$coupon->set_discount_type( 'percent' );
		$discounts->apply_coupon( $coupon );

		$this->assertEquals( array( 'test' => 5 ), $discounts->get_applied_coupons() );

		$coupon2 = new WC_Coupon;
		$coupon2->set_code( 'test2' );
		$coupon2->set_amount( 50 );
		$coupon2->set_discount_type( 'percent' );
		$discounts->apply_coupon( $coupon2 );

		$this->assertEquals( array( 'test' => 5, 'test2' => 2.50 ), $discounts->get_applied_coupons() );

		$discounts->apply_coupon( $coupon );
		$this->assertEquals( array( 'test' => 6.25, 'test2' => 2.50 ), $discounts->get_applied_coupons() );

		// Test different coupon types.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 2 );
		$coupon->set_discount_type( 'fixed_product' );
		$coupon->set_amount( 2 );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( array( 'test' => 4 ), $discounts->get_applied_coupons() );

		$coupon->set_discount_type( 'fixed_cart' );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( array( 'test' => 2 ), $discounts->get_applied_coupons() );

		// Cleanup.
		WC()->cart->empty_cart();
		$product->delete( true );
	}

	/**
	 * Test applying a coupon (make sure it changes prices).
	 */
	public function test_apply_coupon() {
		$discounts = new WC_Discounts();

		// Create dummy content.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_tax_status( 'taxable' );
		$product->save();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$coupon = new WC_Coupon;
		$coupon->set_code( 'test' );
		$coupon->set_amount( 10 );

		// Apply a percent discount.
		$coupon->set_discount_type( 'percent' );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 9, $discounts->get_discounted_price( current( $discounts->get_items() ) ) );

		// Apply a fixed cart coupon.
		$coupon->set_discount_type( 'fixed_cart' );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 0, $discounts->get_discounted_price( current( $discounts->get_items() ) ) );

		// Apply a fixed product coupon.
		$coupon->set_discount_type( 'fixed_product' );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 0, $discounts->get_discounted_price( current( $discounts->get_items() ) ) );

		// Cleanup.
		WC()->cart->empty_cart();
		$product->delete( true );
	}

	/**
	 * Test various discount calculations are working correctly and produding expected results.
	 */
	public function test_calculations() {
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$tests = array(
			array(
				'prices_include_tax' => false,
				'cart' => array(
					array(
						'price' => 10,
						'qty'   => 1,
					)
				),
				'coupons' => array(
					array(
						'code'          => 'test',
						'discount_type' => 'percent',
						'amount'        => '20',
					)
				),
				'expected_total_discount' => 2,
			),
			array(
				'prices_include_tax' => false,
				'cart' => array(
					array(
						'price' => 10,
						'qty'   => 2,
					)
				),
				'coupons' => array(
					array(
						'code'          => 'test',
						'discount_type' => 'fixed_cart',
						'amount'        => '10',
					)
				),
				'expected_total_discount' => 10,
			),
			array(
				'prices_include_tax' => false,
				'cart' => array(
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					)
				),
				'coupons' => array(
					array(
						'code'          => 'test',
						'discount_type' => 'fixed_cart',
						'amount'        => '10',
					)
				),
				'expected_total_discount' => 10,
			),
			array(
				'prices_include_tax' => false,
				'cart' => array(
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					)
				),
				'coupons' => array(
					array(
						'code'          => 'test',
						'discount_type' => 'fixed_cart',
						'amount'        => '10',
					)
				),
				'expected_total_discount' => 10,
			),
			array(
				'prices_include_tax' => false,
				'cart' => array(
					array(
						'price' => 10,
						'qty'   => 2,
					),
					array(
						'price' => 10,
						'qty'   => 3,
					),
					array(
						'price' => 10,
						'qty'   => 2,
					)
				),
				'coupons' => array(
					array(
						'code'          => 'test',
						'discount_type' => 'fixed_cart',
						'amount'        => '10',
					)
				),
				'expected_total_discount' => 10,
			),
			array(
				'prices_include_tax' => false,
				'cart' => array(
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					),
					array(
						'price' => 10,
						'qty'   => 1,
					)
				),
				'coupons' => array(
					array(
						'code'          => 'test',
						'discount_type' => 'fixed_cart',
						'amount'        => '10',
					)
				),
				'expected_total_discount' => 10,
			),
			array(
				'prices_include_tax' => false,
				'cart' => array(
					array(
						'price' => 1,
						'qty'   => 1,
					),
					array(
						'price' => 1,
						'qty'   => 1,
					),
					array(
						'price' => 1,
						'qty'   => 1,
					)
				),
				'coupons' => array(
					array(
						'code'          => 'test',
						'discount_type' => 'fixed_cart',
						'amount'        => '1',
					)
				),
				'expected_total_discount' => 1,
			),
			array(
				'prices_include_tax' => false,
				'cart' => array(
					array(
						'price' => 10,
						'qty'   => 2,
					)
				),
				'coupons' => array(
					array(
						'code'                   => 'test',
						'discount_type'          => 'percent',
						'amount'                 => '10',
						'limit_usage_to_x_items' => 1,
					)
				),
				'expected_total_discount' => 1,
			),
			array(
				'prices_include_tax' => false,
				'cart' => array(
					array(
						'price' => 10,
						'qty'   => 2,
					),
					array(
						'price' => 10,
						'qty'   => 2,
					)
				),
				'coupons' => array(
					array(
						'code'                   => 'test',
						'discount_type'          => 'percent',
						'amount'                 => '10',
						'limit_usage_to_x_items' => 1,
					)
				),
				'expected_total_discount' => 1,
			),
		);

		foreach ( $tests as $test_index => $test ) {
			$discounts = new WC_Discounts();
			$products  = array();

			foreach ( $test['cart'] as $item ) {
				$product = WC_Helper_Product::create_simple_product();
				$product->set_regular_price( $item['price'] );
				$product->set_tax_status( 'taxable' );
				$product->save();
				WC()->cart->add_to_cart( $product->get_id(), $item['qty'] );
				$products[] = $product;
			}

			$discounts->set_items( WC()->cart->get_cart() );

			foreach ( $test['coupons'] as $coupon_props ) {
				$coupon = new WC_Coupon;
				$coupon->set_props( $coupon_props );
				$discounts->apply_coupon( $coupon );
			}

			$this->assertEquals( $test['expected_total_discount'], array_sum( $discounts->get_discounts() ), 'Test case ' . $test_index . ' failed (' . print_r( $test, true ) . ' - ' . print_r( $discounts->get_discounts(), true ) . ')' );

			// Clean.
			WC()->cart->empty_cart();

			foreach ( $products as $product ) {
				$product->delete( true );
			}
		}

		WC_Tax::_delete_tax_rate( $tax_rate_id );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}
}
