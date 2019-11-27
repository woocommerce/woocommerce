<?php
/**
 * Unit tests for the shipping class.
 *
 * @package WooCommerce\Tests\Shipping
 */

/**
 * WC_Tests_Shipping tests.
 *
 * @package WooCommerce\Tests\Shipping
 * @since 3.9.0
 */
class WC_Tests_Shipping extends WC_Unit_Test_Case {

	/**
	 * Tests that whether or not a package is shippable is evaluated correctly.
	 *
	 * @since 3.9.0
	 */
	public function test_is_package_shippable() {
		$shipping = new WC_Shipping();

		// Failure for no country.
		$result = $shipping->is_package_shippable(
			array(
				'destination' => array(
					'country'  => '',
					'state'    => 'CA',
					'postcode' => '99999',
					'address'  => '',
				),
			)
		);
		$this->assertFalse( $result );

		// Failure for disallowed country.
		$result = $shipping->is_package_shippable(
			array(
				'destination' => array(
					'country'  => 'TEST',
					'state'    => 'CA',
					'postcode' => '99999',
					'address'  => '',
				),
			)
		);
		$this->assertFalse( $result );

		// Failure for no state when required.
		$result = $shipping->is_package_shippable(
			array(
				'destination' => array(
					'country'  => 'US',
					'state'    => '',
					'postcode' => '99999',
					'address'  => '',
				),
			)
		);
		$this->assertFalse( $result );

		// Failure for invalid postcode.
		$result = $shipping->is_package_shippable(
			array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => 'test',
					'address'  => '',
				),
			)
		);
		$this->assertFalse( $result );

		// Success for correct address.
		$result = $shipping->is_package_shippable(
			array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '99999',
					'address'  => '',
				),
			)
		);
		$this->assertTrue( $result );
	}
}
