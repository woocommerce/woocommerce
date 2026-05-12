<?php

declare( strict_types = 1 );

/**
 * Class WC_Cache_Helper_Tests. Tests for WC_Cache_Helper class.
 */
class WC_Cache_Helper_Tests extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_cache_delete( 'wc_orders_cache_prefix', 'orders' );

		parent::tearDown();
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

	/**
	 * @testdox Get cache prefix should generate cache-safe prefixes for empty cache groups.
	 */
	public function test_get_cache_prefix_generates_cache_safe_prefix(): void {
		wp_cache_delete( 'wc_orders_cache_prefix', 'orders' );

		$prefix = WC_Cache_Helper::get_cache_prefix( 'orders' );

		$this->assert_cache_safe_prefix( $prefix );
	}

	/**
	 * @testdox Get cache prefix should replace stale whitespace-prefixed values.
	 */
	public function test_get_cache_prefix_replaces_stale_whitespace_prefix(): void {
		wp_cache_set( 'wc_orders_cache_prefix', '0.84069400 1778478731', 'orders' );

		$prefix = WC_Cache_Helper::get_cache_prefix( 'orders' );

		$this->assert_cache_safe_prefix( $prefix );
		$this->assertNotSame( 'wc_cache_0.84069400 1778478731_', $prefix );
	}

	/**
	 * @testdox Invalidate cache group should generate cache-safe prefixes.
	 */
	public function test_invalidate_cache_group_generates_cache_safe_prefix(): void {
		WC_Cache_Helper::invalidate_cache_group( 'orders' );

		$prefix = WC_Cache_Helper::get_cache_prefix( 'orders' );

		$this->assert_cache_safe_prefix( $prefix );
	}

	/**
	 * @testdox Get cache prefix should recover when a cached prefix is not stringable.
	 */
	public function test_get_cache_prefix_recovers_non_stringable_cached_prefix(): void {
		wp_cache_set( 'wc_orders_cache_prefix', (object) array( 'invalid' => true ), 'orders' );

		$cache_key = WC_Order::generate_meta_cache_key( 123, 'orders' );

		$this->assertStringStartsWith( 'wc_cache_', $cache_key );
		$this->assertStringContainsString( 'object_meta_123', $cache_key );
	}

	/**
	 * Assert that a generated cache prefix is safe to use in cache keys.
	 *
	 * @param string $prefix Cache prefix.
	 */
	private function assert_cache_safe_prefix( string $prefix ): void {
		$this->assertStringStartsWith( 'wc_cache_', $prefix );
		$this->assertStringEndsWith( '_', $prefix );
		$this->assertNotSame( 'wc_cache__', $prefix );
		$this->assertSame( 0, preg_match( '/\s/', $prefix ), 'Cache prefix should not contain whitespace.' );
	}
}
