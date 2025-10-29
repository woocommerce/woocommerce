<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\VersionStringsGenerator;
use WC_Unit_Test_Case;

/**
 * Tests for VersionStringsGenerator.
 */
class VersionStringsGeneratorTest extends WC_Unit_Test_Case {

	/**
	 * Cache group name.
	 */
	private const CACHE_GROUP = 'woocommerce_entity_version_keys';

	/**
	 * The System Under Test.
	 *
	 * @var VersionStringsGenerator
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new VersionStringsGenerator();

		// Flush the cache group before each test.
		wp_cache_flush();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_entity_version_key_ttl' );
		$this->sut = null;
		parent::tearDown();
	}

	/**
	 * @testdox should_use caches the result and returns the same value on subsequent calls.
	 */
	public function test_should_use_is_cached() {
		$result1 = $this->sut->should_use();
		$result2 = $this->sut->should_use();

		$this->assertEquals( $result1, $result2, 'should_use should return the same value on subsequent calls' );
	}

	/**
	 * @testdox get_entity_version creates a new version if it doesn't exist.
	 */
	public function test_get_entity_version_creates_new_if_not_exists() {
		$version = $this->sut->get_entity_version( 'custom_entity', 123 );

		$this->assertNotEmpty( $version, 'Version should not be empty' );
		$this->assertIsString( $version, 'Version should be a string' );

		$cache_key    = 'wc_entity_version_key_custom_entity_123';
		$cached_value = wp_cache_get( $cache_key, self::CACHE_GROUP );
		$this->assertNotFalse( $cached_value, 'Cache entry should be created' );
		$this->assertEquals( $version, $cached_value, 'Stored version should match returned version' );
	}

	/**
	 * @testdox get_entity_version returns the existing version if it exists.
	 */
	public function test_get_entity_version_returns_existing() {
		// Pre-populate cache with a known version.

		$expected_version = 'existing-version-uuid';
		$cache_key        = 'wc_entity_version_key_custom_entity_456';
		wp_cache_set( $cache_key, $expected_version, self::CACHE_GROUP );

		$version = $this->sut->get_entity_version( 'custom_entity', 456 );

		$this->assertEquals( $expected_version, $version, 'Should return existing version' );
	}

	/**
	 * @testdox get_entity_version refreshes the TTL of the existing entity version.
	 */
	public function test_get_entity_version_refreshes_ttl() {
		// Pre-populate cache with a known version.

		$expected_version = 'existing-version-uuid';
		$cache_key        = 'wc_entity_version_key_custom_entity_789';
		wp_cache_set( $cache_key, $expected_version, self::CACHE_GROUP );

		$this->sut->get_entity_version( 'custom_entity', 789 );

		// Verify the cache entry still exists (refresh happened).

		$cached_value = wp_cache_get( $cache_key, self::CACHE_GROUP );
		$this->assertNotFalse( $cached_value, 'Cache entry should still exist after refresh' );
		$this->assertEquals( $expected_version, $cached_value, 'Value should remain the same after refresh' );
	}

	/**
	 * @testdox regenerate_entity_version sets a new version for a not yet versioned entity.
	 */
	public function test_regenerate_entity_version_creates_new() {
		$version = $this->sut->regenerate_entity_version( 'new_entity', 111 );

		$this->assertNotEmpty( $version, 'Version should not be empty' );
		$this->assertIsString( $version, 'Version should be a string' );

		// Verify cache entry was created.

		$cache_key    = 'wc_entity_version_key_new_entity_111';
		$cached_value = wp_cache_get( $cache_key, self::CACHE_GROUP );
		$this->assertNotFalse( $cached_value, 'Cache entry should be created' );
		$this->assertEquals( $version, $cached_value );
	}

	/**
	 * @testdox regenerate_entity_version changes the version of an already versioned entity.
	 */
	public function test_regenerate_entity_version_updates_existing() {
		// Pre-populate cache with a known version.

		$old_version = 'old-version-uuid';
		$cache_key   = 'wc_entity_version_key_updated_entity_222';
		wp_cache_set( $cache_key, $old_version, self::CACHE_GROUP );

		$new_version = $this->sut->regenerate_entity_version( 'updated_entity', 222 );

		$this->assertNotEmpty( $new_version, 'New version should not be empty' );
		$this->assertNotEquals( $old_version, $new_version, 'New version should differ from old version' );

		$cached_value = wp_cache_get( $cache_key, self::CACHE_GROUP );
		$this->assertEquals( $new_version, $cached_value, 'Stored version should be updated' );
	}

