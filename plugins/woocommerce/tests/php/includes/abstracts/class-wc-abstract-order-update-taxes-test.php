<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Abstract_Order::update_taxes() to ensure cart and order tax totals match.
 *
 * @package WooCommerce\Tests\Order
 */
class WC_Abstract_Order_Update_Taxes_Test extends WC_Unit_Test_Case {

	private $tax_rate_ids = array();
	private $product;
	private $zone;
	private $flat_rate_id;

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->cart->empty_cart();

		if ( $this->zone ) {
			$this->zone->delete();
		}

		if ( $this->flat_rate_id ) {
			delete_option( 'woocommerce_flat_rate_' . $this->flat_rate_id . '_settings' );
		}

		foreach ( $this->tax_rate_ids as $tax_rate_id ) {
			WC_Tax::_delete_tax_rate( $tax_rate_id );
		}

		if ( $this->product ) {
			WC_Helper_Product::delete_product( $this->product->get_id() );
		}

		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Test that order tax matches cart tax when per-rate sums land on 0.5 boundary.
	 *
	 * Reproduces issue #65623: tax correct at checkout but increases after order placed.
	 * Root cause: cart does sum-then-round, order did round-then-sum (missing final round).
	 */
	public function test_order_tax_matches_cart_tax_on_half_cent_boundary() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' ); // Default: round per-line.
		update_option( 'woocommerce_price_num_decimals', 2 );

		WC()->cart->empty_cart();

