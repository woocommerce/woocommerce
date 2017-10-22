<?php

/**
 * Test for the discounts class.
 * @package WooCommerce\Tests\Discounts
 */
class WC_Tests_Discounts extends WC_Unit_Test_Case {

	/**
	 * @var WC_Product[] Array of products to clean up.
	 */
	protected $products;

	/**
	 * @var WC_Coupon[] Array of coupons to clean up.
	 */
	protected $coupons;

	/**
	 * @var WC_Order[] Array of orders to clean up.
	 */
	protected $orders;

	/**
	 * @var array An array containing all the test data from the last Data Provider test.
	 */
	protected $last_test_data;

	/**
	 * Helper function to hold a reference to created coupon objects so they
	 * can be cleaned up properly at the end of each test.
	 *
	 * @param $coupon WC_Coupon The coupon object to store.
	 */
	protected function store_coupon( $coupon ) {
		$this->coupons[ $coupon->get_code() ] = $coupon;
	}

	/**
	 * Helper function to hold a reference to created product objects so they
	 * can be cleaned up properly at the end of each test.
	 *
	 * @param $product WC_Product The product object to store.
	 */
	protected function store_product( $product ) {
		$this->products[] = $product;
	}

	/**
	 * Helper function to hold a reference to created order objects so they
	 * can be cleaned up properly at the end of each test.
	 *
	 * @param $order WC_Order The order object to store.
	 */
	protected function store_order( $order ) {
		$this->orders[] = $order;
	}

	public function setUp() {
		parent::setUp();

		$this->products = array();
		$this->coupons = array();
		$this->orders = array();
		$this->last_test_data = null;
	}

	/**
	 * Clean up after each test. DB changes are reverted in parent::tearDown().
	 */
	public function tearDown() {
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();

		foreach ( $this->products as $product ) {
			$product->delete( true );
		}

		foreach ( $this->coupons as $code => $coupon ) {
			$coupon->delete( true );

			// Temporarily necessary until https://github.com/woocommerce/woocommerce/pull/16767 is implemented.
			wp_cache_delete( WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . $code, 'coupons' );
		}

		foreach ( $this->orders as $order ) {
			$order->delete( true );
		}

		if ( $this->last_test_data ) {
			if ( isset( $this->last_test_data['wc_options'] ) ) {
				foreach ( $this->last_test_data['wc_options'] as $_option_name => $_option_value ) {
					update_option( $_option_name, $_option_value['revert'] );
				}
			}

			$this->last_test_data = null;
		}

		parent::tearDown();
	}

