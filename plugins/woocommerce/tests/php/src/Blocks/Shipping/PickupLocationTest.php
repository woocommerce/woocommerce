<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Shipping;

use Automattic\WooCommerce\Blocks\Shipping\PickupLocation;
use WC_Unit_Test_Case;

/**
 * Tests for the PickupLocation shipping method.
 */
class PickupLocationTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should take the tax out of the entered cost only when the cost is entered with tax.
	 * @testWith ["yes", 4.596774, 1.103226]
	 *           ["no", 5.70, 1.368]
	 *
	 * @param string $prices_include_tax Stored setting value.
	 * @param float  $expected_cost      Expected net cost on the rate.
	 * @param float  $expected_tax       Expected tax on the rate.
	 */
	public function test_rates_honour_prices_include_tax( string $prices_include_tax, float $expected_cost, float $expected_tax ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_shipping_tax_class', '' );
		\WC_Tax::_insert_tax_rate( array( 'tax_rate' => '24.0000' ) );
		\WC_Helper_Shipping::force_customer_us_address();
		update_option(
			'pickup_location_pickup_locations',
			array(
				array(
					'name'    => 'Downtown',
					'enabled' => true,
					'details' => '',
					'address' => array(),
				),
			)
		);
		update_option(
			'woocommerce_pickup_location_settings',
			array(
				'enabled'            => 'yes',
				'tax_status'         => 'taxable',
				'cost'               => '5.70',
				'prices_include_tax' => $prices_include_tax,
			)
		);

		$sut = new PickupLocation();
		$sut->calculate_shipping();
		$rate = reset( $sut->rates );

		$this->assertEqualsWithDelta( $expected_cost, (float) $rate->get_cost(), 0.000001, 'The stored cost should be the net' );
		$this->assertEqualsWithDelta( $expected_tax, array_sum( $rate->get_taxes() ), 0.000001, 'The tax should match the setting' );
	}
}