	/**
	 * @testdox delete_entity_version removes the cached entry for an already versioned entity.
	 */
	public function test_delete_entity_version_removes_existing() {
		// Pre-populate cache with a known version.

		$cache_key = 'wc_entity_version_key_forgotten_entity_333';
		wp_cache_set( $cache_key, 'version-to-forget', self::CACHE_GROUP );

		$result = $this->sut->delete_entity_version( 'forgotten_entity', 333 );

		$this->assertTrue( $result, 'delete_entity_version should return true when entity existed' );
		$cached_value = wp_cache_get( $cache_key, self::CACHE_GROUP );
		$this->assertFalse( $cached_value, 'Cache entry should be deleted' );
	}

	/**
	 * @testdox delete_entity_version does nothing for an entity that isn't versioned.
	 */
	public function test_delete_entity_version_nonexistent() {
		$result = $this->sut->delete_entity_version( 'nonexistent_entity', 999 );

		$this->assertFalse( $result, 'delete_entity_version should return false when entity does not exist' );
	}

	/**
	 * @testdox woocommerce_entity_version_key_ttl filter works correctly.
	 */
	public function test_cached_entity_version_ttl_filter() {
		$custom_ttl   = 7200; // 2 hours.
		$filter_calls = array();

		add_filter(
			'woocommerce_entity_version_key_ttl',
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

		$this->sut->regenerate_entity_version( 'custom_entity', 555 );

		$this->assertCount( 1, $filter_calls, 'TTL filter should be called once' );
		$this->assertEquals( 'custom_entity', $filter_calls[0]['entity_type'] );
		$this->assertEquals( 555, $filter_calls[0]['entity_id'] );
		$this->assertEquals( DAY_IN_SECONDS, $filter_calls[0]['ttl'], 'Default TTL should be passed to filter' );
	}

	/**
	 * @testdox Entity IDs work correctly.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $use_numeric_id Whether to use numeric ID (true) or string ID (false).
	 */
	public function test_entity_ids( bool $use_numeric_id ) {
		$entity_id = $use_numeric_id ? 123 : 'string-id-123';

		// Get version for entity ID (creates new version).

		$version1 = $this->sut->get_entity_version( 'custom_entity', $entity_id );
		$this->assertNotEmpty( $version1, 'Should create version for entity ID' );

		// Retrieving again should return same version.

		$version2 = $this->sut->get_entity_version( 'custom_entity', $entity_id );
		$this->assertEquals( $version1, $version2, 'Same entity ID should return same version' );

		// Modifying version should create new UUID.

		$version3 = $this->sut->regenerate_entity_version( 'custom_entity', $entity_id );
		$this->assertNotEquals( $version1, $version3, 'Modified version should be different' );

		// Forgetting version should work.

		$result = $this->sut->delete_entity_version( 'custom_entity', $entity_id );
		$this->assertTrue( $result, 'Should successfully forget entity version' );

		// After forgetting, getting should create new version.

		$version4 = $this->sut->get_entity_version( 'custom_entity', $entity_id );
		$this->assertNotEquals( $version3, $version4, 'After forgetting, new version should be created' );
	}

	/**
	 * @testdox Numeric and string entity IDs are equivalent when stringified
	 */
	public function test_numeric_and_string_ids_equivalence() {
		// Create versions for both numeric and string IDs.

		$numeric_version = $this->sut->get_entity_version( 'product', 123 );
		$string_version  = $this->sut->get_entity_version( 'product', '123' );

		// They should be the SAME (same cache key due to string interpolation).

		$this->assertEquals( $numeric_version, $string_version, 'Numeric 123 and string "123" should have same version (same cache key)' );

		// Modifying one should affect the other (because they share the same cache key).

		$this->sut->regenerate_entity_version( 'product', 123 );
		$new_numeric_version = $this->sut->get_entity_version( 'product', 123 );
		$new_string_version  = $this->sut->get_entity_version( 'product', '123' );

		$this->assertEquals( $new_numeric_version, $new_string_version, 'After modifying numeric ID, string ID should have same new version' );
		$this->assertNotEquals( $numeric_version, $new_numeric_version, 'Version should have changed after modification' );
	}

	/**
	 * @testdox get_entity_version throws InvalidArgumentException when entity_type is empty.
	 */
	public function test_get_entity_version_throws_on_empty_entity_type() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Entity type cannot be empty.' );

		$this->sut->get_entity_version( '', 123 );
	}

