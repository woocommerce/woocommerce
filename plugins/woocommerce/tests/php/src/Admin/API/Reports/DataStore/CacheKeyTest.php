<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\DataStore;

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use WC_Unit_Test_Case;

/**
 * Tests for the base Reports DataStore cache key generation.
 */
class CacheKeyTest extends WC_Unit_Test_Case {

	/**
	 * @testdox get_cache_key produces identical keys for DateTime objects representing the same second.
	 */
	public function test_cache_key_is_stable_across_datetime_objects_with_same_second(): void {
		$store = new CustomersDataStore();

		// Two DateTime objects with the same timestamp but different microseconds.
		$dt1 = new \DateTime( '2026-04-27 12:00:00.123456' );
		$dt2 = new \DateTime( '2026-04-27 12:00:00.654321' );

		$key1 = $this->invoke_get_cache_key( $store, array( 'before' => $dt1 ) );
		$key2 = $this->invoke_get_cache_key( $store, array( 'before' => $dt2 ) );

		$this->assertSame( $key1, $key2, 'Cache keys should be identical when DateTime objects differ only in microseconds.' );
	}

	/**
	 * @testdox get_cache_key produces different keys for DateTime objects with different seconds.
	 */
	public function test_cache_key_differs_for_different_dates(): void {
		$store = new CustomersDataStore();

		$dt1 = new \DateTime( '2026-04-27 12:00:00' );
		$dt2 = new \DateTime( '2026-04-27 12:00:01' );

		$key1 = $this->invoke_get_cache_key( $store, array( 'before' => $dt1 ) );
		$key2 = $this->invoke_get_cache_key( $store, array( 'before' => $dt2 ) );

		$this->assertNotSame( $key1, $key2, 'Cache keys should differ when DateTime objects represent different moments.' );
	}

	/**
	 * @testdox get_cache_key produces identical keys for both before and after DateTime params in the same second.
	 */
	public function test_cache_key_is_stable_with_before_and_after(): void {
		$store = new CustomersDataStore();

		$params1 = array(
			'before' => new \DateTime( '2026-04-27 12:00:00.111111' ),
			'after'  => new \DateTime( '2026-04-20 12:00:00.222222' ),
		);
		$params2 = array(
			'before' => new \DateTime( '2026-04-27 12:00:00.999999' ),
			'after'  => new \DateTime( '2026-04-20 12:00:00.888888' ),
		);

		$key1 = $this->invoke_get_cache_key( $store, $params1 );
		$key2 = $this->invoke_get_cache_key( $store, $params2 );

		$this->assertSame( $key1, $key2, 'Cache keys should be identical when both before/after differ only in microseconds.' );
	}

	/**
	 * @testdox get_cache_key does not modify the original params array's DateTime objects.
	 */
	public function test_get_cache_key_does_not_modify_original_params(): void {
		$store = new CustomersDataStore();

		$original = new \DateTime( '2026-04-27 12:00:00.123456', new \DateTimeZone( 'UTC' ) );
		$params   = array( 'before' => clone $original );

		$this->invoke_get_cache_key( $store, $params );

		$this->assertInstanceOf( \DateTime::class, $params['before'], 'DateTime object in original params should remain a DateTime.' );
		$this->assertSame( $original->getTimestamp(), $params['before']->getTimestamp(), 'DateTime timestamp should not be mutated.' );
		$this->assertSame( $original->getTimezone()->getName(), $params['before']->getTimezone()->getName(), 'DateTime timezone should not be mutated.' );
	}

	/**
	 * Call the protected get_cache_key method via reflection.
	 *
	 * @param CustomersDataStore $store  DataStore instance.
	 * @param array              $params Parameters.
	 * @return string Cache key.
	 */
	private function invoke_get_cache_key( CustomersDataStore $store, array $params ): string {
		$method = new \ReflectionMethod( $store, 'get_cache_key' );
		$method->setAccessible( true );
		return $method->invoke( $store, $params );
	}
}
