<?php

declare( strict_types = 1 );

/**
 * Class WC_Cache_Helper_Tests. Tests for WC_Cache_Helper class.
 */
class WC_Cache_Helper_Tests extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->delete_orders_cache_prefixes();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->delete_orders_cache_prefixes();

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
	 * @testdox Get cache prefix should generate string prefixes for empty cache groups.
	 */
	public function test_get_cache_prefix_generates_string_prefix(): void {
		$prefix = WC_Cache_Helper::get_cache_prefix( 'orders' );

		$this->assert_prefixed_cache_key_namespace( $prefix );
	}

	/**
	 * @testdox Get cache prefix should store string prefixes.
	 */
	public function test_get_cache_prefix_stores_string_prefix(): void {
		WC_Cache_Helper::get_cache_prefix( 'orders' );

		$stored_prefix = wp_cache_get( 'wc_orders_cache_prefix', 'orders' );

		$this->assert_valid_stored_prefix( $stored_prefix );
	}

	/**
	 * Data provider for invalid cache prefix values.
	 *
	 * @return array<string,array{0:mixed}>
	 */
	public function data_provider_invalid_cache_prefixes(): array {
		return array(
			'null'              => array( null ),
			'array'             => array( array( 'invalid' => true ) ),
			'true'              => array( true ),
			'false'             => array( false ),
			'integer'           => array( 123 ),
			'float'             => array( 123.45 ),
			'stdClass'          => array( (object) array( 'invalid' => true ) ),
			'stringable object' => array(
				new class() {
					/**
					 * Convert the object to a string.
					 *
					 * @return string
					 */
					public function __toString() {
						return '0.12345600_1778592656_deadbeefdeadbeef';
					}
				},
			),
			'empty string'      => array( '' ),
			'spaces'            => array( '   ' ),
			'tab'               => array( "\t" ),
			'newline'           => array( "\n" ),
			'null byte'         => array( "\0" ),
		);
	}

	/**
	 * @testdox Get cache prefix should replace invalid cached values.
	 *
	 * @dataProvider data_provider_invalid_cache_prefixes
	 *
	 * @param mixed $invalid_prefix Invalid cache prefix.
	 */
	public function test_get_cache_prefix_replaces_invalid_cached_prefixes( $invalid_prefix ): void {
		wp_cache_set( 'wc_orders_cache_prefix', $invalid_prefix, 'orders' );

		$prefix        = WC_Cache_Helper::get_cache_prefix( 'orders' );
		$stored_prefix = wp_cache_get( 'wc_orders_cache_prefix', 'orders' );

		$this->assert_prefixed_cache_key_namespace( $prefix );
		$this->assert_valid_stored_prefix( $stored_prefix );
		$this->assertNotSame( $invalid_prefix, $stored_prefix );
	}

	/**
	 * @testdox Get cache prefix should fire an action when replacing an invalid cached prefix.
	 */
	public function test_get_cache_prefix_fires_action_when_replacing_invalid_cached_prefix(): void {
		$invalid_prefix = array( 'invalid' => true );
		$detected       = array();
		$callback       = function ( $group, $prefix ) use ( &$detected ) {
			$detected[] = array(
				'group'  => $group,
				'prefix' => $prefix,
			);
		};

		wp_cache_set( 'wc_orders_cache_prefix', $invalid_prefix, 'orders' );
		add_action( 'woocommerce_invalid_cache_prefix_detected', $callback, 10, 2 );

		try {
			WC_Cache_Helper::get_cache_prefix( 'orders' );
		} finally {
			remove_action( 'woocommerce_invalid_cache_prefix_detected', $callback, 10 );
		}

		$this->assertCount( 1, $detected );
		$this->assertSame( 'orders', $detected[0]['group'] );
		$this->assertSame( $invalid_prefix, $detected[0]['prefix'] );
	}

	/**
	 * Data provider for valid cache prefix values.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function data_provider_valid_cache_prefixes(): array {
		return array(
			'generated format'    => array( '0.12345600_1778592656_deadbeefdeadbeef' ),
			'old microtime'       => array( '0.84069400 1778478731' ),
			'plain string'        => array( 'extension-prefix' ),
			'string with spaces'  => array( 'extension prefix' ),
			'numeric string zero' => array( '0' ),
		);
	}

	/**
	 * @testdox Get cache prefix should reuse valid cached values.
	 *
	 * @dataProvider data_provider_valid_cache_prefixes
	 *
	 * @param string $stored_prefix Stored cache prefix.
	 */
	public function test_get_cache_prefix_reuses_valid_cached_prefix( string $stored_prefix ): void {
		wp_cache_set( 'wc_orders_cache_prefix', $stored_prefix, 'orders' );

		$prefix = WC_Cache_Helper::get_cache_prefix( 'orders' );

		$this->assertSame( 'wc_cache_' . $stored_prefix . '_', $prefix );
		$this->assertSame( $stored_prefix, wp_cache_get( 'wc_orders_cache_prefix', 'orders' ) );
	}

	/**
	 * @testdox Invalidate cache group should generate string prefixes.
	 */
	public function test_invalidate_cache_group_generates_string_prefix(): void {
		WC_Cache_Helper::invalidate_cache_group( 'orders' );

		$prefix        = WC_Cache_Helper::get_cache_prefix( 'orders' );
		$stored_prefix = wp_cache_get( 'wc_orders_cache_prefix', 'orders' );

		$this->assert_prefixed_cache_key_namespace( $prefix );
		$this->assert_valid_stored_prefix( $stored_prefix );
	}

	/**
	 * @testdox Get cache prefix should recover when a cached prefix is not stringable.
	 */
	public function test_get_cache_prefix_recovers_non_stringable_cached_prefix(): void {
		wp_cache_set( 'wc_orders_cache_prefix', (object) array( 'invalid' => true ), 'orders' );

		$cache_key     = WC_Order::generate_meta_cache_key( 123, 'orders' );
		$stored_prefix = wp_cache_get( 'wc_orders_cache_prefix', 'orders' );

		$this->assertStringStartsWith( 'wc_cache_', $cache_key );
		$this->assertStringContainsString( 'object_meta_123', $cache_key );
		$this->assert_valid_stored_prefix( $stored_prefix );
	}

	/**
	 * Assert that a namespaced cache key contains a valid prefix.
	 *
	 * @param string $prefix Namespaced cache key prefix.
	 */
	private function assert_prefixed_cache_key_namespace( string $prefix ): void {
		$this->assertStringStartsWith( 'wc_cache_', $prefix );
		$this->assertStringEndsWith( '_', $prefix );
		$this->assert_valid_stored_prefix( substr( $prefix, strlen( 'wc_cache_' ), -1 ) );
	}

	/**
	 * Assert that a stored cache prefix can be used as a cache-key namespace.
	 *
	 * @param mixed $prefix Stored cache prefix.
	 */
	private function assert_valid_stored_prefix( $prefix ): void {
		$this->assertIsString( $prefix );
		$this->assertNotSame( '', trim( $prefix ) );
	}

	/**
	 * Delete cache prefix fixtures.
	 */
	private function delete_orders_cache_prefixes(): void {
		wp_cache_delete( 'wc_orders_cache_prefix', 'orders' );
	}
}
