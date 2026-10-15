<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Cache;

use Automattic\WooCommerce\Internal\SiteHealth\Cache\CheckResultCache;
use WC_Unit_Test_Case;

/**
 * CheckResultCacheTest class.
 */
class CheckResultCacheTest extends WC_Unit_Test_Case {

	/**
	 * The cache instance under test.
	 *
	 * @var CheckResultCache
	 */
	private CheckResultCache $result_cache;

	/**
	 * Create a fresh cache instance before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->result_cache = new CheckResultCache();
	}

	/**
	 * remember() runs the factory once and returns the cached result on subsequent calls.
	 */
	public function test_remember_runs_callback_and_caches_result() {
		$calls   = 0;
		$factory = function () use ( &$calls ) {
			++$calls;
			return array(
				'status' => 'good',
				'label'  => 'L',
			);
		};

		$first  = $this->result_cache->remember( 'foo', $factory );
		$second = $this->result_cache->remember( 'foo', $factory );

		$this->assertSame( 1, $calls );
		$this->assertSame( $first, $second );
	}

	/**
	 * forget() invalidates the cache so the factory runs again on the next remember() call.
	 */
	public function test_forget_invalidates_cache() {
		$calls   = 0;
		$factory = function () use ( &$calls ) {
			++$calls;
			return array( 'status' => 'good' );
		};

		$this->result_cache->remember( 'foo', $factory );
		$this->result_cache->forget( 'foo' );
		$this->result_cache->remember( 'foo', $factory );

		$this->assertSame( 2, $calls );
	}

	/**
	 * The transient cache key embeds an md5 hash of the current WooCommerce version.
	 */
	public function test_cache_key_includes_wc_version() {
		$this->result_cache->remember( 'foo', fn() => array( 'status' => 'good' ) );
		global $wpdb;
		$found = $wpdb->get_var( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_woocommerce_site_health_foo_%'" );
		$this->assertNotNull( $found );
		// The key embeds an md5 hash of the WC version so that stale results from
		// previous versions are automatically ignored (they expire naturally).
		$expected_hash = md5( WC()->version );
		$this->assertStringContainsString( $expected_hash, $found );
	}

	/**
	 * Changing the WooCommerce version produces a new cache key, so the cache misses and the factory runs again.
	 */
	public function test_cache_key_changes_when_wc_version_changes() {
		$original = WC()->version;

		WC()->version = '99.99.99';
		$this->result_cache->remember( 'foo', fn() => array( 'v' => 'first' ) );

		WC()->version = '88.88.88';
		$calls        = 0;
		$factory      = function () use ( &$calls ) {
			++$calls;
			return array( 'v' => 'second' );
		};
		$this->result_cache->remember( 'foo', $factory );

		$this->assertSame( 1, $calls, 'Cache should miss when WC version changes' );

		WC()->version = $original;
	}

	/**
	 * The TTL filter keeps the result cached so a later call returns the original value rather than a new one.
	 */
	public function test_ttl_filter_applies() {
		add_filter( 'woocommerce_site_health_check_foo_cache_ttl', fn() => 60 );
		$this->result_cache->remember( 'foo', fn() => array( 'status' => 'good' ) );
		$this->assertSame( array( 'status' => 'good' ), $this->result_cache->remember( 'foo', fn() => array( 'status' => 'critical' ) ) );
		remove_all_filters( 'woocommerce_site_health_check_foo_cache_ttl' );
	}

	/**
	 * An empty result is not cached, so the factory runs again on the next call.
	 */
	public function test_remember_does_not_cache_empty_result() {
		$calls   = 0;
		$factory = function () use ( &$calls ) {
			++$calls;
			return array();
			// simulates a disabled check.
		};

		$this->result_cache->remember( 'foo', $factory );
		$this->result_cache->remember( 'foo', $factory );

		$this->assertSame( 2, $calls, 'Empty results must not be cached so re-enabling a check via filter takes immediate effect.' );
	}
}