		// Two tax rates that produce 0.5-boundary sum when applied to shipping.
		$tax_rate_1 = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.0000',
			'tax_rate_name'     => 'TAX1',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1', // Apply to shipping.
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$tax_rate_2 = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '3.2500',
			'tax_rate_name'     => 'TAX2',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1', // Apply to shipping.
			'tax_rate_order'    => '2',
			'tax_rate_class'    => '',
		);

		$this->tax_rate_ids[] = WC_Tax::_insert_tax_rate( $tax_rate_1 );
		$this->tax_rate_ids[] = WC_Tax::_insert_tax_rate( $tax_rate_2 );

		$this->product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => '100.00',
			)
		);

		$this->zone = new WC_Shipping_Zone();
		$this->zone->set_zone_name( 'Test Zone' );
		$this->zone->set_zone_order( 1 );
		$this->zone->save();

		$this->zone->add_location( 'US', 'country' );
		$this->zone->save();

		$this->flat_rate_id = $this->zone->add_shipping_method( 'flat_rate' );

		// Shipping cost chosen to produce 0.5-boundary tax: $10.00 * 5% = $0.50, $10.00 * 3.25% = $0.325.
		// Sum: $0.50 + $0.325 = $0.825 → rounds to $0.83 (if sum-then-round) or $0.82 (if round-then-sum with 0.50 + 0.32).
		update_option(
			'woocommerce_flat_rate_' . $this->flat_rate_id . '_settings',
			array(
				'enabled'    => 'yes',
				'title'      => 'Flat rate',
				'tax_status' => 'taxable',
				'cost'       => '10.00',
			)
		);

		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

		WC_Helper_Shipping::force_customer_us_address();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:' . $this->flat_rate_id ) );
		WC()->cart->calculate_totals();

		$cart_shipping_tax = WC()->cart->get_shipping_tax();
		$cart_cart_tax     = WC()->cart->get_cart_contents_tax() + WC()->cart->get_fee_tax();

		// Create order via checkout (mimics real flow).
		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order(
			array(
				'billing_email'      => 'test@example.com',
				'billing_first_name' => 'Test',
				'billing_last_name'  => 'User',
				'billing_address_1'  => '123 Main St',
				'billing_city'       => 'New York',
				'billing_state'      => 'NY',
				'billing_postcode'   => '10001',
				'billing_country'    => 'US',
			)
		);

		$this->assertIsInt( $order_id, 'Order creation should succeed.' );

		$order = wc_get_order( $order_id );

		// Trigger update_taxes() as WC_Order::save() does.
		$order->calculate_taxes();
		$order->save();

		$order_shipping_tax = $order->get_shipping_tax();
		$order_cart_tax     = $order->get_cart_tax();

		$this->assertEquals(
			wc_format_decimal( $cart_shipping_tax, 2 ),
			wc_format_decimal( $order_shipping_tax, 2 ),
			'Order shipping tax should match cart shipping tax (issue #65623 regression check).'
		);

		$this->assertEquals(
			wc_format_decimal( $cart_cart_tax, 2 ),
			wc_format_decimal( $order_cart_tax, 2 ),
			'Order cart tax should match cart contents+fee tax.'
		);

		// Clean up order.
		$order->delete( true );
	}

	/**
	 * Test that rounding is idempotent (already-rounded values stay unchanged).
	 */
	public function test_final_rounding_is_idempotent() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		update_option( 'woocommerce_price_num_decimals', 2 );

		WC()->cart->empty_cart();

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

		$this->tax_rate_ids[] = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => '50.00',
			)
		);

		$this->zone = new WC_Shipping_Zone();
		$this->zone->set_zone_name( 'Test Zone' );
		$this->zone->set_zone_order( 1 );
		$this->zone->save();

		$this->zone->add_location( 'US', 'country' );
		$this->zone->save();

		$this->flat_rate_id = $this->zone->add_shipping_method( 'flat_rate' );

		update_option(
			'woocommerce_flat_rate_' . $this->flat_rate_id . '_settings',
			array(
				'enabled'    => 'yes',
				'title'      => 'Flat rate',
				'tax_status' => 'taxable',
				'cost'       => '5.00',
			)
		);

		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

		WC_Helper_Shipping::force_customer_us_address();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:' . $this->flat_rate_id ) );
		WC()->cart->calculate_totals();

		$cart_shipping_tax = WC()->cart->get_shipping_tax(); // 5.00 * 10% = 0.50 (already 2-decimal).

		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order(
			array(
				'billing_email'      => 'test@example.com',
				'billing_first_name' => 'Test',
				'billing_last_name'  => 'User',
				'billing_address_1'  => '123 Main St',
				'billing_city'       => 'New York',
				'billing_state'      => 'NY',
				'billing_postcode'   => '10001',
				'billing_country'    => 'US',
			)
		);

		$this->assertIsInt( $order_id, 'Order creation should succeed.' );


		$order = wc_get_order( $order_id );
		$order->calculate_taxes();
		$order->save();

		$order_shipping_tax = $order->get_shipping_tax();

		$this->assertEquals(
			wc_format_decimal( $cart_shipping_tax, 2 ),
			wc_format_decimal( $order_shipping_tax, 2 ),
			'Idempotent rounding: already-rounded 2-decimal value should stay unchanged after second rounding.'
		);

		$order->delete( true );
	}

	/**
	 * Test with woocommerce_tax_round_at_subtotal = yes (both paths skip per-line rounding).
	 */
	public function test_round_at_subtotal_mode_no_drift() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' ); // Both paths skip per-line rounding.
		update_option( 'woocommerce_price_num_decimals', 2 );

		WC()->cart->empty_cart();

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

		$this->tax_rate_ids[] = WC_Tax::_insert_tax_rate( $tax_rate );

		$this->product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => '100.00',
			)
		);

		$this->zone = new WC_Shipping_Zone();
		$this->zone->set_zone_name( 'Test Zone' );
		$this->zone->set_zone_order( 1 );
		$this->zone->save();

		$this->zone->add_location( 'US', 'country' );
		$this->zone->save();

		$this->flat_rate_id = $this->zone->add_shipping_method( 'flat_rate' );

		update_option(
			'woocommerce_flat_rate_' . $this->flat_rate_id . '_settings',
			array(
				'enabled'    => 'yes',
				'title'      => 'Flat rate',
				'tax_status' => 'taxable',
				'cost'       => '10.00',
			)
		);

		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

		WC_Helper_Shipping::force_customer_us_address();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:' . $this->flat_rate_id ) );
		WC()->cart->calculate_totals();

		$cart_shipping_tax = WC()->cart->get_shipping_tax();
		$cart_cart_tax     = WC()->cart->get_cart_contents_tax() + WC()->cart->get_fee_tax();

		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order(
			array(
				'billing_email'      => 'test@example.com',
				'billing_first_name' => 'Test',
				'billing_last_name'  => 'User',
				'billing_address_1'  => '123 Main St',
				'billing_city'       => 'New York',
				'billing_state'      => 'NY',
				'billing_postcode'   => '10001',
				'billing_country'    => 'US',
			)
		);

		$this->assertIsInt( $order_id, 'Order creation should succeed.' );


		$order = wc_get_order( $order_id );
		$order->calculate_taxes();
		$order->save();

		$this->assertEquals(
			wc_format_decimal( $cart_shipping_tax, 2 ),
			wc_format_decimal( $order->get_shipping_tax(), 2 ),
			'round_at_subtotal mode: cart and order shipping tax should match (no per-line rounding drift).'
		);

		$this->assertEquals(
			wc_format_decimal( $cart_cart_tax, 2 ),
			wc_format_decimal( $order->get_cart_tax(), 2 ),
			'round_at_subtotal mode: cart and order cart tax should match.'
		);

		$order->delete( true );
	}
}
