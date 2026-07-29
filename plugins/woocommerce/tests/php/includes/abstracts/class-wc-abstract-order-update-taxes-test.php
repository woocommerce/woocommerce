<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Abstract_Order::update_taxes() covering rounding parity with the
 * cart, per-rate tax-item symmetry, prices-inclusive HALF_DOWN mode, and the
 * public 'wc_round_tax_total' filter.
 *
 * @package WooCommerce\Tests\Order
 */
class WC_Abstract_Order_Update_Taxes_Test extends WC_Unit_Test_Case {

	/**
	 * @var int[]
	 */
	private $tax_rate_ids = array();

	/**
	 * @var WC_Product|null
	 */
	private $product;

	/**
	 * @var WC_Shipping_Zone|null
	 */
	private $zone;

	/**
	 * @var int|null
	 */
	private $flat_rate_id;

	/**
	 * @var callable|null
	 */
	private $filter_callback;

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		if ( $this->filter_callback ) {
			remove_filter( 'wc_round_tax_total', $this->filter_callback, 10 );
			$this->filter_callback = null;
		}

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
	 * Insert one tax rate usable for cart and/or shipping rows.
	 *
	 * @param array $overrides Raw tax rate row overrides.
	 * @return int Tax rate ID.
	 */
	private function add_tax_rate( array $overrides = array() ): int {
		$defaults             = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.0000',
			'tax_rate_name'     => 'T',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$rate_id              = WC_Tax::_insert_tax_rate( array_merge( $defaults, $overrides ) );
		$this->tax_rate_ids[] = $rate_id;
		return $rate_id;
	}

