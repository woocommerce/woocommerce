<?php

/**
 * Test for the discounts class.
 * @package WooCommerce\Tests\Discounts
 */
class WC_Tests_Discounts extends WC_Unit_Test_Case {

	/**
	 * Clean up after each test. DB changes are reverted in parent::tearDown().
	 */
	public function tearDown() {
		parent::tearDown();

		WC()->cart->empty_cart();

		WC()->cart->remove_coupons();
	}

	/**
	 * Test get and set items.
	 */
	public function test_get_set_items_from_cart() {
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
		$discounts->set_items_from_cart( WC()->cart );
		$this->assertEquals( 1, count( $discounts->get_items() ) );

		// Test setting items to an order.
		$discounts = new WC_Discounts();
		$discounts->set_items_from_cart( WC()->cart );
		$this->assertEquals( 1, count( $discounts->get_items() ) );

		// Empty array of items.
		$discounts = new WC_Discounts();
		$discounts->set_items_from_cart( array() );
		$this->assertEquals( array(), $discounts->get_items() );

		// Invalid items.
		$discounts = new WC_Discounts();
		$discounts->set_items_from_cart( false );
		$this->assertEquals( array(), $discounts->get_items() );
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
		$coupon = WC_Helper_Coupon::create_coupon( 'test' );
		$coupon->set_amount( 10 );

		// Apply a percent discount.
		$coupon->set_discount_type( 'percent' );
		$discounts->set_items_from_cart( WC()->cart );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 9, $discounts->get_discounted_price( current( $discounts->get_items() ) ), print_r( $discounts->get_discounts(), true ) );

