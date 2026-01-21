<?php
declare( strict_types = 1 );

/**
 * Class WC_Cart_Shipping_Rounding_Test
 *
 * Regression test for Issue #62692:
 * Order total off by $0.01 when combining percentage-based shipping with tax.
 *
 * @package WooCommerce\Tests\Cart
 * @link https://github.com/woocommerce/woocommerce/issues/62692
 */
class WC_Cart_Shipping_Rounding_Test extends WC_Unit_Test_Case {

	/**
	 * Tax rate ID created during test.
	 *
	 * @var int
	 */
	private $tax_rate_id;

	/**
	 * Product created during test.
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 * Shipping zone created during test.
	 *
	 * @var WC_Shipping_Zone
	 */
	private $zone;

	/**
	 * Flat rate instance ID.
	 *
	 * @var int
	 */
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

		if ( $this->tax_rate_id ) {
			WC_Tax::_delete_tax_rate( $this->tax_rate_id );
		}

		if ( $this->product ) {
			WC_Helper_Product::delete_product( $this->product->get_id() );
		}

		// Clear shipping caches.
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Tests that percentage-based shipping with tax does not cause $0.01 rounding error.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/62692
	 */
	public function test_percentage_shipping_with_tax_rounding_issue_62692() {
		// 1. Configure WooCommerce Settings.
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' ); // Key trigger.
		update_option( 'woocommerce_price_num_decimals', 2 );

		WC()->cart->empty_cart();

		// 2. Create Tax Rate (8.25% on Items Only).
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '8.2500',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0', // Tax does NOT apply to shipping.
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		// Insert the tax rate into the database.
		$this->tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		// 3. Create Product ($110.50).
		$this->product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => '110.50',
			)
		);

		// 4. Create Shipping Zone with Flat Rate Method (15% fee).
		// Using WC_Shipping_Zone ensures the code path goes through:
		// calculate_totals() -> WC_Shipping_Zone -> WC_Shipping_Flat_Rate::evaluate_cost() -> fee().
		$this->zone = new WC_Shipping_Zone();
		$this->zone->set_zone_name( 'Test Zone' );
		$this->zone->set_zone_order( 1 );
		$this->zone->save();

		// Add US location to match force_customer_us_address().
		$this->zone->add_location( 'US', 'country' );
		$this->zone->save();

		// Add flat rate method to zone.
		$this->flat_rate_id = $this->zone->add_shipping_method( 'flat_rate' );

		// Configure the flat rate instance with percentage cost.
		update_option(
			'woocommerce_flat_rate_' . $this->flat_rate_id . '_settings',
			array(
				'enabled'    => 'yes',
				'title'      => 'Flat rate',
				'tax_status' => 'none', // Shipping is not taxable.
				'cost'       => '[fee percent="15"]',
			)
		);

		// Clear shipping cache and reload methods.
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

		// 5. Simulate Cart Interaction.
		WC_Helper_Shipping::force_customer_us_address();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:' . $this->flat_rate_id ) );
		WC()->cart->calculate_totals();

		// 6. Assertions.
		// Expected calculation:
		// Subtotal: $110.50
		// Shipping (15%): $110.50 * 0.15 = $16.575 → rounded to $16.58.
		// Tax (8.25% on items only): $110.50 * 0.0825 = $9.11625 → rounded to $9.12.
		// Total: $110.50 + $16.58 + $9.12 = $136.20.

		$this->assertEquals(
			'110.50',
			wc_format_decimal( WC()->cart->get_subtotal(), 2 ),
			'Subtotal should be $110.50.'
		);

		$this->assertEquals(
			'16.58',
			wc_format_decimal( WC()->cart->get_shipping_total(), 2 ),
			'Shipping should be $16.58 (15% of $110.50 = $16.575, rounded).'
		);

		$this->assertEquals(
			'9.12',
			wc_format_decimal( WC()->cart->get_total_tax(), 2 ),
			'Tax should be $9.12.'
		);

		// THE KEY ASSERTION.
		// Bug behavior: Returns $136.19.
		// Fixed behavior: Returns $136.20.
		$this->assertEquals(
			'136.20',
			wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ),
			'Total should be $136.20. If $136.19, floating-point precision loss occurred in shipping fee calculation.'
		);
	}
}
