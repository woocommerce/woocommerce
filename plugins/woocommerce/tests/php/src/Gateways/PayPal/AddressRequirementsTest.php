<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

use Automattic\WooCommerce\Gateways\PayPal\AddressRequirements as PayPalAddressRequirements;

/**
 * Tests for the AddressRequirements helper class.
 */
class AddressRequirementsTest extends \WC_Unit_Test_Case {
	/**
	 * Tests for `country_requires_city`.
	 *
	 * @param string $country  ISO 3166-1 alpha-2 country code.
	 * @param bool   $expected Expected result.
	 * @return void
	 *
	 * @dataProvider provide_test_country_requires_city
	 */
	public function test_country_requires_city( $country, $expected ) {
		$address_requirements = wc_get_container()->get( PayPalAddressRequirements::class )::instance();
		$this->assertSame( $expected, $address_requirements->country_requires_city( $country ) );
	}

	/**
	 * Data provider for `test_country_requires_city`.
	 *
	 * @return array[]
	 */
	public function provide_test_country_requires_city() {
		return array(
			'empty'            => array(
				'country'  => '',
				'expected' => false,
			),
			'invalid'          => array(
				'country'  => 'XX',
				'expected' => false,
			),
			'does not require' => array(
				'country'  => 'CW',
				'expected' => false,
			),
			'requires'         => array(
				'country'  => 'US',
				'expected' => true,
			),
		);
	}

	/**
	 * Tests for `country_requires_postal_code`.
	 *
	 * @param string $country  ISO 3166-1 alpha-2 country code.
	 * @param bool   $expected Expected result.
	 * @return void
	 *
	 * @dataProvider provide_test_country_requires_postal_code
	 */
	public function test_country_requires_postal_code( $country, $expected ) {
		$address_requirements = wc_get_container()->get( PayPalAddressRequirements::class )::instance();
		$this->assertSame( $expected, $address_requirements->country_requires_postal_code( $country ) );
	}

	/**
	 * Data provider for `test_country_requires_postal_code`.
	 *
	 * @return array[]
	 */
	public function provide_test_country_requires_postal_code() {
		return array(
			'empty'            => array(
				'country'  => '',
				'expected' => false,
			),
			'invalid'          => array(
				'country'  => 'XX',
				'expected' => false,
			),
			'does not require' => array(
				'country'  => 'IE',
				'expected' => false,
			),
			'requires'         => array(
				'country'  => 'US',
				'expected' => true,
			),
		);
	}
}