	/**
	 * @testdox get_entity_version throws InvalidArgumentException when entity_id is empty string.
	 */
	public function test_get_entity_version_throws_on_empty_entity_id() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Entity ID cannot be an empty string.' );

		$this->sut->get_entity_version( 'product', '' );
	}

	/**
	 * @testdox get_entity_version throws InvalidArgumentException when entity_id is invalid type.
	 */
	public function test_get_entity_version_throws_on_invalid_entity_id_type() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Entity ID must be a number or a string.' );

		$this->sut->get_entity_version( 'product', array( 123 ) );
	}

	/**
	 * @testdox regenerate_entity_version throws InvalidArgumentException when entity_type is empty.
	 */
	public function test_regenerate_entity_version_throws_on_empty_entity_type() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Entity type cannot be empty.' );

		$this->sut->regenerate_entity_version( '', 123 );
	}

	/**
	 * @testdox regenerate_entity_version throws InvalidArgumentException when entity_id is empty string.
	 */
	public function test_regenerate_entity_version_throws_on_empty_entity_id() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Entity ID cannot be an empty string.' );

		$this->sut->regenerate_entity_version( 'product', '' );
	}

	/**
	 * @testdox regenerate_entity_version throws InvalidArgumentException when entity_id is invalid type.
	 */
	public function test_regenerate_entity_version_throws_on_invalid_entity_id_type() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Entity ID must be a number or a string.' );

		$this->sut->regenerate_entity_version( 'product', null );
	}

	/**
	 * @testdox delete_entity_version throws InvalidArgumentException when entity_type is empty.
	 */
	public function test_delete_entity_version_throws_on_empty_entity_type() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Entity type cannot be empty.' );

		$this->sut->delete_entity_version( '', 123 );
	}

	/**
	 * @testdox delete_entity_version throws InvalidArgumentException when entity_id is empty string.
	 */
	public function test_delete_entity_version_throws_on_empty_entity_id() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Entity ID cannot be an empty string.' );

		$this->sut->delete_entity_version( 'product', '' );
	}

	/**
	 * @testdox delete_entity_version throws InvalidArgumentException when entity_id is invalid type.
	 */
	public function test_delete_entity_version_throws_on_invalid_entity_id_type() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Entity ID must be a number or a string.' );

		$this->sut->delete_entity_version( 'product', true );
	}

	/**
	 * @testdox Negative TTL from filter is converted to 0.
	 */
	public function test_negative_ttl_is_converted_to_zero() {
		// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

		add_filter(
			'woocommerce_entity_version_key_ttl',
			function ( $ttl, $entity_type, $entity_id ) {
				return -100;
			},
			10,
			3
		);

		// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter

		// Create a test cache instance that captures the TTL.
		$captured_ttl = null;
		$cache        = new class( $captured_ttl ) extends VersionStringsGenerator {
			/**
			 * Cache storage.
			 *
			 * @var array
			 */
			public $cache = array();

			/**
			 * Reference to captured TTL.
			 *
			 * @var int|null
			 */
			private $captured_ttl_ref;

			/**
			 * Constructor.
			 *
			 * @param int|null $captured_ttl_ref Reference to captured TTL.
			 */
			public function __construct( &$captured_ttl_ref ) {
				$this->captured_ttl_ref = &$captured_ttl_ref;
			}

			/**
			 * Get a value from the cache.
			 *
			 * @param string $cache_key The cache key.
			 * @return mixed|null The cached value or null if not found.
			 */
			protected function get_cached( string $cache_key ) {
				return $this->entity_cache[ $cache_key ] ?? null;
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
				$this->captured_ttl_ref           = $ttl;
				$this->entity_cache[ $cache_key ] = $value;
				return true;
			}

			/**
			 * Delete a value from the cache.
			 *
			 * @param string $cache_key The cache key.
			 * @return bool True on success, false on failure.
			 */
			protected function delete_cached( string $cache_key ): bool {
				if ( isset( $this->entity_cache[ $cache_key ] ) ) {
					unset( $this->entity_cache[ $cache_key ] );
					return true;
				}
				return false;
			}
		};

		$cache->regenerate_entity_version( 'product', 123 );

		$this->assertEquals( 0, $captured_ttl, 'Negative TTL should be converted to 0' );
	}
}
