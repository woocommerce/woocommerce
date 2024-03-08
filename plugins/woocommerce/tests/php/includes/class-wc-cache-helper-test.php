<?php

/**
 * Class WC_Cache_Helper_Tests. Tests for WC_Cache_Helper class.
 */
class WC_Cache_Helper_Tests extends WC_Unit_Test_Case {

	/**
	 * Data provider for test_geolocation_ajax_get_location_hash.
	 *
	 * @return array[]
	 */
	public function data_provider_test_geolocation_ajax_get_location_hash(): array {
		return array(
			array(
				'e6c92cbd0e81',
				array(
					'country'  => 'GB',
					'state'    => 'Greater London',
					'postcode' => 'NW1 8QL',
					'city'     => 'London',
				),
			),
			array(
				'2a20d6f979cd',
				array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '90210',
					'city'     => 'Beverly Hills',
				),
			),
			array(
				'd5655d9bc4ca',
				array(
					'country'  => 'FI',
					'state'    => '',
					'postcode' => '00100',
					'city'     => 'Helsinki',
				),
			),
		);
	}

	/**
	 * Tests whether geolocation_ajax_get_location_hash returns expected hash.
	 *
	 * @dataProvider data_provider_test_geolocation_ajax_get_location_hash
	 *
	 * @param string $expected Expected outcome.
	 * @param array  $location Location data to test.
	 */
	public function test_geolocation_ajax_get_location_hash( string $expected, array $location ) {
		$session = new WC_Customer( 0, true );
		$session->set_billing_country( $location['country'] );
		$session->set_billing_state( $location['state'] );
		$session->set_billing_postcode( $location['postcode'] );
		$session->set_billing_city( $location['city'] );
		$session->save();

		$this->assertSame(
			$expected,
			WC_Cache_Helper::geolocation_ajax_get_location_hash()
		);
	}
}
