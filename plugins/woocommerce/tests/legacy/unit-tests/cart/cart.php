<?php
/**
 * Cart tests.
 *
 * @package WooCommerce\Tests\Cart
 */

/**
 * Class Cart.
 */
class WC_Tests_Cart extends WC_Unit_Test_Case {

	/**
	 * tearDown.
	 */
	public function tearDown() {
		parent::tearDown();

		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
	}

	/**
	 * Test whether totals are correct when discount is applied.
	 */
	public function test_cart_total_with_discount_and_taxes() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );

		WC()->cart->empty_cart();

		$tax_rate    = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'TAX20',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '20percent',
		);
		$tax_rate_20 = WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product with price 19.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 8.99 );
		$product->set_regular_price( 8.99 );
		$product->set_tax_class( '20percent' );
		$product->save();

		$coupon = WC_Helper_Coupon::create_coupon( 'off5', array( 'coupon_amount' => 5 ) );

		// Create a flat rate method.
		$flat_rate_settings = array(
			'enabled'      => 'yes',
			'title'        => 'Flat rate',
			'availability' => 'all',
			'countries'    => '',
			'tax_status'   => 'taxable',
			'cost'         => '9.59',
		);
		update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );

		WC_Helper_Shipping::force_customer_us_address();

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );

		WC()->cart->calculate_totals();

		$this->assertEquals( '13.58', WC()->cart->get_total( 'edit' ) );
		$this->assertEquals( 0.66, WC()->cart->get_total_tax() );
		$this->assertEquals( 0.83, wc_format_decimal( WC()->cart->get_discount_tax(), 2 ) );
		$this->assertEquals( 4.17, wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
	}

	/**
	 * Test for subtotals and multiple tax rounding.
	 * Ticket:
	 *  https://github.com/woocommerce/woocommerce/issues/21871
	 */
	public function test_cart_subtotal_issue_21871() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		update_option( 'woocommerce_tax_display_cart', 'incl' );

		// Create dummy product - price will be 10.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 79 );
		$product->save();

		// Add taxes.
		$tax_rate_025 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '0.2500',
				'tax_rate_name'     => 'TAX025',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$tax_rate_06 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '6.0000',
				'tax_rate_name'     => 'TAX06',
				'tax_rate_priority' => '2',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$tax_rate_015 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '1.5000',
				'tax_rate_name'     => 'TAX015',
				'tax_rate_priority' => '3',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$tax_rate_01 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '1.000',
				'tax_rate_name'     => 'TAX01',
				'tax_rate_priority' => '4',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Add product to cart x1, calc and test.
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( '85.92', number_format( WC()->cart->total, 2, '.', '' ) );
		$this->assertEquals( '85.92', number_format( WC()->cart->subtotal, 2, '.', '' ) );
		$this->assertEquals( '85.92', wc_get_price_including_tax( $product ) );

		WC_Helper_Product::delete_product( $product->get_id() );
		WC_Tax::_delete_tax_rate( $tax_rate_025 );
		WC_Tax::_delete_tax_rate( $tax_rate_06 );
		WC_Tax::_delete_tax_rate( $tax_rate_015 );
		WC_Tax::_delete_tax_rate( $tax_rate_01 );
	}

	/**
	 * Test tax rounding.
	 * Ticket: https://github.com/woocommerce/woocommerce/issues/21021.
	 */
	public function test_cart_get_total_issue_21021() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );

		// Set an address so that shipping can be calculated.
		WC_Helper_Shipping::force_customer_us_address();

		// Create tax classes first.
		WC_Tax::create_tax_class( '23percent' );
		WC_Tax::create_tax_class( '5percent' );

		$tax_rate    = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '23.0000',
			'tax_rate_name'     => 'TAX23',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '23percent',
		);
		$tax_rate_23 = WC_Tax::_insert_tax_rate( $tax_rate );

		$tax_rate   = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.0000',
			'tax_rate_name'     => 'TAX5',
			'tax_rate_priority' => '2',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '5percent',
		);
		$tax_rate_5 = WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product with price 19.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 19 );
		$product->set_regular_price( 19 );
		$product->set_tax_class( '5percent' );
		$product->save();

		// Create product with price 59.
		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_price( 59 );
		$product2->set_regular_price( 59 );
		$product2->set_tax_class( '23percent' );
		$product2->save();

		// Create a flat rate method.
		$flat_rate_settings = array(
			'enabled'      => 'yes',
			'title'        => 'Flat rate',
			'availability' => 'all',
			'countries'    => '',
			'tax_status'   => 'taxable',
			'cost'         => '8.05',
		);
		update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );
		update_option( 'woocommerce_flat_rate', array() );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping->load_shipping_methods();

		WC()->cart->empty_cart();

		// Set the flat_rate shipping method.
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );

		// Add product to cart x1, calc and test.
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->cart->calculate_totals();
		$this->assertEquals( 27.05, WC()->cart->total );

		// Add product2 to cart.
		WC()->cart->add_to_cart( $product2->get_id(), 1 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->cart->calculate_totals();
		$this->assertEquals( 87.9, WC()->cart->total );

		WC_Helper_Product::delete_product( $product->get_id() );
		WC_Helper_Product::delete_product( $product2->get_id() );

		WC_Tax::_delete_tax_rate( $tax_rate_23 );
		WC_Tax::_delete_tax_rate( $tax_rate_5 );
	}

	/**
	 * Test for subtotal when multiple tax slabs are present and round at subtotal is enabled.
	 *
	 * Ticket: @link https://github.com/woocommerce/woocommerce/issues/23917
	 */
	public function test_cart_calculate_total_rounding_23917() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '24.0000',
			'tax_rate_name'     => 'CGST',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => 'tax_1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '24.0000',
			'tax_rate_name'     => 'SGST',
			'tax_rate_priority' => '2',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => 'tax_2',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create a product with price 599.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 599 );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( 599, wc_format_decimal( WC()->cart->subtotal, wc_get_price_decimals() ) );
		$this->assertEquals( 599, WC()->cart->total );
	}

	/**
	 * Test some discount logic which has caused issues in the past.
	 * Ticket: https://github.com/woocommerce/woocommerce/issues/10963.
	 *
	 * Due to discounts being split amongst products in cart.
	 */
	public function test_cart_get_discounted_price_issue_10963() {
		// Create dummy coupon - fixed cart, 1 value.
		$coupon = WC_Helper_Coupon::create_coupon();

		// Add coupon.
		WC()->cart->add_discount( $coupon->get_code() );

		// Create dummy product - price will be 10.
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart x1, calc and test.
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( '9.00', number_format( WC()->cart->total, 2, '.', '' ) );
		$this->assertEquals( '1.00', number_format( WC()->cart->discount_cart, 2, '.', '' ) );

		// Add product to cart x2, calc and test.
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( '19.00', number_format( WC()->cart->total, 2, '.', '' ) );
		$this->assertEquals( '1.00', number_format( WC()->cart->discount_cart, 2, '.', '' ) );

		// Add product to cart x3, calc and test.
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( '29.00', number_format( WC()->cart->total, 2, '.', '' ) );
		$this->assertEquals( '1.00', number_format( WC()->cart->discount_cart, 2, '.', '' ) );
	}

	/**
	 * Ticket: https://github.com/woocommerce/woocommerce/issues/11626
	 */
	public function test_cart_get_discounted_price_issue_11626() {
		$expected_values = array(
			1 => '13.90', // Tax exempt customer: all the prices without tax, then halved, i.e. sum = 33.09, sum_w/o_tax = 27.807, halved = 13.9035.
			0 => '16.55', // Normal customer: all the prices halved, i.e. sum = 33.09, halved = 16.545.
		);

		// Create dummy coupon - fixed cart, 1 value.
		$coupon = WC_Helper_Coupon::create_coupon();

		// Test case 3 #11626.
		update_post_meta( $coupon->get_id(), 'discount_type', 'percent' );
		update_post_meta( $coupon->get_id(), 'coupon_amount', '50' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '19.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$product_ids   = array();
		$products_data = array(
			'5.17',
			'3.32',
			'1.25',
			'3.50',
			'5.01',
			'3.34',
			'5.99',
			'5.51',
		);

		foreach ( $expected_values as $customer_tax_exempt => $value ) {
			WC()->customer->set_is_vat_exempt( $customer_tax_exempt );

			foreach ( $products_data as $price ) {
				$loop_product  = WC_Helper_Product::create_simple_product();
				$product_ids[] = $loop_product->get_id();
				$loop_product->set_regular_price( $price );
				$loop_product->save();
				WC()->cart->add_to_cart( $loop_product->get_id(), 1 );
			}

			WC()->cart->add_discount( $coupon->get_code() );
			WC()->cart->calculate_totals();
			$this->assertEquals( $value, WC()->cart->total );

			WC()->cart->empty_cart();
		}
	}

	/**
	 * Ticket: https://github.com/woocommerce/woocommerce/issues/10573
	 */
	public function test_cart_get_discounted_price_issue_10573() {
		// Create dummy coupon - fixed cart, 1 value.
		$coupon = WC_Helper_Coupon::create_coupon();

		// Create dummy product - price will be 10.
		$product = WC_Helper_Product::create_simple_product();

		$product->set_regular_price( '29.95' );
		$product->save();
		update_post_meta( $coupon->get_id(), 'discount_type', 'percent' );
		update_post_meta( $coupon->get_id(), 'coupon_amount', '10' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );
		$product = wc_get_product( $product->get_id() );

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );

		WC()->cart->calculate_totals();
		$cart_item = current( WC()->cart->get_cart() );
		$this->assertEquals( '24.51', number_format( $cart_item['line_total'], 2, '.', '' ) );
	}

	/**
	 * Test that calculation rounding is done correctly with and without taxes.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/16305.
	 * @since 3.2
	 */
	public function test_discount_cart_rounding() {
		$expected_values = array(
			1 => array( '31.12', '31.12' ), // Tax exempt customer: Total and Total + tax are the same.
			0 => array( '31.12', '33.69' ), // Normal customer: Total, then Total + tax.
		);

		// Test with no taxes.
		$product = new WC_Product_Simple();
		$product->set_regular_price( 51.86 );
		$product->save();

		$coupon = new WC_Coupon();
		$coupon->set_code( 'testpercent' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 40 );
		$coupon->save();

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '8.2500',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		foreach ( $expected_values as $customer_tax_exempt => $values ) {
			WC()->customer->set_is_vat_exempt( $customer_tax_exempt );

			// Test without taxes.
			update_option( 'woocommerce_prices_include_tax', 'yes' );
			update_option( 'woocommerce_calc_taxes', 'no' );

			WC()->cart->add_to_cart( $product->get_id(), 1 );
			WC()->cart->add_discount( $coupon->get_code() );

			WC()->cart->calculate_totals();
			$cart_item = current( WC()->cart->get_cart() );
			$this->assertEquals( $values[0], number_format( $cart_item['line_total'], 2, '.', '' ) );

			// Clean up.
			WC()->cart->empty_cart();
			WC()->cart->remove_coupons();

			// Test with taxes.
			update_option( 'woocommerce_prices_include_tax', 'no' );
			update_option( 'woocommerce_calc_taxes', 'yes' );

			WC()->cart->add_to_cart( $product->get_id(), 1 );
			WC()->cart->add_discount( $coupon->get_code() );

			WC()->cart->calculate_totals();
			$cart_item = current( WC()->cart->get_cart() );
			$this->assertEquals( $values[1], number_format( $cart_item['line_total'] + $cart_item['line_tax'], 2, '.', '' ) );

			WC()->cart->empty_cart();
		}
	}

	/**
	 * Test cart calculations when out of base location and using inclusive taxes and discounts.
	 *
	 * @see GitHub issues #17517 and #17536.
	 * @since 3.3
	 */
	public function test_out_of_base_discounts_inclusive_tax() {
		// Set up tax options.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );

		// 20% tax for GB.
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// 20% tax everywhere else.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product.
		$product = new WC_Product_Simple();
		$product->set_regular_price( '9.99' );
		$product->save();

		// Create coupons.
		$ten_coupon = new WC_Coupon();
		$ten_coupon->set_code( '10off' );
		$ten_coupon->set_discount_type( 'percent' );
		$ten_coupon->set_amount( 10 );
		$ten_coupon->save();

		$half_coupon = new WC_Coupon();
		$half_coupon->set_code( '50off' );
		$half_coupon->set_discount_type( 'percent' );
		$half_coupon->set_amount( 50 );
		$half_coupon->save();

		$full_coupon = new WC_Coupon();
		$full_coupon->set_code( '100off' );
		$full_coupon->set_discount_type( 'percent' );
		$full_coupon->set_amount( 100 );
		$full_coupon->save();

		add_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_gb_country' ) );
		add_filter( 'woocommerce_customer_get_shipping_postcode', array( $this, 'force_customer_gb_postcode' ) );
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test in store location with no coupon.
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '1.66', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '9.99', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Test in store location with 10% coupon.
		WC()->cart->add_discount( $ten_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.83', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '1.50', wc_format_decimal( WC()->cart->get_total_tax(), 2 ), WC()->cart->get_total_tax() );
		$this->assertEquals( '8.99', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test in store location with 50% coupon.
		WC()->cart->add_discount( $half_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '4.16', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.83', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '5.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test in store location with 100% coupon.
		WC()->cart->add_discount( $full_coupon->get_code() );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_discount_total(), 2 ), 'Discount total in base' );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		WC()->cart->empty_cart();
		remove_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_gb_country' ) );
		remove_filter( 'woocommerce_customer_get_shipping_postcode', array( $this, 'force_customer_gb_postcode' ) );
		WC_Helper_Shipping::force_customer_us_address();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test out of store location with no coupon.
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '1.66', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '9.99', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Test out of store location with 10% coupon.
		WC()->cart->add_discount( $ten_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.83', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '1.50', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '8.99', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test out of store location with 50% coupon.
		WC()->cart->add_discount( $half_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '4.16', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.83', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '5.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test out of store location with 100% coupon.
		WC()->cart->add_discount( $full_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_discount_total(), 2 ), 'Discount total out of base' );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
	}

	/**
	 * Test cart calculations when out of base location and using inclusive taxes and discounts, for tax exempt customer.
	 *
	 * @see GitHub issues #17517 and #17536.
	 * @since 3.5
	 */
	public function test_out_of_base_discounts_inclusive_tax_for_tax_exempt_customer() {
		WC()->customer->set_is_vat_exempt( true );

		// Set up tax options.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );

		// 20% tax for GB.
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// 20% tax everywhere else.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product.
		$product = new WC_Product_Simple();
		$product->set_regular_price( '9.99' );
		$product->save();

		// Create coupons.
		$ten_coupon = new WC_Coupon();
		$ten_coupon->set_code( '10off' );
		$ten_coupon->set_discount_type( 'percent' );
		$ten_coupon->set_amount( 10 );
		$ten_coupon->save();

		$half_coupon = new WC_Coupon();
		$half_coupon->set_code( '50off' );
		$half_coupon->set_discount_type( 'percent' );
		$half_coupon->set_amount( 50 );
		$half_coupon->save();

		$full_coupon = new WC_Coupon();
		$full_coupon->set_code( '100off' );
		$full_coupon->set_discount_type( 'percent' );
		$full_coupon->set_amount( 100 );
		$full_coupon->save();

		add_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_gb_country' ) );
		add_filter( 'woocommerce_customer_get_shipping_postcode', array( $this, 'force_customer_gb_postcode' ) );
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test in store location with no coupon.
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Test in store location with 10% coupon.
		WC()->cart->add_discount( $ten_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.83', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ), WC()->cart->get_total_tax() );
		$this->assertEquals( '7.50', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test in store location with 50% coupon.
		WC()->cart->add_discount( $half_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '4.16', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '4.17', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test in store location with 100% coupon.
		WC()->cart->add_discount( $full_coupon->get_code() );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_discount_total(), 2 ), 'Discount total in base' );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		WC()->cart->empty_cart();
		remove_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_gb_country' ) );
		remove_filter( 'woocommerce_customer_get_shipping_postcode', array( $this, 'force_customer_gb_postcode' ) );
		WC_Helper_Shipping::force_customer_us_address();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test out of store location with no coupon.
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Test out of store location with 10% coupon.
		WC()->cart->add_discount( $ten_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.83', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '7.50', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test out of store location with 50% coupon.
		WC()->cart->add_discount( $half_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '4.16', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '4.17', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test out of store location with 100% coupon.
		WC()->cart->add_discount( $full_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_discount_total(), 2 ), 'Discount total out of base' );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
	}

	/**
	 * Test cart calculations when out of base location with no matching taxes and using inclusive taxes and discounts.
	 *
	 * @see GitHub issue #19390.
	 * @since 3.3
	 */
	public function test_out_of_base_discounts_inclusive_tax_no_oob_tax() {
		// Set up tax options.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );

		// 20% tax for GB.
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// 0% tax everywhere else.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '0.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product.
		$product = new WC_Product_Simple();
		$product->set_regular_price( '24.99' );
		$product->save();

		// Create coupon.
		$ten_coupon = new WC_Coupon();
		$ten_coupon->set_code( '10off' );
		$ten_coupon->set_discount_type( 'percent' );
		$ten_coupon->set_amount( 10 );
		$ten_coupon->save();

		$half_coupon = new WC_Coupon();
		$half_coupon->set_code( '50off' );
		$half_coupon->set_discount_type( 'percent' );
		$half_coupon->set_amount( 50 );
		$half_coupon->save();

		$full_coupon = new WC_Coupon();
		$full_coupon->set_code( '100off' );
		$full_coupon->set_discount_type( 'percent' );
		$full_coupon->set_amount( 100 );
		$full_coupon->save();

		WC_Helper_Shipping::force_customer_us_address();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test out of store location with no coupon.
		WC()->cart->calculate_totals();
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Test out of store location with 10% coupon.
		WC()->cart->add_discount( $ten_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '2.08', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '18.75', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test out of store location with 50% coupon.
		WC()->cart->add_discount( $half_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '10.41', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '10.42', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test out of store location with 100% coupon.
		WC()->cart->add_discount( $full_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_discount_total(), 2 ), 'Discount total out of base' );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
	}

	/**
	 * Helper that can be hooked to a filter to force the customer's shipping country to be GB.
	 *
	 * @since 3.3
	 * @param string $country Country code.
	 * @return string
	 */
	public function force_customer_gb_country( $country ) {
		return 'GB';
	}

	/**
	 * Helper that can be hooked to a filter to force the customer's shipping postal code to be ANN NAA.
	 *
	 * @since 4.0.0
	 * @param string $postcode Postal code..
	 * @return string
	 */
	public function force_customer_gb_postcode( $postcode ) {
		return 'ANN NAA';
	}

	/**
	 * Helper that can be hooked to a filter to force the customer's shipping country to be US.
	 *
	 * @since 3.3
	 * @param string $country Country code.
	 * @return string
	 */
	public function force_customer_us_country( $country ) {
		return WC_Helper_Shipping::force_customer_us_country( $country );
	}

	/**
	 * Helper that can be hooked to a filter to force the customer's shipping state to be NY.
	 *
	 * @since 4.0.0
	 * @param string $state State code.
	 * @return string
	 */
	public function force_customer_us_state( $state ) {
		return WC_Helper_Shipping::force_customer_us_state( $state );
	}

	/**
	 * Helper that can be hooked to a filter to force the customer's shipping postal code to be 12345.
	 *
	 * @since 4.0.0
	 * @param string $postcode Postal code.
	 * @return string
	 */
	public function force_customer_us_postcode( $postcode ) {
		return WC_Helper_Shipping::force_customer_us_postcode( $postcode );
	}

	/**
	 * Test a rounding issue on prices that are entered inclusive tax and shipping is used.
	 * See: #17970.
	 *
	 * @since 3.2.6
	 */
	public function test_inclusive_tax_rounding() {
		$expected_values = array(
			1 => array( // Tax exempt customer.
				'total'     => '129.45', // Price -> price w/o tax, then + shipping, i.e. 149.14 -> 125.3277 + 4.12 = 129.45.
				'total_tax' => '0.00',   // No tax.
			),
			0 => array( // Normal customer.
				'total'     => '154.04', // Price + shipping + shipping tax, i.e. 149.14 + 4.12 * 1.19 = 154.04.
				'total_tax' => '24.59',  // Tax = price tax + shipping tax.
			),
		);

		// Store is set to enter product prices inclusive tax.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Set an address so that shipping can be calculated.
		WC_Helper_Shipping::force_customer_us_address();

		// 19% tax.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '19.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create a flat rate method.
		$flat_rate_settings = array(
			'enabled'      => 'yes',
			'title'        => 'Flat rate',
			'availability' => 'all',
			'countries'    => '',
			'tax_status'   => 'taxable',
			'cost'         => '4.12',
		);
		update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );
		update_option( 'woocommerce_flat_rate', array() );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

		// Create the product and add it to the cart.
		$product = new WC_Product_Simple();
		$product->set_regular_price( '149.14' );
		$product->save();

		foreach ( $expected_values as $customer_tax_exempt => $values ) {
			WC()->customer->set_is_vat_exempt( $customer_tax_exempt );

			WC()->cart->add_to_cart( $product->get_id(), 1 );

			// Set the flat_rate shipping method.
			WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );

			WC()->cart->calculate_totals();
			$this->assertEquals( $values['total'], wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
			$this->assertEquals( $values['total_tax'], wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );

			WC()->cart->empty_cart();
		}
	}

	/**
	 * Test a rounding issue on prices that are entered inclusive tax.
	 * See #20997
	 *
	 * @since 3.5.0
	 */
	public function test_inclusive_tax_rounding_issue_20997() {
		// Store is set to enter product prices inclusive tax.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_currency', 'EUR' );
		update_option( 'woocommerce_price_decimal_sep', ',' );

		// 22% tax.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '22.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create products and add them to cart.
		$product1 = new WC_Product_Simple();
		$product1->set_regular_price( '46,00' );
		$product1->save();

		$product2 = new WC_Product_Simple();
		$product2->set_regular_price( '22,50' );
		$product2->save();

		$product3 = new WC_Product_Simple();
		$product3->set_regular_price( '69,00' );
		$product3->save();

		$product4 = new WC_Product_Simple();
		$product4->set_regular_price( '43,00' );
		$product4->save();

		$product5 = new WC_Product_Simple();
		$product5->set_regular_price( '27,00' );
		$product5->save();

		$product6 = new WC_Product_Simple();
		$product6->set_regular_price( '100.00' );
		$product6->save();

		WC()->cart->add_to_cart( $product1->get_id(), 1 );
		WC()->cart->add_to_cart( $product2->get_id(), 1 );
		WC()->cart->calculate_totals();

		$expected_price = '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&euro;</span>68,50</bdi></span>';
		$this->assertEquals( $expected_price, WC()->cart->get_total() );
		$this->assertEquals( '12.36', wc_round_tax_total( WC()->cart->get_total_tax( 'edit' ) ) );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product3->get_id(), 1 );
		WC()->cart->add_to_cart( $product4->get_id(), 1 );
		WC()->cart->calculate_totals();

		$expected_price = '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&euro;</span>112,00</bdi></span>';
		$this->assertEquals( $expected_price, WC()->cart->get_total() );
		$this->assertEquals( '20.19', wc_round_tax_total( WC()->cart->get_total_tax( 'edit' ) ) );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product3->get_id(), 1 );
		WC()->cart->add_to_cart( $product4->get_id(), 1 );
		WC()->cart->add_to_cart( $product5->get_id(), 1 );
		WC()->cart->add_to_cart( $product6->get_id(), 1 );
		WC()->cart->calculate_totals();

		$expected_price = '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&euro;</span>239,00</bdi></span>';
		$this->assertEquals( $expected_price, WC()->cart->get_total() );
		$this->assertEquals( '43.09', wc_round_tax_total( WC()->cart->get_total_tax( 'edit' ) ) );
	}

	/**
	 * Test a rounding issue on prices that are entered exclusive tax.
	 * See: #17970.
	 *
	 * @since 3.2.6
	 */
	public function test_exclusive_tax_rounding() {
		$expected_values = array(
			1 => '95.84',  // Tax exempt customer: Total = simple sum of prices w/o tax.
			0 => '115.00', // Normal customer: Total = sum of prices with tax added.
		);

		// Store is set to enter product prices excluding tax.
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// 20% tax.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Add 2 products whose retail prices (inc tax) are: £65, £50.
		// Their net prices are therefore: £54.1666667 and £41.6666667.
		$product1 = new WC_Product_Simple();
		$product1->set_regular_price( '54.1666667' );
		$product1->save();

		$product2 = new WC_Product_Simple();
		$product2->set_regular_price( '41.6666667' );
		$product2->save();

		foreach ( $expected_values as $customer_tax_exempt => $value ) {
			WC()->customer->set_is_vat_exempt( $customer_tax_exempt );

			WC()->cart->add_to_cart( $product1->get_id(), 1 );
			WC()->cart->add_to_cart( $product2->get_id(), 1 );

			WC()->cart->calculate_totals();
			$this->assertEquals( $value, wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

			WC()->cart->empty_cart();
		}
	}

	/**
	 * Test a rounding issue on prices and totals that are entered exclusive tax.
	 * See: #17647.
	 *
	 * @since 3.2.6
	 */
	public function test_exclusive_tax_rounding_and_totals() {
		$expected_values = array(
			1 => array( // Tax exempt customer.
				'total_tax'           => '0.00',
				'cart_contents_tax'   => '0.00',
				'cart_contents_total' => '44.17',
				'total'               => '44.17',
			),
			0 => array( // Normal customer.
				'total_tax'           => '2.44',
				'cart_contents_tax'   => '2.44',
				'cart_contents_total' => '44.17',
				'total'               => '46.61',
			),
		);

		$product_data = array(
			// price, quantity.
			array( 2.13, 1 ),
			array( 2.55, 0.5 ),
			array( 39, 1 ),
			array( 1.76, 1 ),
		);

		foreach ( $product_data as $data ) {
			$product = new WC_Product_Simple();
			$product->set_regular_price( $data[0] );
			$product->save();
			$products[] = array( $product, $data[1] );
		}

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.5',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		WC_Tax::_insert_tax_rate( $tax_rate );

		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );

		foreach ( $expected_values as $customer_tax_exempt => $values ) {
			WC()->customer->set_is_vat_exempt( $customer_tax_exempt );

			foreach ( $products as $data ) {
				WC()->cart->add_to_cart( $data[0]->get_id(), $data[1] );
			}

			WC()->cart->calculate_totals();

			$cart_totals = WC()->cart->get_totals();

			$this->assertEquals( $values['total_tax'], wc_format_decimal( $cart_totals['total_tax'], 2 ) );
			$this->assertEquals( $values['cart_contents_tax'], wc_format_decimal( $cart_totals['cart_contents_tax'], 2 ) );
			$this->assertEquals( $values['cart_contents_total'], wc_format_decimal( $cart_totals['cart_contents_total'], 2 ) );
			$this->assertEquals( $values['total'], wc_format_decimal( $cart_totals['total'], 2 ) );

			WC()->cart->empty_cart();
		}
	}

	/**
	 * Test get_remove_url.
	 *
	 * @since 2.3
	 */
	public function test_get_remove_url() {
		// Get the cart page id.
		$cart_page_url = wc_get_page_permalink( 'cart' );

		// Test cart item key.
		$cart_item_key = 'test';

		// Do the check.
		$this->assertEquals( apply_filters( 'woocommerce_get_remove_url', $cart_page_url ? wp_nonce_url( add_query_arg( 'remove_item', $cart_item_key, $cart_page_url ), 'woocommerce-cart' ) : '' ), wc_get_cart_remove_url( $cart_item_key ) );
	}

	/**
	 * Test add to cart simple product.
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_simple() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		// Add the product to the cart. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 1 ) );

		// Check if the item is in the cart.
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Check if we can add a trashed product to the cart.
	 */
	public function test_add_to_cart_trashed() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		// Trash product.
		wp_trash_post( $product->get_id() );

		// Refetch product, to be sure.
		$product = wc_get_product( $product->get_id() );

		// Add product to cart.
		$this->assertFalse( WC()->cart->add_to_cart( $product->get_id(), 1 ) );
	}

	/**
	 * Test add to cart variable product.
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_variable() {
		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();
		$variation  = array_shift( $variations );

		// Add the product to the cart. Methods returns boolean on failure, string on success.
		$result = WC()->cart->add_to_cart(
			$product->get_id(),
			1,
			$variation['variation_id'],
			array(
				'attribute_pa_colour' => 'red', // Set a value since this is an 'any' attribute.
				'attribute_pa_number' => '2', // Set a value since this is an 'any' attribute.
			)
		);
		$this->assertNotFalse( $result );

		// Check if the item is in the cart.
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Check if adding a product that is sold individually is corrected when adding multiple times.
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_sold_individually() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		$product->set_sold_individually( true );
		$product->save();

		// Add the product twice to cart, should be corrected to 1. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 2 ) );

		// Check if the item is in the cart.
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test the find_product_in_cart method.
	 *
	 * @since 2.3
	 */
	public function test_find_product_in_cart() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Generate cart id.
		$cart_id = WC()->cart->generate_cart_id( $product->get_id() );

		// Get the product from the cart.
		$this->assertNotEquals( '', WC()->cart->find_product_in_cart( $cart_id ) );
	}

	/**
	 * Test the generate_cart_id method.
	 *
	 * @since 2.3
	 */
	public function test_generate_cart_id() {
		// Setup data.
		$product_id     = 1;
		$variation_id   = 2;
		$variation      = array( 'Testing' => 'yup' );
		$cart_item_data = array(
			'string_val' => 'The string I was talking about',
			'array_val'  => array(
				'this',
				'is',
				'an',
				'array',
			),
		);

		// Manually generate ID.
		$id_parts = array( $product_id );

		if ( $variation_id && 0 != $variation_id ) {
			$id_parts[] = $variation_id;
		}

		if ( is_array( $variation ) && ! empty( $variation ) ) {
			$variation_key = '';
			foreach ( $variation as $key => $value ) {
				$variation_key .= trim( $key ) . trim( $value );
			}
			$id_parts[] = $variation_key;
		}

		if ( is_array( $cart_item_data ) && ! empty( $cart_item_data ) ) {
			$cart_item_data_key = '';
			foreach ( $cart_item_data as $key => $value ) {

				if ( is_array( $value ) ) {
					$value = http_build_query( $value );
				}
				$cart_item_data_key .= trim( $key ) . trim( $value );

			}
			$id_parts[] = $cart_item_data_key;
		}

		$manual_cart_id = md5( implode( '_', $id_parts ) );

		$this->assertEquals( $manual_cart_id, WC()->cart->generate_cart_id( $product_id, $variation_id, array( 'Testing' => 'yup' ), $cart_item_data ) );

	}

	/**
	 * Test the set_quantity method.
	 *
	 * @since 2.3
	 */
	public function test_set_quantity() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		// Add 1 product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Get cart id.
		$cart_id = WC()->cart->generate_cart_id( $product->get_id() );

		// Set quantity of product in cart to 2.
		$this->assertTrue( WC()->cart->set_quantity( $cart_id, 2 ), $cart_id );

		// Check if there are 2 items in cart now.
		$this->assertEquals( 2, WC()->cart->get_cart_contents_count() );

		// Set quantity of product in cart to 0.
		$this->assertTrue( WC()->cart->set_quantity( $cart_id, 0 ) );

		// Check if there are 0 items in cart now.
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test check_cart_item_validity method.
	 *
	 * @since 2.3
	 */
	public function test_check_cart_item_validity() {

		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Check cart validity, should pass.
		$this->assertTrue( WC()->cart->check_cart_item_validity() );
	}

	/**
	 * Test get_total.
	 *
	 * @since 2.3
	 */
	public function test_get_total() {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$this->assertEquals( apply_filters( 'woocommerce_cart_total', wc_price( WC()->cart->total ) ), WC()->cart->get_total() );
	}

	/**
	 * Test get_total_ex_tax.
	 *
	 * @since 2.3
	 */
	public function test_get_total_ex_tax() {
		$expected_values = array(
			1 => array( // Tax exempt customer.
				'total'        => 20,
				'total_ex_tax' => 20,
			),
			0 => array( // Normal customer.
				'total'        => 22,
				'total_ex_tax' => 20,
			),
		);

		// Set calc taxes option.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		foreach ( $expected_values as $customer_tax_exempt => $values ) {
			WC()->customer->set_is_vat_exempt( $customer_tax_exempt );

			// Add 10 fee.
			WC_Helper_Fee::add_cart_fee( 'taxed' );

			// Add product to cart (10).
			WC()->cart->add_to_cart( $product->get_id(), 1 );

			// Check.
			$this->assertEquals( wc_price( $values['total'] ), WC()->cart->get_total() );
			$this->assertEquals( wc_price( $values['total_ex_tax'] ), WC()->cart->get_total_ex_tax() );

			WC()->cart->empty_cart();
		}
	}

	/**
	 * Test needs_shipping_address method.
	 */
	public function test_needs_shipping_address() {
		$needs_shipping_address = false;

		if ( WC()->cart->needs_shipping() === true && ! wc_ship_to_billing_address_only() ) {
			$needs_shipping_address = true;
		}

		$this->assertEquals( apply_filters( 'woocommerce_cart_needs_shipping_address', $needs_shipping_address ), WC()->cart->needs_shipping_address() );
	}

	/**
	 * Test shipping total.
	 *
	 * @since 2.3
	 */
	public function test_shipping_total() {
		// Create product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		// Create a flat rate method.
		WC_Helper_Shipping::create_simple_flat_rate();

		// Add product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Set an address so that shipping can be calculated.
		WC_Helper_Shipping::force_customer_us_address();

		// Set the flat_rate shipping method.
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->cart->calculate_totals();

		// Test if the shipping total amount is equal 20.
		$this->assertEquals( 10, WC()->cart->shipping_total );

		// Test if the cart total amount is equal 20.
		$this->assertEquals( 20, WC()->cart->total );
	}

	/**
	 * Test that shipping tax rounding does not round down when price are inclusive of taxes.
	 */
	public function test_calculate_totals_shipping_tax_rounded_26654() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '25.0000',
			'tax_rate_name'     => 'Tax @ 25%',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => 'standard',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 242 );
		$product->set_tax_class( 'product' );
		$product->save();

		WC_Helper_Shipping::create_simple_flat_rate( 75.10 );
		WC_Helper_Shipping::force_customer_us_address();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->cart->calculate_totals();

		$this->assertEquals( 18.775, WC()->cart->get_shipping_tax() );
		$this->assertEquals( 335.88, WC()->cart->get_total( 'edit' ) );
		$this->assertEquals( 67.18, WC()->cart->get_taxes_total() );

		$checkout = WC_Checkout::instance();
		$order = new WC_Order();
		$checkout->set_data_from_cart( $order );
		$this->assertEquals( 67.18, $order->get_total_tax() );
		$this->assertEquals( 335.88, $order->get_total() );
		$this->assertEquals( 18.775, $order->get_shipping_tax() );

		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		WC()->cart->calculate_totals();

		$this->assertEquals( 18.78, WC()->cart->get_shipping_tax() );
		$this->assertEquals( 335.88, WC()->cart->get_total( 'edit' ) );
		$this->assertEquals( 67.18, WC()->cart->get_taxes_total() );

		$order = new WC_Order();
		$checkout->set_data_from_cart( $order );
		$this->assertEquals( 67.18, $order->get_total_tax() );
		$this->assertEquals( 335.88, $order->get_total() );
		$this->assertEquals( 18.78, $order->get_shipping_tax() );
	}

	/**
	 * Test cart fee.
	 *
	 * @since 2.3
	 */
	public function test_cart_fee() {
		// Create product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		// Add fee.
		WC_Helper_Fee::add_cart_fee();

		// Add product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test if the cart total amount is equal 20.
		$this->assertEquals( 20, WC()->cart->total );
	}

	/**
	 * Test cart fee with taxes.
	 *
	 * @since 3.2
	 */
	public function test_cart_fee_taxes() {
		$expected_values = array(
			1 => 20, // Tax exempt customer.
			0 => 22, // Normal customer.
		);

		// Set up taxes.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		foreach ( $expected_values as $customer_tax_exempt => $value ) {
			WC()->customer->set_is_vat_exempt( $customer_tax_exempt );

			// Add fee.
			WC_Helper_Fee::add_cart_fee( 'taxed' );

			// Add product to cart.
			WC()->cart->add_to_cart( $product->get_id(), 1 );

			// Test if the cart total amount is equal 22 ($10 item + $10 fee + 10% taxes).
			$this->assertEquals( $value, WC()->cart->total );

			WC()->cart->empty_cart();
		}
	}

	/**
	 * Test negative cart fee.
	 *
	 * @since 3.2
	 */
	public function test_cart_negative_fee() {
		// Create product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 15 );
		$product->save();

		// Add fee.
		WC_Helper_Fee::add_cart_fee( 'negative' );

		// Add product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test if the cart total amount is equal 5.
		$this->assertEquals( 5, WC()->cart->total );
	}

	/**
	 * Test negative cart fee with taxes.
	 *
	 * @since 3.2
	 */
	public function test_cart_negative_fee_taxes() {
		$expected_values = array(
			1 => 5.00, // Tax exempt customer.
			0 => 5.50, // Normal customer: cart total amount is equal 5.50 ($15 item - $10 negative fee + 10% tax).
		);

		// Set up taxes.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 15 );
		$product->save();

		foreach ( $expected_values as $customer_tax_exempt => $value ) {
			WC()->customer->set_is_vat_exempt( $customer_tax_exempt );

			// Add fee.
			WC_Helper_Fee::add_cart_fee( 'negative-taxed' );

			// Add product to cart.
			WC()->cart->add_to_cart( $product->get_id(), 1 );

			// Test if the cart total amount is correct.
			$this->assertEquals( $value, WC()->cart->total );

			WC()->cart->empty_cart();
		}
	}

	/**
	 * Test cart coupons.
	 */
	public function test_get_coupons() {
		// Create coupon.
		$coupon = WC_Helper_Coupon::create_coupon();

		// Add coupon.
		WC()->cart->add_discount( $coupon->get_code() );

		$this->assertEquals( count( WC()->cart->get_coupons() ), 1 );
	}

	/**
	 * Test add_discount allows coupons by code but not by ID.
	 *
	 * @since 3.2
	 */
	public function test_add_discount_code_id() {

		$coupon = new WC_Coupon();
		$coupon->set_code( 'test' );
		$coupon->set_amount( 100 );
		$coupon->save();

		$success = WC()->cart->add_discount( $coupon->get_code() );
		$this->assertTrue( $success );

		$success = WC()->cart->add_discount( (string) $coupon->get_id() );
		$this->assertFalse( $success );
	}

	/**
	 * test_add_invidual_use_coupon.
	 */
	public function test_add_invidual_use_coupon() {
		$iu_coupon = WC_Helper_Coupon::create_coupon( 'code1' );
		$iu_coupon->set_individual_use( true );
		$iu_coupon->save();
		$coupon = WC_Helper_Coupon::create_coupon();

		WC()->cart->add_discount( $iu_coupon->get_code() );
		WC()->cart->add_discount( $coupon->get_code() );

		$coupons = WC()->cart->get_coupons();

		$this->assertEquals( count( $coupons ), 1 );
		$this->assertEquals( 'code1', reset( $coupons )->get_code() );
	}

	/**
	 * test_add_individual_use_coupon_removal.
	 */
	public function test_add_individual_use_coupon_removal() {
		$coupon    = WC_Helper_Coupon::create_coupon();
		$iu_coupon = WC_Helper_Coupon::create_coupon( 'code1' );
		$iu_coupon->set_individual_use( true );
		$iu_coupon->save();

		WC()->cart->add_discount( $coupon->get_code() );
		WC()->cart->add_discount( $iu_coupon->get_code() );

		$coupons = WC()->cart->get_coupons();

		$this->assertEquals( count( $coupons ), 1 );
		$this->assertEquals( 'code1', reset( $coupons )->get_code() );
		$this->assertEquals( 1, did_action( 'woocommerce_removed_coupon' ) );
	}

	/**
	 * test_add_individual_use_coupon_double_individual.
	 */
	public function test_add_individual_use_coupon_double_individual() {
		$iu_coupon1 = WC_Helper_Coupon::create_coupon( 'code1' );
		$iu_coupon1->set_individual_use( true );
		$iu_coupon1->save();

		$iu_coupon2 = WC_Helper_Coupon::create_coupon( 'code2' );
		$iu_coupon2->set_individual_use( true );
		$iu_coupon2->save();

		WC()->cart->add_discount( $iu_coupon1->get_code() );
		WC()->cart->add_discount( $iu_coupon2->get_code() );

		$coupons = WC()->cart->get_coupons();

		$this->assertEquals( count( $coupons ), 1 );
		$this->assertEquals( 'code2', reset( $coupons )->get_code() );
	}

	/**
	 * test_clone_cart.
	 */
	public function test_clone_cart() {
		$cart              = wc()->cart;
		$new_cart          = clone $cart;
		$is_identical_cart = $cart === $new_cart;

		// Cloned carts should not be identical.
		$this->assertFalse( $is_identical_cart, 'Cloned cart not identical to original cart' );
	}

	/**
	 * test_cloned_cart_session.
	 */
	public function test_cloned_cart_session() {
		$cart     = wc()->cart;
		$new_cart = clone $cart;

		// Allow accessing protected properties.
		$reflected_cart = new ReflectionClass( $cart );
		$cart_session   = $reflected_cart->getProperty( 'session' );
		$cart_session->setAccessible( true );
		$reflected_new_cart = new ReflectionClass( $new_cart );
		$new_cart_session   = $reflected_new_cart->getProperty( 'session' );
		$new_cart_session->setAccessible( true );

		// Ensure that cloned properties are not identical.
		$identical_sessions = $cart_session->getValue( $cart ) === $new_cart_session->getValue( $new_cart );
		$this->assertFalse( $identical_sessions, 'Cloned cart sessions should not be identical to original cart' );
	}

	/**
	 * test_cloned_cart_fees.
	 */
	public function test_cloned_cart_fees() {
		$cart     = wc()->cart;
		$new_cart = clone $cart;

		// Get the properties from each object.
		$cart_fees     = $cart->fees_api();
		$new_cart_fees = $new_cart->fees_api();

		// Ensure that cloned properties are not identical.
		$identical_fees = $cart_fees === $new_cart_fees;
		$this->assertFalse( $identical_fees, 'Cloned cart fees should not be identical to original cart.' );
	}

	/**
	 * test_cart_object_istantiation.
	 */
	public function test_cart_object_istantiation() {
		$cart = new WC_Cart();
		$this->assertInstanceOf( 'WC_Cart', $cart );
	}

	/**
	 * test_get_cart_item_quantities.
	 */
	public function test_get_cart_item_quantities() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertEquals( 1, array_sum( WC()->cart->get_cart_item_quantities() ) );
	}

	/**
	 * test_get_cart_contents_weight.
	 */
	public function test_get_cart_contents_weight() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertEquals( 1.1, WC()->cart->get_cart_contents_weight() );
	}

	/**
	 * test_check_cart_items.
	 */
	public function test_check_cart_items() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertEquals( true, WC()->cart->check_cart_items() );
	}

	/**
	 * test_check_cart_item_stock.
	 */
	public function test_check_cart_item_stock() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertEquals( true, WC()->cart->check_cart_item_stock() );
	}

	/**
	 * test_get_cross_sells.
	 */
	public function test_get_cross_sells() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertEquals( array(), WC()->cart->get_cross_sells() );
	}

	/**
	 * test_get_tax_totals.
	 */
	public function test_get_tax_totals() {
		// Set calc taxes option.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		WC()->customer->set_is_vat_exempt( false );

		// Add product to cart (10).
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Check.
		$tax_totals = WC()->cart->get_tax_totals();
		$this->assertArrayHasKey( 'TAX-1', $tax_totals );
		$this->assertEquals( 1, $tax_totals['TAX-1']->amount );
		$this->assertEquals( false, $tax_totals['TAX-1']->is_compound );
		$this->assertEquals( 'TAX', $tax_totals['TAX-1']->label );

		WC()->cart->empty_cart();

		// Test the same for tax exempt customer.
		WC()->customer->set_is_vat_exempt( true );

		// Add product to cart (10).
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Check.
		$tax_totals = WC()->cart->get_tax_totals();
		$this->assertEquals( array(), $tax_totals );
	}

	/**
	 * Test is_coupon_emails_allowed function on the cart, specifically test wildcard emails.
	 *
	 * @return void
	 */
	public function test_is_coupon_emails_allowed() {
		$this->assertEquals( true, WC()->cart->is_coupon_emails_allowed( array( 'customer@wc.local' ), array( '*.local' ) ) );
		$this->assertEquals( false, WC()->cart->is_coupon_emails_allowed( array( 'customer@wc.local' ), array( '*.test' ) ) );
		$this->assertEquals( true, WC()->cart->is_coupon_emails_allowed( array( 'customer@wc.local' ), array( 'customer@wc.local' ) ) );
		$this->assertEquals( false, WC()->cart->is_coupon_emails_allowed( array( 'customer@wc.local' ), array( 'customer2@wc.local' ) ) );
	}

	/**
	 * Check subtotals align when using filters. Ref: 23340
	 */
	public function test_changing_tax_class_via_filter_issue_23340() {
		// Store is set to enter product prices inclusive tax.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// 5% tax.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// 20% tax.
		$tax_rate = array(
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
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create products and add them to cart.
		$product1 = new WC_Product_Simple();
		$product1->set_regular_price( '6' );
		$product1->save();

		WC()->cart->add_to_cart( $product1->get_id(), 1 );
		WC()->cart->calculate_totals();

		$this->assertEquals( '5.71', WC()->cart->get_subtotal() );
		$this->assertEquals( '6.00', WC()->cart->get_total( 'edit' ) );

		add_filter( 'woocommerce_product_get_tax_class', array( $this, 'change_tax_class_filter' ) );
		add_filter( 'woocommerce_product_variation_get_tax_class', array( $this, 'change_tax_class_filter' ) );

		WC()->cart->calculate_totals();
		$this->assertEquals( '5.71', WC()->cart->get_subtotal() );
		$this->assertEquals( '6.85', WC()->cart->get_total( 'edit' ) );

		remove_filter( 'woocommerce_product_get_tax_class', array( $this, 'change_tax_class_filter' ) );
		remove_filter( 'woocommerce_product_variation_get_tax_class', array( $this, 'change_tax_class_filter' ) );
	}

	/**
	 * Test rounding with fees as described in Github issue 25629.
	 */
	public function test_rounding_with_fees_25629() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );

		WC()->cart->empty_cart();
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX10',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 71.50 );
		$product->save();

		WC_Helper_Coupon::create_coupon(
			'3percent',
			array(
				'discount_type' => 'percent',
				'coupon_amount' => 3,
			)
		);

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fee_1_5_to_cart' ) );
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->apply_coupon( '3percent' );
		WC()->cart->calculate_totals();
		remove_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fee_1_5_to_cart' ) );

		$this->assertEquals( 70.86, WC()->cart->get_total( 'edit' ) );
	}

	/**
	 * Test that adding a variation with URL parameter increases the quantity appropriately
	 * as described in issue 24000.
	 */
	public function test_add_variation_by_url() {
		add_filter( 'woocommerce_add_to_cart_redirect', '__return_false' );
		update_option( 'woocommerce_cart_redirect_after_add', 'no' );
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();
		$variation  = current(
			array_filter(
				$variations,
				function( $variation ) {
					return 'DUMMY SKU VARIABLE HUGE RED 2' === $variation['sku'];
				}
			)
		);

		// Add variation with add_to_cart_action.
		$_REQUEST['add-to-cart'] = $variation['variation_id'];
		WC_Form_Handler::add_to_cart_action( false );
		$notices = WC()->session->get( 'wc_notices', array() );

		// Reset filter / REQUEST variables.
		unset( $_REQUEST['add-to-cart'] );
		remove_filter( 'woocommerce_add_to_cart_redirect', '__return_false' );

		// Check if the item is in the cart.
		$this->assertCount( 1, WC()->cart->get_cart_contents() );
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Check that there are no error notices.
		$this->assertArrayNotHasKey( 'error', $notices );

		// Add variation using parent id.
		WC()->cart->add_to_cart(
			$product->get_id(),
			1,
			$variation['variation_id'],
			array(
				'attribute_pa_size'   => 'huge',
				'attribute_pa_colour' => 'red',
				'attribute_pa_number' => '2',
			)
		);
		$notices = WC()->session->get( 'wc_notices', array() );

		// Check that the second add to cart call increases the quantity of the existing cart-item.
		$this->assertCount( 1, WC()->cart->get_cart_contents() );
		$this->assertEquals( 2, WC()->cart->get_cart_contents_count() );

		// Check that there are no error notices.
		$this->assertArrayNotHasKey( 'error', $notices );
	}

	/**
	 * Test that adding a variation via URL parameter fails when specifying a value for the attribute
	 * that differs from a value belonging to that variant.
	 */
	public function test_add_variation_by_url_with_invalid_attribute() {
		add_filter( 'woocommerce_add_to_cart_redirect', '__return_false' );
		update_option( 'woocommerce_cart_redirect_after_add', 'no' );
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();
		$variation  = array_pop( $variations );

		// Attempt adding variation with add_to_cart_action, specifying a different colour.
		$_REQUEST['add-to-cart']         = $variation['variation_id'];
		$_REQUEST['attribute_pa_colour'] = 'green';
		WC_Form_Handler::add_to_cart_action( false );
		$notices = WC()->session->get( 'wc_notices', array() );

		// Reset filter / REQUEST variables.
		unset( $_REQUEST['add-to-cart'] );
		unset( $_REQUEST['attribute_pa_colour'] );
		remove_filter( 'woocommerce_add_to_cart_redirect', '__return_false' );

		// Check that the notices contain an error message about an invalid colour.
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$this->assertEquals( 'Invalid value posted for colour', $notices['error'][0]['notice'] );
	}

	/**
	 * Test that adding a variation via URL parameter succeeds when some attributes belong to the
	 * variation and others are specificed via URL parameter.
	 */
	public function test_add_variation_by_url_with_valid_attribute() {
		add_filter( 'woocommerce_add_to_cart_redirect', '__return_false' );
		update_option( 'woocommerce_cart_redirect_after_add', 'no' );
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();
		$variation  = array_shift( $variations );

		// Attempt adding variation with add_to_cart_action, specifying attributes not defined in the variation.
		$_REQUEST['add-to-cart']         = $variation['variation_id'];
		$_REQUEST['attribute_pa_colour'] = 'red';
		$_REQUEST['attribute_pa_number'] = '1';
		WC_Form_Handler::add_to_cart_action( false );
		$notices = WC()->session->get( 'wc_notices', array() );

		// Reset filter / REQUEST variables.
		unset( $_REQUEST['add-to-cart'] );
		unset( $_REQUEST['attribute_pa_colour'] );
		unset( $_REQUEST['attribute_pa_number'] );
		remove_filter( 'woocommerce_add_to_cart_redirect', '__return_false' );

		// Check if the item is in the cart.
		$this->assertCount( 1, WC()->cart->get_cart_contents() );
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Check that there are no error notices.
		$this->assertArrayNotHasKey( 'error', $notices );
	}

	/**
	 * Test that adding a varition via URL parameter fails when an 'any' attribute is missing.
	 */
	public function test_add_variation_by_url_fails_with_missing_any_attribute() {
		add_filter( 'woocommerce_add_to_cart_redirect', '__return_false' );
		update_option( 'woocommerce_cart_redirect_after_add', 'no' );
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();
		$variation  = array_shift( $variations );

		// Attempt adding variation with add_to_cart_action, without specifying attribute_pa_colour.
		$_REQUEST['add-to-cart']         = $variation['variation_id'];
		$_REQUEST['attribute_pa_number'] = '';
		WC_Form_Handler::add_to_cart_action( false );
		$notices = WC()->session->get( 'wc_notices', array() );

		// Reset filter / REQUEST variables.
		unset( $_REQUEST['add-to-cart'] );
		unset( $_REQUEST['attribute_pa_number'] );
		remove_filter( 'woocommerce_add_to_cart_redirect', '__return_false' );

		// Verify that there is nothing in the cart.
		$this->assertCount( 0, WC()->cart->get_cart_contents() );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Check that the notices contain an error message about invalid colour and number.
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$this->assertEquals( 'colour and number are required fields', $notices['error'][0]['notice'] );
	}

	/**
	 * Helper function. Adds 1.5 taxable fees to cart.
	 */
	public function add_fee_1_5_to_cart() {
		WC()->cart->add_fee( 'Dummy fee', 1.5 / 1.1, true );
	}

	/**
	 * Change tax class.
	 *
	 * @return string
	 */
	public function change_tax_class_filter() {
		return 'reduced-rate';
	}
}
