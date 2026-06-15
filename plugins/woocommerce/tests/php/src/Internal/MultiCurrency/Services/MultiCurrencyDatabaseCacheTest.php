<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyCacheInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDatabaseCache;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyDatabaseCache class.
 */
class MultiCurrencyDatabaseCacheTest extends WC_Unit_Test_Case {

	/**
	 * Cache key used by these tests.
	 *
	 * @var string
	 */
	private string $cache_key = MultiCurrencyCacheInterface::CURRENCIES_KEY;

	/**
	 * Clean up test cache state before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		delete_option( $this->cache_key );
		wp_cache_delete( $this->cache_key, 'options' );
	}

	/**
	 * Clean up test cache state after each test.
	 */
	public function tear_down(): void {
		delete_option( $this->cache_key );
		wp_cache_delete( $this->cache_key, 'options' );

		parent::tear_down();
	}

	/**
	 * @testdox Should generate and store cache data.
	 */
	public function test_generates_and_stores_cache_data(): void {
		$cache = new MultiCurrencyDatabaseCache();

		$refreshed = false;
		$value     = $cache->get_or_add(
			$this->cache_key,
			static fn() => array(
				'currencies' => array( 'eur' => 1.2 ),
				'updated'    => 123,
			),
			static fn( $data ) => isset( $data['currencies'], $data['updated'] ),
			false,
			$refreshed
		);

		$this->assertSame(
			array(
				'currencies' => array( 'eur' => 1.2 ),
				'updated'    => 123,
			),
			$value
		);
		$this->assertTrue( $refreshed );
		$this->assertSame( $value, $cache->get( $this->cache_key ) );
	}

	/**
	 * @testdox Should reuse valid cached data without regenerating.
	 */
	public function test_reuses_cached_data_without_regenerating(): void {
		$cache = new MultiCurrencyDatabaseCache();

		$first_refresh = false;
		$cache->get_or_add(
			$this->cache_key,
			static fn() => array(
				'currencies' => array( 'eur' => 1.2 ),
				'updated'    => 123,
			),
			static fn( $data ) => isset( $data['currencies'], $data['updated'] ),
			false,
			$first_refresh
		);

		$second_refresh = false;
		$second_value   = $cache->get_or_add(
			$this->cache_key,
			static function () {
				throw new \RuntimeException( 'Generator should not run for valid cached data.' );
			},
			static fn( $data ) => isset( $data['currencies'], $data['updated'] ),
			false,
			$second_refresh
		);

		$this->assertTrue( $first_refresh );
		$this->assertFalse( $second_refresh );
		$this->assertSame(
			array(
				'currencies' => array( 'eur' => 1.2 ),
				'updated'    => 123,
			),
			$second_value
		);
	}

	/**
	 * @testdox Should return previous valid data when regeneration fails.
	 */
	public function test_returns_previous_value_when_regeneration_fails(): void {
		$cache = new MultiCurrencyDatabaseCache();

		$cache->get_or_add(
			$this->cache_key,
			static fn() => array(
				'currencies' => array( 'eur' => 1.2 ),
				'updated'    => 123,
			),
			static fn( $data ) => isset( $data['currencies'], $data['updated'] )
		);

		$refreshed = true;
		$value     = $cache->get_or_add(
			$this->cache_key,
			static fn() => null,
			static fn( $data ) => isset( $data['currencies'], $data['updated'] ),
			true,
			$refreshed
		);
		$stored    = get_option( $this->cache_key );

		$this->assertFalse( $refreshed );
		$this->assertSame(
			array(
				'currencies' => array( 'eur' => 1.2 ),
				'updated'    => 123,
			),
			$value
		);
		$this->assertIsArray( $stored );
		$this->assertTrue( $stored['errored'] );
		$this->assertSame( 1, $stored['consecutive_errors'] );
	}

	/**
	 * @testdox Should delete option and in-memory cache data.
	 */
	public function test_delete_removes_option_and_memory_cache(): void {
		$cache = new MultiCurrencyDatabaseCache();

		$cache->get_or_add(
			$this->cache_key,
			static fn() => array(
				'currencies' => array( 'eur' => 1.2 ),
				'updated'    => 123,
			),
			static fn( $data ) => isset( $data['currencies'], $data['updated'] )
		);

		$cache->delete( $this->cache_key );

		$this->assertFalse( get_option( $this->cache_key ) );
		$this->assertNull( $cache->get( $this->cache_key ) );
	}
}