	/**
	 * Test get and set items.
	 */
	public function test_get_set_items_from_cart() {
		// Create dummy product - price will be 10
		$product = WC_Helper_Product::create_simple_product();
		$this->store_product( $product );

		// Add product to the cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Add product to a dummy order.
		$order = new WC_Order();
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();
		$this->store_order( $order );

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
		$this->store_product( $product );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$coupon = WC_Helper_Coupon::create_coupon( 'test' );
		$coupon->set_amount( 10 );
		$this->store_coupon( $coupon );

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
		$this->last_test_data = $test_data;
		$discounts = new WC_Discounts();

		if ( isset( $test_data['tax_rate'] ) ) {
			WC_Tax::_insert_tax_rate( $test_data['tax_rate'] );
		}

		if ( isset( $test_data['wc_options'] ) ) {
			foreach ( $test_data['wc_options'] as $_option_name => $_option_value ) {
				update_option( $_option_name, $_option_value['set'] );
			}
		}

		foreach ( $test_data['cart'] as $item ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( $item['price'] );
			$product->set_tax_status( 'taxable' );
			$product->save();
			$this->store_product( $product );
			WC()->cart->add_to_cart( $product->get_id(), $item['qty'] );
		}

		$discounts->set_items_from_cart( WC()->cart );

		foreach ( $test_data['coupons'] as $coupon_props ) {
			$coupon = WC_Helper_Coupon::create_coupon( $coupon_props['code'] );
			$coupon->set_props( $coupon_props );
			$discounts->apply_coupon( $coupon );
			$this->store_coupon( $coupon );
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
						'woocommerce_calc_taxes' => array(
							'set'    => 'yes',
							'revert' => 'no',
						),
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
			array(
				array(
					'desc' => 'Test multiple coupons. No limits. Not discounting sequentially.',
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
							'price' => 5,
							'qty'   => 1,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'percent',
							'amount'                 => '10',
						),
						array(
							'code'                   => 'test1',
							'discount_type'          => 'percent',
							'amount'                 => '20',
						),
					),
					'expected_total_discount' => 7.5,
				),
			),
			array(
				array(
					'desc' => 'Test multiple coupons. One coupon has limit up to one item. Not discounting sequentially.',
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
							'price' => 5,
							'qty'   => 1,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'percent',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 1,
						),
						array(
							'code'                   => 'test1',
							'discount_type'          => 'percent',
							'amount'                 => '20',
						),
					),
					'expected_total_discount' => 6,
				),
			),
			array(
				array(
					'desc' => 'Test multiple coupons. No limits. Discounting sequentially.',
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
					'wc_options' => array(
						'woocommerce_calc_discounts_sequentially' => array(
							'set'    => 'yes',
							'revert' => 'no',
						),
					),
					'cart' => array(
						array(
							'price' => 10,
							'qty'   => 2,
						),
						array(
							'price' => 5,
							'qty'   => 1,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'percent',
							'amount'                 => '10',
						),
						array(
							'code'                   => 'test1',
							'discount_type'          => 'percent',
							'amount'                 => '20',
						),
					),
					'expected_total_discount' => 7,
				),
			),
			array(
				array(
					'desc' => 'Test multiple coupons. No limits. Discounting sequentially.',
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
					'wc_options' => array(
						'woocommerce_calc_discounts_sequentially' => array(
							'set'    => 'yes',
							'revert' => 'no',
						),
					),
					'cart' => array(
						array(
							'price' => 1.80,
							'qty'   => 10,
						),
						array(
							'price' => 13.95,
							'qty'   => 3,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'percent',
							'amount'                 => '10',
						),
						array(
							'code'                   => 'test1',
							'discount_type'          => 'percent',
							'amount'                 => '20',
						),
					),
					'expected_total_discount' => 16.75,
				),
			),
			array(
				array(
					'desc' => 'Test multiple coupons. One coupon has limit up to 5 item. Discounting non-sequentially.',
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
							'qty'   => 3,
						),
						array(
							'price' => 5,
							'qty'   => 3,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'percent',
							'amount'                 => '30',
							'limit_usage_to_x_items' => 5,
						),
						array(
							'code'                   => 'test1',
							'discount_type'          => 'percent',
							'amount'                 => '20',
						),
					),
					'expected_total_discount' => 21,
				),
			),
			array(
				array(
					'desc' => 'Test multiple coupons. One coupon has limit up to 5 item. Discounting sequentially.',
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
					'wc_options' => array(
						'woocommerce_calc_discounts_sequentially' => array(
							'set'    => 'yes',
							'revert' => 'no',
						),
					),
					'cart' => array(
						array(
							'price' => 10,
							'qty'   => 3,
						),
						array(
							'price' => 5,
							'qty'   => 3,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test1',
							'discount_type'          => 'percent',
							'amount'                 => '20',
						),
						array(
							'code'                   => 'test',
							'discount_type'          => 'percent',
							'amount'                 => '30',
							'limit_usage_to_x_items' => 5,
						),
					),
					'expected_total_discount' => 18.30,
				),
			),
			array(
				array(
					'desc' => 'Test multiple coupons. One coupon has limit up to 5 item. Discounting sequentially. Multiple zero-dollar items.',
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
					'wc_options' => array(
						'woocommerce_calc_discounts_sequentially' => array(
							'set'    => 'yes',
							'revert' => 'no',
						),
					),
					'cart' => array(
						array(
							'price' => 1.80,
							'qty'   => 3,
						),
						array(
							'price' => 13.95,
							'qty'   => 3,
						),
						array(
							'price' => 0,
							'qty'   => 1,
						),
						array(
							'price' => 0,
							'qty'   => 1,
						),
						array(
							'price' => 0,
							'qty'   => 1,
						),
						array(
							'price' => 0,
							'qty'   => 1,
						),
						array(
							'price' => 0,
							'qty'   => 1,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'percent',
							'amount'                 => '30',
							'limit_usage_to_x_items' => 5,
						),
						array(
							'code'                   => 'test1',
							'discount_type'          => 'percent',
							'amount'                 => '20',
						),
					),
					'expected_total_discount' => 20.35,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on one item.',
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
							'price' => 13.95,
							'qty'   => 3,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '10',
						),
					),
					'expected_total_discount' => 30,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on one item. Coupon greater than item cost.',
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
							'price' => 13.95,
							'qty'   => 3,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '20',
						),
					),
					'expected_total_discount' => 41.85,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on one item. Limit to one item.',
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
							'price' => 13.95,
							'qty'   => 3,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 1,
						),
					),
					'expected_total_discount' => 10,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on one item. Limit to one item. Price greater than product.',
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
							'price' => 13.95,
							'qty'   => 3,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '15',
							'limit_usage_to_x_items' => 1,
						),
					),
					'expected_total_discount' => 13.95,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on one item. Limit to same number of items as product. Price same as product.',
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
							'price' => 13.95,
							'qty'   => 3,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '13.95',
							'limit_usage_to_x_items' => 3,
						),
					),
					'expected_total_discount' => 41.85,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on two items. No limit.',
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
							'price' => 13.95,
							'qty'   => 3,
						),
						array(
							'price' => 1.80,
							'qty'   => 5,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '10',
						),
					),
					'expected_total_discount' => 39,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on two items. Limit to same number of items as first product.',
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
							'price' => 13.95,
							'qty'   => 3,
						),
						array(
							'price' => 1.80,
							'qty'   => 5,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 3,
						),
					),
					'expected_total_discount' => 30,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on two items. Limit to number greater than first product but less than total quantities.',
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
							'price' => 13.95,
							'qty'   => 3,
						),
						array(
							'price' => 1.80,
							'qty'   => 5,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 5,
						),
					),
					'expected_total_discount' => 33.60,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on two items. Limit to number greater than first product but less than total quantities. Amount less than price of either product.',
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
							'price' => 13.95,
							'qty'   => 3,
						),
						array(
							'price' => 1.80,
							'qty'   => 5,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '1',
							'limit_usage_to_x_items' => 5,
						),
					),
					'expected_total_discount' => 5,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on two items. Limit to number greater than both product quantities combined. Amount less than price of either product.',
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
							'price' => 13.95,
							'qty'   => 3,
						),
						array(
							'price' => 1.80,
							'qty'   => 5,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '1',
							'limit_usage_to_x_items' => 10,
						),
					),
					'expected_total_discount' => 8,
				),
			),
			array(
				array(
					'desc' => 'Test single fixed product coupon on two items. Limit to two items. Testing the products are sorted according to legacy method where first one to apply is the one with greatest price * quantity.',
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
							'price' => 1.80,
							'qty'   => 5,
						),
						array(
							'price' => 13.95,
							'qty'   => 3,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'test',
							'discount_type'          => 'fixed_product',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 2,
						),
					),
					'expected_total_discount' => 20,
				),
			),
			array(
				array(
					'desc' => 'Test multiple coupons with limits of 1. Discounting sequentially.',
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
					'wc_options' => array(
						'woocommerce_calc_discounts_sequentially' => array(
							'set'    => 'yes',
							'revert' => 'no',
						),
					),
					'cart' => array(
						array(
							'price' => 10,
							'qty'   => 4,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'one',
							'discount_type'          => 'fixed_product',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 1,
						),
						array(
							'code'                   => 'two',
							'discount_type'          => 'fixed_product',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 1,
						),
						array(
							'code'                   => 'three',
							'discount_type'          => 'percent',
							'amount'                 => '100',
							'limit_usage_to_x_items' => 1,
						),
					),
					'expected_total_discount' => 30,
				),
			),
			array(
				array(
					'desc' => 'Test multiple coupons with limits of 1.',
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
							'qty'   => 4,
						),
					),
					'coupons' => array(
						array(
							'code'                   => 'one',
							'discount_type'          => 'fixed_product',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 1,
						),
						array(
							'code'                   => 'two',
							'discount_type'          => 'fixed_product',
							'amount'                 => '10',
							'limit_usage_to_x_items' => 1,
						),
						array(
							'code'                   => 'three',
							'discount_type'          => 'percent',
							'amount'                 => '100',
							'limit_usage_to_x_items' => 1,
						),
					),
					'expected_total_discount' => 30,
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