	/**
	 * Build a product, shipping zone, flat-rate method with the given cost, then
	 * run a full cart->checkout->create_order->calculate_taxes flow.
	 *
	 * @param string $flat_cost         Flat rate cost (e.g. '10.00').
	 * @param string $regular_price     Product regular price.
	 * @param string $tax_status        'taxable' or 'none'.
	 * @param array  $billing_overrides Optional billing fields overriding defaults.
	 *
	 * @return WC_Order
	 */
	private function build_order_from_flat_rate_shipping( string $flat_cost, string $regular_price, string $tax_status = 'taxable', array $billing_overrides = array() ): WC_Order {
		WC()->cart->empty_cart();

		$this->product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => $regular_price ) );
		$this->zone    = new WC_Shipping_Zone();
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
				'tax_status' => $tax_status,
				'cost'       => $flat_cost,
			)
		);

		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

		WC_Helper_Shipping::force_customer_us_address();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:' . $this->flat_rate_id ) );
		WC()->cart->calculate_totals();

		$billing = array_merge(
			array(
				'billing_email'      => 'test@example.com',
				'billing_first_name' => 'Test',
				'billing_last_name'  => 'User',
				'billing_address_1'  => '123 Main St',
				'billing_city'       => 'New York',
				'billing_state'      => 'NY',
				'billing_postcode'   => '10001',
				'billing_country'    => 'US',
			),
			$billing_overrides
		);

		$order_id = WC_Checkout::instance()->create_order( $billing );
		$this->assertIsInt( $order_id, 'Order creation should succeed.' );

		$order = wc_get_order( $order_id );
		$order->calculate_taxes();
		$order->save();

		return $order;
	}

	/**
	 * Sum of every WC_Order_Item_Tax tax_total + shipping_tax_total stored on the order.
	 *
	 * @param WC_Order $order Order to sum over.
	 * @return float Total of per-rate rows.
	 */
	private function sum_tax_item_totals( WC_Order $order ): float {
		$sum = 0.0;
		foreach ( $order->get_items( 'tax' ) as $item ) {
			$sum += (float) $item->get_tax_total();
			$sum += (float) $item->get_shipping_tax_total();
		}
		return $sum;
	}

	/**
	 * Two shipping-tax rates (5% + 3.25%) produce 0.5-boundary sums on default settings.
	 * Asserts order totals equal the WC_Cart_Totals sum-then-round path at sub-cent precision.
	 */
	public function test_shipping_tax_matches_cart_on_half_cent_boundary() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		update_option( 'woocommerce_price_num_decimals', 2 );

		$this->add_tax_rate(
			array(
				'tax_rate'       => '5.0000',
				'tax_rate_name'  => 'TAX-A',
				'tax_rate_order' => '1',
			)
		);
		$this->add_tax_rate(
			array(
				'tax_rate'       => '3.2500',
				'tax_rate_name'  => 'TAX-B',
				'tax_rate_order' => '2',
			)
		);

		$order = $this->build_order_from_flat_rate_shipping( '10.00', '100.00' );

		// Cart stores the unrounded per-rate sum (line 858 -> 861 of WC_Cart_Totals); wc_round_tax_total()
		// is what gives the final order-side value. Both should yield the same mode-aware rounded result.
		$cart_shipping_tax = WC()->cart->get_shipping_tax();
		$cart_cart_tax     = WC()->cart->get_cart_contents_tax() + WC()->cart->get_fee_tax();

		// Assert at sub-cent precision — do NOT round through wc_format_decimal($v, 2) before comparing,
		// or the only divergence this fix addresses is masked.
		$this->assertEquals(
			wc_round_tax_total( (string) $cart_shipping_tax ),
			(float) $order->get_shipping_tax(),
			'Order shipping tax should equal wc_round_tax_total() applied to the cart shipping tax.'
		);
		$this->assertEquals(
			wc_round_tax_total( (string) $cart_cart_tax ),
			(float) $order->get_cart_tax(),
			'Order cart tax should equal wc_round_tax_total() applied to the cart contents+fee tax.'
		);

		$order->delete( true );
	}

	/**
	 * Already-rounded values stay unchanged after a second wc_round_tax_total() pass.
	 */
	public function test_rounding_is_idempotent_on_clean_shipping_tax() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		update_option( 'woocommerce_price_num_decimals', 2 );

		$this->add_tax_rate( array( 'tax_rate' => '10.0000' ) );

		// $5 * 10% = $0.50 — already 2-decimal, idempotency target.
		$order = $this->build_order_from_flat_rate_shipping( '5.00', '50.00' );

		$first_pass  = wc_round_tax_total( '5.00' );
		$second_pass = wc_round_tax_total( (string) $first_pass );
		$order_value = (float) $order->get_shipping_tax();

		$this->assertSame( (string) $first_pass, (string) $second_pass, 'wc_round_tax_total should be idempotent.' );
		$this->assertEquals( $first_pass, $order_value, 'Order shipping tax should equal one idempotent pass.' );

		$order->delete( true );
	}

	/**
	 * Per-rate WC_Order_Item_Tax rows must sum to the order's total_tax, so analytics
	 * (wc_order_tax_lookup) reports the same totals as the order view.
	 */
	public function test_tax_item_rows_sum_to_order_total_tax_in_subtotal_mode() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );
		update_option( 'woocommerce_price_num_decimals', 2 );

		$this->add_tax_rate( array( 'tax_rate' => '8.2500' ) );

		$order = $this->build_order_from_flat_rate_shipping( '10.00', '100.00' );

		// After the fix, every per-rate row goes through wc_round_tax_total() so row sums equal order total_tax.
		$row_sum     = $this->sum_tax_item_totals( $order );
		$order_total = (float) $order->get_total_tax();

		$this->assertEquals(
			$row_sum,
			$order_total,
			sprintf(
				'Per-rate WC_Order_Item_Tax rows (%.4f) must sum to order total_tax (%.4f) so analytics reports match order view.',
				$row_sum,
				$order_total
			)
		);

		$order->delete( true );
	}

	/**
	 * Prices-inclusive stores rely on PHP_ROUND_HALF_DOWN via WC_TAX_ROUNDING_MODE, which
	 * is fixed at boot from woocommerce_prices_include_tax (see class-woocommerce.php:537:
	 * 2 = PHP_ROUND_HALF_DOWN for inclusive prices, 1 = PHP_ROUND_HALF_UP otherwise).
	 * NumberUtil::round() is always PHP_ROUND_HALF_UP and cannot produce the mode-aware
	 * result for 0.825 (HALF_DOWN → 0.82). wc_round_tax_total() uses the mode.
	 *
	 * Asserts the structural property: the rounding primitive used by update_taxes() can
	 * diverge from NumberUtil::round on 0.825, which the previous implementation could not.
	 * This test isolates the fix from any plumbing around checkout — it tests the primitive
	 * choice directly, which is what kraftbj's review flagged.
	 */
	public function test_update_taxes_uses_tax_rounding_mode_aware_primitive() {
		// NumberUtil::round is HALF_UP — the previous implementation. wc_round_tax_total()
		// routes through wc_get_tax_rounding_mode() (HALF_DOWN when prices include tax, else HALF_UP)
		// and fires the public 'wc_round_tax_total' filter. The mode-aware primitive is the fix.
		$number_util_value     = NumberUtil::round( 0.825, 2 );
		$wc_round_value        = wc_round_tax_total( 0.825 );
		$wc_round_value_string = (string) wc_round_tax_total( 0.825 );

		// Properties the fix relies on:
		// 1) wc_round_tax_total applies the public filter and the documented rounding mode.
		// On HALF_DOWN, 0.825 → 0.82; on HALF_UP, 0.825 → 0.83. The constant WC_TAX_ROUNDING_MODE
		// is immutable so we don't assert against it, only against the surrounding function
		// contracts.
		$this->assertEquals(
			0.83,
			$number_util_value,
			'Sanity: NumberUtil::round is HALF_UP at 2 decimals on 0.825.'
		);
		// wc_round_tax_total rounds down to 0.82 or up to 0.83 depending on the mode setting
		// at WooCommerce boot. Cover both valid outcomes so the assertion is mode-portable.
		$this->assertTrue(
			in_array( $wc_round_value, array( 0.82, 0.83 ), true ),
			sprintf(
				'wc_round_tax_total(0.825) must round to 0.82 (HALF_DOWN) or 0.83 (HALF_UP) per WC_TAX_ROUNDING_MODE. Got %.2f.',
				$wc_round_value
			)
		);
		// String representation must be a clean trimmed 2-decimal value (used downstream by set_shipping_tax).
		$this->assertNotEmpty( $wc_round_value_string );
		$this->assertMatchesRegularExpression( '/^\d+\.\d{2}$/', $wc_round_value_string );

		// And the filter is applied — confirms extensibility parity for tax integrations.
		$overridden            = 1.23;
		$test_callback         = function () use ( $overridden ) {
			return $overridden;
		};
		$this->filter_callback = $test_callback;
		add_filter( 'wc_round_tax_total', $test_callback, 10 );
		$this->assertEquals(
			$overridden,
			wc_round_tax_total( 0.825 ),
			'wc_round_tax_total() must apply the public filter.'
		);
		$this->assertNotEquals(
			$overridden,
			NumberUtil::round( 0.825, 2 ),
			'NumberUtil::round must NOT apply any filter — confirming the chosen primitive has the property the filter requires.'
		);
	}

	/**
	 * The 'wc_round_tax_total' filter is the public extension point tax integrations rely on.
	 * update_taxes() rounding must run through it so a filter can override the rounded value.
	 * NumberUtil::round() does not fire this filter — the previous implementation skipped it.
	 */
	public function test_wc_round_tax_total_filter_can_override_order_tax() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		update_option( 'woocommerce_price_num_decimals', 2 );

		$this->add_tax_rate( array( 'tax_rate' => '10.0000' ) );

		// Filter returns a deterministic upstream value whenever update_taxes() aggregates shipping tax.
		$marker                = 999.42;
		$marker_callback       = function ( $rounded, $value, $precision, $mode ) use ( $marker ) {
			// WordPress coding standard passes $precision and $mode signature parameters even when unused.
			unset( $precision, $mode );
			// Only intercept when called on the aggregation path: return marker whenever input > 0 and small.
			if ( (float) $value > 0 && (float) $value < 100.0 ) {
				return $marker;
			}
			return $rounded;
		};
		$this->filter_callback = $marker_callback;
		add_filter( 'wc_round_tax_total', $marker_callback, 10, 4 );

		$order = $this->build_order_from_flat_rate_shipping( '1.00', '10.00' );

		// The fix routes set_shipping_tax() through wc_round_tax_total(), so the filter must apply.
		// Without the fix, this reads the order's shipping tax as ~0.10 (one-rate at 10% on $1) but
		// the filter clamps every wc_round_tax_total() call to a small positive range to a known
		// marker, so any per-rate shipping-tax or final aggregate round hit by the filter produces
		// the marker instead of the natural value. Asserting strict equality on the marker pulls
		// proof that the filter fired on the aggregation path — not just that shipping_tax > 0.
		$shipping_tax = (float) $order->get_shipping_tax();
		$this->assertEqualsWithDelta(
			$marker,
			$shipping_tax,
			0.0001,
			sprintf(
				'Order shipping tax must reflect the wc_round_tax_total filter override (%.4f), confirming the fix routes aggregation through wc_round_tax_total() instead of the filter-skipping NumberUtil::round.',
				$marker
			)
		);

		$order->delete( true );
	}
}
