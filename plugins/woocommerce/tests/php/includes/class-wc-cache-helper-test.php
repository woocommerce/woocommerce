<?php

declare( strict_types = 1 );

/**
 * Class WC_Cache_Helper_Tests. Tests for WC_Cache_Helper class.
 */
class WC_Cache_Helper_Tests extends WC_Unit_Test_Case {

	/**
	 * @testdox Should refresh transient versions within the same second.
	 */
	public function test_get_transient_version_refreshes_within_same_second(): void {
		$group          = 'test-cache-helper-version';
		$transient_name = $group . '-transient-version';

		delete_transient( $transient_name );

		$current_time = microtime( true );
		while ( 0.1 < $current_time - floor( $current_time ) ) {
			usleep( 1000 );
			$current_time = microtime( true );
		}

		$first_version  = WC_Cache_Helper::get_transient_version( $group, true );
		usleep( 1000 );
		$second_version = WC_Cache_Helper::get_transient_version( $group, true );

		delete_transient( $transient_name );

		$this->assertNotSame(
			$first_version,
			$second_version,
			'Refreshing a transient version should always create a new cache version.'
		);
	}

	/**
	 * Data provider for test_geolocation_ajax_get_location_hash.
	 *
	 * @return array[]
	 */
	public function data_provider_test_geolocation_ajax_get_location_hash(): array {
		return array(
			array(
				'393fc03f1382',
				array(
					'country'  => 'GB',
					'state'    => 'Greater London',
					'postcode' => 'NW1 8QL',
					'city'     => 'London',
				),
			),
			array(
				'393fc03f1382',
				array(
					'country'  => 'GB',
					'state'    => 'greater london',
					'postcode' => 'NW1 8QL',
					'city'     => 'london',
				),
			),
			array(
				'87b6bacfb240',
				array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '90210',
					'city'     => 'Beverly Hills',
				),
			),
			array(
				'edd7a1221c2e',
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
		WC()->session->set( 'customer', null );
		update_option( 'woocommerce_default_country', $location['country'] );

		$session = new WC_Customer( 0, true );
		$session->set_billing_location( $location['country'], $location['state'], $location['postcode'], $location['city'] );
		$session->save();

		$this->assertSame(
			$expected,
			WC_Cache_Helper::geolocation_ajax_get_location_hash()
		);
	}
}