		// Apply a fixed cart coupon.
		$coupon->set_discount_type( 'fixed_cart' );
		$discounts->set_items_from_cart( WC()->cart );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 0, $discounts->get_discounted_price( current( $discounts->get_items() ) ), print_r( $discounts->get_discounts(), true ) );

		// Apply a fixed product coupon.
		$coupon->set_discount_type( 'fixed_product' );
		$discounts->set_items_from_cart( WC()->cart );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 0, $discounts->get_discounted_price( current( $discounts->get_items() ) ), print_r( $discounts->get_discounts(), true ) );
	}

	/**
	 * Test various discount calculations are working correctly and producing expected results.
	 *
	 * @dataProvider calculations_test_provider
	 *
	 * @param array $test_data All of the settings to use for testing.
	 */
	public function test_calculations( $test_data ) {
		$discounts = new WC_Discounts();
		$products  = array();
		$coupons   = array();

		if ( isset( $test_data['tax_rate'] ) ) {
			WC_Tax::_insert_tax_rate( $test_data['tax_rate'] );
		}

		if ( isset( $test_data['wc_options'] ) ) {
			foreach ( $test_data['wc_options'] as $_option_name => $_option_value ) {
				update_option( $_option_name, $_option_value );
			}
		}

		foreach ( $test_data['cart'] as $item ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( $item['price'] );
			$product->set_tax_status( 'taxable' );
			$product->save();
			WC()->cart->add_to_cart( $product->get_id(), $item['qty'] );
			$products[] = $product;
		}

		$discounts->set_items_from_cart( WC()->cart );

		foreach ( $test_data['coupons'] as $coupon_props ) {
			$coupon = WC_Helper_Coupon::create_coupon( $coupon_props['code'] );
			$coupon->set_props( $coupon_props );
			$discounts->apply_coupon( $coupon );

			$coupons[ $coupon_props['code'] ] = $coupon;
		}

		$all_discounts = $discounts->get_discounts();

		$discount_total = 0;
		foreach ( $all_discounts as $code_name => $discounts_by_coupon ) {
			$discount_total += array_sum( $discounts_by_coupon );
		}

		$this->assertEquals( $test_data['expected_total_discount'], $discount_total, 'Failed (' . print_r( $test_data, true ) . ' - ' . print_r( $discounts->get_discounts(), true ) . ')' );
	}

	/**
	 * This is a dataProvider for test_calculations().
	 *
	 * @return array An array of discount tests to be run.
	 */
	public function calculations_test_provider() {
		return array(
			array(
				array(
					'tax_rate' => array(
						'tax_rate_country'  => '',
						'tax_rate_state'    => '',
						'tax_rate'          => '20.0000',
						'tax_rate_name'     => 'VAT',
						'tax_rate_priority' => '1',
						'tax_rate_compound' => '0',
						'tax_rate_shipping' => '1',
						'tax_rate_order'    => '1',
						'tax_rate_class'    => '',
					),
					'wc_options' => array(
						'woocommerce_calc_taxes' => 'yes',
					),
					'prices_include_tax' => false,
					'cart' => array(
						array(
							'price' => 10,
							'qty'   => 1,
						),
					),
					'coupons' => array(
						array(
							'code'          => 'test',
							'discount_type' => 'percent',
							'amount'        => '20',
						),
					),
					'expected_total_discount' => 2,
				),
			),
			array(
				array(
					'tax_rate' => array(
						'tax_rate_country'  => '',
						'tax_rate_state'    => '',
						'tax_rate'          => '20.0000',
						'tax_rate_name'     => 'VAT',
						'tax_rate_priority' => '1',
						'tax_rate_compound' => '0',
						'tax_rate_shipping' => '1',
						'tax_rate_order'    => '1',
						'tax_rate_class'    => '',
					),
					'prices_include_tax' => false,
					'cart' => array(
						array(
							'price' => 10,
							'qty'   => 2,
						),
					),
					'coupons' => array(
						array(
							'code'          => 'test',
							'discount_type' => 'fixed_cart',
							'amount'        => '10',
						),
					),
					'expected_total_discount' => 10,
				),
			),
			array(
				array(
					'tax_rate' => array(
						'tax_rate_country'  => '',
						'tax_rate_state'    => '',
						'tax_rate'          => '20.0000',
						'tax_rate_name'     => 'VAT',
						'tax_rate_priority' => '1',
						'tax_rate_compound' => '0',
						'tax_rate_shipping' => '1',
						'tax_rate_order'    => '1',
						'tax_rate_class'    => '',
					),
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
					),
					'coupons' => array(
						array(
							'code'          => 'test',
							'discount_type' => 'fixed_cart',
							'amount'        => '10',
						),
					),
					'expected_total_discount' => 10,
				),
			),
			array(
				array(
					'tax_rate' => array(
						'tax_rate_country'  => '',
						'tax_rate_state'    => '',
						'tax_rate'          => '20.0000',
						'tax_rate_name'     => 'VAT',
						'tax_rate_priority' => '1',
						'tax_rate_compound' => '0',
						'tax_rate_shipping' => '1',
						'tax_rate_order'    => '1',
						'tax_rate_class'    => '',
					),
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
					),
					'coupons' => array(
						array(
							'code'          => 'test',
							'discount_type' => 'fixed_cart',
							'amount'        => '10',
						),
					),
					'expected_total_discount' => 10,
				),
			),
			array(
				array(
					'tax_rate' => array(
						'tax_rate_country'  => '',
						'tax_rate_state'    => '',
						'tax_rate'          => '20.0000',
						'tax_rate_name'     => 'VAT',
						'tax_rate_priority' => '1',
						'tax_rate_compound' => '0',
						'tax_rate_shipping' => '1',
						'tax_rate_order'    => '1',
						'tax_rate_class'    => '',
					),
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
						),
					),
					'coupons' => array(
						array(
							'code'          => 'test',
							'discount_type' => 'fixed_cart',
							'amount'        => '10',
						),
					),
					'expected_total_discount' => 10,
				),
			),
			array(
				array(
					'tax_rate' => array(
						'tax_rate_country'  => '',
						'tax_rate_state'    => '',
						'tax_rate'          => '20.0000',
						'tax_rate_name'     => 'VAT',
						'tax_rate_priority' => '1',
						'tax_rate_compound' => '0',
						'tax_rate_shipping' => '1',
						'tax_rate_order'    => '1',
						'tax_rate_class'    => '',
					),
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
						),
					),
					'coupons' => array(
						array(
							'code'          => 'test',
							'discount_type' => 'fixed_cart',
							'amount'        => '10',
						),
					),
					'expected_total_discount' => 10,
				),
			),
			array(
				array(
					'tax_rate' => array(
						'tax_rate_country'  => '',
						'tax_rate_state'    => '',
						'tax_rate'          => '20.0000',
						'tax_rate_name'     => 'VAT',
						'tax_rate_priority' => '1',
						'tax_rate_compound' => '0',
						'tax_rate_shipping' => '1',
						'tax_rate_order'    => '1',
						'tax_rate_class'    => '',
					),
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
						),
					),
					'coupons' => array(
						array(
							'code'          => 'test',
							'discount_type' => 'fixed_cart',
							'amount'        => '1',
						),
					),
					'expected_total_discount' => 1,
				),
			),
			array(
				array(
					'tax_rate' => array(
						'tax_rate_country'  => '',
						'tax_rate_state'    => '',
						'tax_rate'          => '20.0000',
						'tax_rate_name'     => 'VAT',
						'tax_rate_priority' => '1',
						'tax_rate_compound' => '0',
						'tax_rate_shipping' => '1',
						'tax_rate_order'    => '1',
						'tax_rate_class'    => '',
					),
					'prices_include_tax' => false,
					'cart' => array(
						array(
							'price' => 10,
							'qty'   => 2,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'percent',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 1,
						),
					),
					'expected_total_discount' => 1,
				),
			),
			array(
				array(
					'tax_rate' => array(
						'tax_rate_country'  => '',
						'tax_rate_state'    => '',
						'tax_rate'          => '20.0000',
						'tax_rate_name'     => 'VAT',
						'tax_rate_priority' => '1',
						'tax_rate_compound' => '0',
						'tax_rate_shipping' => '1',
						'tax_rate_order'    => '1',
						'tax_rate_class'    => '',
					),
					'prices_include_tax' => false,
					'cart' => array(
						array(
							'price' => 10,
							'qty'   => 2,
						),
						array(
							'price' => 10,
							'qty'   => 2,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'percent',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 1,
						),
					),
					'expected_total_discount' => 1,
				),
			),
		);
	}

	public function test_free_shipping_coupon_no_products() {
		$discounts = new WC_Discounts();
		$coupon = WC_Helper_Coupon::create_coupon( 'freeshipping' );
		$coupon->set_props( array(
			'discount_type' => 'percent',
			'amount' => '',
			'free_shipping' => 'yes',
		));

		$discounts->apply_coupon( $coupon );

		$all_discounts = $discounts->get_discounts();
		$this->assertEquals( 0, count( $all_discounts['freeshipping'] ), 'Free shipping coupon should not have any discounts.' );
	}
}
