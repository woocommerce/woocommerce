<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\EntityVersionsCache;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Unit_Test_Case;

/**
 * Tests for EntityVersionsCache.
 */
class EntityVersionsCacheTest extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var object
	 */
	private $sut;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = $this->create_test_entity_versions_cache();
	}

	/**
	 * Create test EntityVersionsCache with local storage.
	 *
	 * @return object Test EntityVersionsCache instance.
	 */
	private function create_test_entity_versions_cache() {
		return new class() extends EntityVersionsCache {
			/**
			 * Cache storage.
			 *
			 * @var array
			 */
			public $cache = array();

			/**
			 * Get a value from the cache.
			 *
			 * @param string $cache_key The cache key.
			 * @return mixed|false The cached value or false if not found.
			 */
			protected function get_cached( string $cache_key ) {
				return $this->cache[ $cache_key ] ?? false;
			}

			/**
			 * Set a value in the cache.
			 *
			 * @param string $cache_key The cache key.
			 * @param mixed  $value     The value to cache.
			 * @param int    $ttl       Time to live in seconds.
			 * @return bool True on success, false on failure.
			 */
			protected function set_cached( string $cache_key, $value, int $ttl ): bool {
				$this->cache[ $cache_key ] = $value;
				return true;
			}

			/**
			 * Delete a value from the cache.
			 *
			 * @param string $cache_key The cache key.
			 * @return bool True on success, false on failure.
			 */
			protected function delete_cached( string $cache_key ): bool {
				if ( isset( $this->cache[ $cache_key ] ) ) {
					unset( $this->cache[ $cache_key ] );
					return true;
				}
				return false;
			}
		};
	}

	/**
	 * @testdox is_enabled returns true when filter enables the cache
	 */
	public function test_is_enabled_when_filter_enables() {
		// Use filter to enable cache regardless of wp_using_ext_object_cache.
		add_filter(
			'woocommerce_enable_entity_versions_cache',
			'__return_true'
		);

		// Create a new instance to test fresh is_enabled state.
		$cache = $this->create_test_entity_versions_cache();

		$this->assertTrue( $cache->is_enabled() );
	}

	/**
	 * @testdox is_enabled returns false when filter disables the cache
	 */
	public function test_is_enabled_when_filter_disables() {
		// Use filter to disable cache regardless of wp_using_ext_object_cache.
		add_filter(
			'woocommerce_enable_entity_versions_cache',
			'__return_false'
		);

		// Create a new instance to test fresh is_enabled state.
		$cache = $this->create_test_entity_versions_cache();

		$this->assertFalse( $cache->is_enabled() );
	}

	/**
	 * @testdox is_enabled caches the result and returns the same value on subsequent calls
	 */
	public function test_is_enabled_is_cached() {
		// Mock wp_using_ext_object_cache to return true initially.
		$call_count = 0;
		add_filter(
			'woocommerce_enable_entity_versions_cache',
			function ( $enabled ) use ( &$call_count ) {
				$call_count++;
				return $enabled;
			}
		);

		$result1 = $this->sut->is_enabled();
		$result2 = $this->sut->is_enabled();

		$this->assertEquals( $result1, $result2 );
		$this->assertEquals( 1, $call_count, 'Filter should only be called once, result is cached' );
	}

	/**
	 * @testdox get_entity_version creates a new version if it doesn't exist
	 */
	public function test_get_entity_version_creates_new_if_not_exists() {
		$version = $this->sut->get_entity_version( 'custom_entity', 123 );

		$this->assertNotEmpty( $version, 'Version should not be empty' );
		$this->assertIsString( $version, 'Version should be a string' );

		// Verify cache entry was created.
		$cache_key = 'wc_entity_version_custom_entity_123';
		$this->assertArrayHasKey( $cache_key, $this->sut->cache, 'Cache entry should be created' );
		$this->assertEquals( $version, $this->sut->cache[ $cache_key ], 'Stored version should match returned version' );
	}

	/**
	 * @testdox get_entity_version returns the existing version if it exists
	 */
	public function test_get_entity_version_returns_existing() {
		// Pre-populate cache with a known version.
		$expected_version               = 'existing-version-uuid';
		$cache_key                      = 'wc_entity_version_custom_entity_456';
		$this->sut->cache[ $cache_key ] = $expected_version;

		$version = $this->sut->get_entity_version( 'custom_entity', 456 );

		$this->assertEquals( $expected_version, $version, 'Should return existing version' );
	}

	/**
	 * @testdox get_entity_version refreshes the TTL of the existing entity version
	 */
	public function test_get_entity_version_refreshes_ttl() {
		// Pre-populate cache with a known version.
		$expected_version               = 'existing-version-uuid';
		$cache_key                      = 'wc_entity_version_custom_entity_789';
		$this->sut->cache[ $cache_key ] = $expected_version;

		// Track set_cached calls by monitoring the cache.
		$initial_cache_state = $this->sut->cache;

		$this->sut->get_entity_version( 'custom_entity', 789 );

		// Verify the cache entry still exists (refresh happened).
		$this->assertArrayHasKey( $cache_key, $this->sut->cache, 'Cache entry should still exist after refresh' );
		$this->assertEquals( $expected_version, $this->sut->cache[ $cache_key ], 'Value should remain the same after refresh' );
	}

	/**
	 * @testdox modify_entity_version sets a new version for a not yet versioned entity
	 */
	public function test_modify_entity_version_creates_new() {
		$version = $this->sut->modify_entity_version( 'new_entity', 111 );

		$this->assertNotEmpty( $version, 'Version should not be empty' );
		$this->assertIsString( $version, 'Version should be a string' );

		// Verify cache entry was created.
		$cache_key = 'wc_entity_version_new_entity_111';
		$this->assertArrayHasKey( $cache_key, $this->sut->cache, 'Cache entry should be created' );
		$this->assertEquals( $version, $this->sut->cache[ $cache_key ] );
	}

	/**
	 * @testdox modify_entity_version changes the version of an already versioned entity
	 */
	public function test_modify_entity_version_updates_existing() {
		// Pre-populate cache with a known version.
		$old_version                    = 'old-version-uuid';
		$cache_key                      = 'wc_entity_version_updated_entity_222';
		$this->sut->cache[ $cache_key ] = $old_version;

		$new_version = $this->sut->modify_entity_version( 'updated_entity', 222 );

		$this->assertNotEmpty( $new_version, 'New version should not be empty' );
		$this->assertNotEquals( $old_version, $new_version, 'New version should differ from old version' );
		$this->assertEquals( $new_version, $this->sut->cache[ $cache_key ], 'Stored version should be updated' );
	}

	/**
	 * @testdox forget_entity_version removes the cached entry for an already versioned entity
	 */
	public function test_forget_entity_version_removes_existing() {
		// Pre-populate cache with a known version.
		$cache_key                      = 'wc_entity_version_forgotten_entity_333';
		$this->sut->cache[ $cache_key ] = 'version-to-forget';

		$result = $this->sut->forget_entity_version( 'forgotten_entity', 333 );

		$this->assertTrue( $result, 'forget_entity_version should return true when entity existed' );
		$this->assertArrayNotHasKey( $cache_key, $this->sut->cache, 'Cache entry should be deleted' );
	}

	/**
	 * @testdox forget_entity_version does nothing for an entity that isn't versioned
	 */
	public function test_forget_entity_version_nonexistent() {
		$result = $this->sut->forget_entity_version( 'nonexistent_entity', 999 );

		$this->assertFalse( $result, 'forget_entity_version should return false when entity does not exist' );
	}

	/**
	 * @testdox woocommerce_cached_entity_version_ttl filter works correctly
	 */
	public function test_cached_entity_version_ttl_filter() {
		$custom_ttl   = 7200; // 2 hours.
		$filter_calls = array();

		add_filter(
			'woocommerce_cached_entity_version_ttl',
			function ( $ttl, $entity_type, $entity_id ) use ( $custom_ttl, &$filter_calls ) {
				$filter_calls[] = array(
					'ttl'         => $ttl,
					'entity_type' => $entity_type,
					'entity_id'   => $entity_id,
				);
				if ( 'custom_entity' === $entity_type && 555 === $entity_id ) {
					return $custom_ttl;
				}
				return $ttl;
			},
			10,
			3
		);

		$this->sut->modify_entity_version( 'custom_entity', 555 );

		// Verify filter was called with correct parameters.
		$this->assertCount( 1, $filter_calls, 'TTL filter should be called once' );
		$this->assertEquals( 'custom_entity', $filter_calls[0]['entity_type'] );
		$this->assertEquals( 555, $filter_calls[0]['entity_id'] );
		$this->assertEquals( HOUR_IN_SECONDS, $filter_calls[0]['ttl'], 'Default TTL should be passed to filter' );
	}

	/**
	 * @testdox woocommerce_entity_version_cached action is fired when version is created or modified
	 */
	public function test_entity_version_cached_action() {
		$action_calls = array();

		add_action(
			'woocommerce_entity_version_cached',
			function ( $entity_type, $entity_id, $ttl, $is_new ) use ( &$action_calls ) {
				$action_calls[] = array(
					'entity_type' => $entity_type,
					'entity_id'   => $entity_id,
					'ttl'         => $ttl,
					'is_new'      => $is_new,
				);
			},
			10,
			4
		);

		// Test creating new version.
		$this->sut->modify_entity_version( 'test_entity', 666 );

		$this->assertCount( 1, $action_calls, 'Action should be fired once for new version' );
		$this->assertEquals( 'test_entity', $action_calls[0]['entity_type'] );
		$this->assertEquals( 666, $action_calls[0]['entity_id'] );
		$this->assertEquals( HOUR_IN_SECONDS, $action_calls[0]['ttl'] );
		$this->assertTrue( $action_calls[0]['is_new'], 'is_new should be true for new version' );

		// Test refreshing existing version.
		$action_calls = array();
		$this->sut->get_entity_version( 'test_entity', 666 );

		$this->assertCount( 1, $action_calls, 'Action should be fired once for refresh' );
		$this->assertFalse( $action_calls[0]['is_new'], 'is_new should be false for refresh' );
	}

	/**
	 * @testdox woocommerce_entity_version_cache_deleted action is fired when version is deleted
	 */
	public function test_entity_version_cache_deleted_action() {
		$action_calls = array();

		add_action(
			'woocommerce_entity_version_cache_deleted',
			function ( $entity_type, $entity_id ) use ( &$action_calls ) {
				$action_calls[] = array(
					'entity_type' => $entity_type,
					'entity_id'   => $entity_id,
				);
			},
			10,
			2
		);

		// Pre-populate a version to delete.
		$this->sut->cache['wc_entity_version_deleted_entity_777'] = 'version-to-delete';

		$this->sut->forget_entity_version( 'deleted_entity', 777 );

		$this->assertCount( 1, $action_calls, 'Action should be fired once' );
		$this->assertEquals( 'deleted_entity', $action_calls[0]['entity_type'] );
		$this->assertEquals( 777, $action_calls[0]['entity_id'] );
	}

	/**
	 * @testdox woocommerce_entity_version_cache_deleted action is fired even when entity doesn't exist
	 */
	public function test_entity_version_cache_deleted_action_for_nonexistent() {
		$action_calls = array();

		add_action(
			'woocommerce_entity_version_cache_deleted',
			function ( $entity_type, $entity_id ) use ( &$action_calls ) {
				$action_calls[] = array(
					'entity_type' => $entity_type,
					'entity_id'   => $entity_id,
				);
			},
			10,
			2
		);

		$this->sut->forget_entity_version( 'nonexistent_entity', 888 );

		$this->assertCount( 1, $action_calls, 'Action should still be fired even when entity does not exist' );
		$this->assertEquals( 'nonexistent_entity', $action_calls[0]['entity_type'] );
		$this->assertEquals( 888, $action_calls[0]['entity_id'] );
	}
}
