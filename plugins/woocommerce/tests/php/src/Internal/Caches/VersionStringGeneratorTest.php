<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\VersionStringGenerator;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Unit_Test_Case;

/**
 * Tests for VersionStringGenerator.
 */
class VersionStringGeneratorTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The System Under Test.
	 *
	 * @var VersionStringGenerator
	 */
	private $sut;

	/**
	 * The real object cache, saved while a mock is installed in its place.
	 *
	 * @var \WP_Object_Cache|null
	 */
	private $original_object_cache = null;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( VersionStringGenerator::class );

		wp_cache_flush();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		$this->restore_object_cache();
		remove_all_filters( 'woocommerce_version_string_generator_ttl' );
		$this->sut = null;
		parent::tearDown();
	}

	/**
	 * Get the cache group from the SUT using reflection.
	 *
	 * @return string
	 */
	private function get_cache_group(): string {
		$reflection = new \ReflectionClass( $this->sut );
		$constant   = $reflection->getConstant( 'CACHE_GROUP' );
		return $constant;
	}

	/**
	 * Get the cache key the SUT uses for an ID.
	 *
	 * @param string $id The ID to get the cache key for.
	 * @return string
	 */
	private function get_version_cache_key( string $id ): string {
		return 'wc_version_string_' . md5( $id );
	}

	/**
	 * Install a mock object cache in place of the real one.
	 *
	 * The real cache is restored in tearDown, so tests don't need to unwind it themselves.
	 *
	 * @return \WP_Object_Cache|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function install_mock_object_cache() {
		global $wp_object_cache;

		$mock = $this->createMock( \WP_Object_Cache::class );

		$this->original_object_cache = $wp_object_cache;
		$wp_object_cache             = $mock; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $mock;
	}

	/**
	 * Restore the real object cache, if a mock was installed in its place.
	 *
	 * @return void
	 */
	private function restore_object_cache(): void {
		global $wp_object_cache;

		if ( null === $this->original_object_cache ) {
			return;
		}

		$wp_object_cache             = $this->original_object_cache; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->original_object_cache = null;
	}

	/**
	 * @testdox can_use caches the result and returns the same value on subsequent calls.
	 */
	public function test_can_use_is_cached() {
		$result1 = $this->sut->can_use();
		$result2 = $this->sut->can_use();

		$this->assertEquals( $result1, $result2, 'can_use should return the same value on subsequent calls' );
	}

	/**
	 * @testdox get_version creates a new version if it doesn't exist.
	 */
	public function test_get_version_creates_new_if_not_exists() {
		$version = $this->sut->get_version( 'custom-id-123' );

		$this->assertNotEmpty( $version, 'Version should not be empty' );
		$this->assertIsString( $version, 'Version should be a string' );

		$cache_key    = 'wc_version_string_' . md5( 'custom-id-123' );
		$cached_value = wp_cache_get( $cache_key, $this->get_cache_group() );
		$this->assertNotFalse( $cached_value, 'Cache entry should be created' );
		$this->assertEquals( $version, $cached_value, 'Stored version should match returned version' );
	}

	/**
	 * @testdox get_version returns the existing version if it exists.
	 */
	public function test_get_version_returns_existing() {
		$expected_version = 'existing-version-uuid';
		$cache_key        = 'wc_version_string_' . md5( 'custom-id-456' );
		wp_cache_set( $cache_key, $expected_version, $this->get_cache_group() );

		$version = $this->sut->get_version( 'custom-id-456' );

		$this->assertEquals( $expected_version, $version, 'Should return existing version' );
	}

	/**
	 * @testdox get_version refreshes the TTL of the existing version.
	 */
	public function test_get_version_refreshes_ttl() {
		$expected_version = 'existing-version-uuid';
		$cache_key        = 'wc_version_string_' . md5( 'custom-id-789' );
		wp_cache_set( $cache_key, $expected_version, $this->get_cache_group() );

		$this->sut->get_version( 'custom-id-789' );

		// Verify the cache entry still exists (refresh happened).

		$cached_value = wp_cache_get( $cache_key, $this->get_cache_group() );
		$this->assertNotFalse( $cached_value, 'Cache entry should still exist after refresh' );
		$this->assertEquals( $expected_version, $cached_value, 'Value should remain the same after refresh' );
	}

	/**
	 * @testdox get_version returns null when version doesn't exist and generate is false.
	 */
	public function test_get_version_returns_null_when_not_found_and_generate_false() {
		$version = $this->sut->get_version( 'nonexistent-id', false );

		$this->assertNull( $version, 'Should return null when version does not exist and generate is false' );

		$cache_key    = 'wc_version_string_' . md5( 'nonexistent-id' );
		$cached_value = wp_cache_get( $cache_key, $this->get_cache_group() );
		$this->assertFalse( $cached_value, 'No cache entry should be created when generate is false' );
	}

	/**
	 * @testdox get_version does not delete on a genuine cache miss.
	 */
	public function test_get_version_does_not_delete_on_genuine_cache_miss(): void {
		$mock_cache = $this->install_mock_object_cache();
		$mock_cache
			->expects( $this->once() )
			->method( 'get' )
			->willReturnCallback(
				static function ( $key, $group, $force, &$found ) {
					$found = false;
					return false;
				}
			);
		$mock_cache->expects( $this->never() )->method( 'delete' );

		$version = $this->sut->get_version( 'genuine-cache-miss', false );

		$this->assertNull( $version, 'A genuine cache miss should return null when generation is disabled' );
	}

	/**
	 * @testdox get_version does not delete when the cache signals a miss with null instead of false.
	 */
	public function test_get_version_does_not_delete_on_null_cache_miss(): void {
		$mock_cache = $this->install_mock_object_cache();
		$mock_cache
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( null );
		$mock_cache->expects( $this->never() )->method( 'delete' );

		$version = $this->sut->get_version( 'null-cache-miss', false );

		$this->assertNull( $version, 'A null miss should be treated as a miss, not as a stored invalid value' );
	}

	/**
	 * @testdox get_version deletes a stored false when the cache reports found as a truthy non-boolean.
	 */
	public function test_get_version_deletes_stored_false_when_found_flag_is_truthy(): void {
		$mock_cache = $this->install_mock_object_cache();
		$mock_cache
			->expects( $this->once() )
			->method( 'get' )
			->willReturnCallback(
				static function ( $key, $group, $force, &$found ) {
					$found = 1;
					return false;
				}
			);
		$mock_cache
			->expects( $this->once() )
			->method( 'delete' )
			->with( $this->get_version_cache_key( 'stored-false-truthy-found' ), $this->get_cache_group() )
			->willReturn( true );

		$version = $this->sut->get_version( 'stored-false-truthy-found', false );

		$this->assertNull( $version, 'A stored false should be treated as an invalid cached value' );
	}

	/**
	 * @testdox get_version accepts a cached string when the cache does not set the found flag.
	 */
	public function test_get_version_accepts_cached_string_without_found_flag(): void {
		$cached_version = 'cached-version';

		$mock_cache = $this->install_mock_object_cache();
		$mock_cache
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( $cached_version );
		$mock_cache
			->expects( $this->once() )
			->method( 'set' )
			->with( $this->get_version_cache_key( 'cached-string-without-found' ), $cached_version, $this->get_cache_group(), DAY_IN_SECONDS )
			->willReturn( true );
		$mock_cache->expects( $this->never() )->method( 'delete' );

		$version = $this->sut->get_version( 'cached-string-without-found' );

		$this->assertSame( $cached_version, $version, 'A valid cached string should not depend on the found flag' );
	}

	/**
	 * @testdox get_version deletes an invalid non-false value when the cache does not set the found flag.
	 */
	public function test_get_version_deletes_invalid_non_false_value_without_found_flag(): void {
		$mock_cache = $this->install_mock_object_cache();
		$mock_cache
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( true );
		$mock_cache
			->expects( $this->once() )
			->method( 'delete' )
			->with( $this->get_version_cache_key( 'invalid-value-without-found' ), $this->get_cache_group() )
			->willReturn( true );

		$version = $this->sut->get_version( 'invalid-value-without-found', false );

		$this->assertNull( $version, 'An invalid cached value should be treated as a miss' );
	}

	/**
	 * @testdox get_version replaces an invalid cached value without a separate delete when generating.
	 */
	public function test_get_version_does_not_delete_when_generating_a_replacement(): void {
		$mock_cache = $this->install_mock_object_cache();
		$mock_cache
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( array( 'unexpected' ) );
		$mock_cache
			->expects( $this->once() )
			->method( 'set' )
			->with( $this->get_version_cache_key( 'invalid-value-regenerated' ), $this->anything(), $this->get_cache_group(), DAY_IN_SECONDS )
			->willReturn( true );
		$mock_cache->expects( $this->never() )->method( 'delete' );

		$version = $this->sut->get_version( 'invalid-value-regenerated' );

		$this->assertTrue( wp_is_uuid( (string) $version, 4 ), 'A new version should be generated over the invalid value' );
	}

	/**
	 * @testdox get_version logs a warning when it discards an invalid cached value.
	 */
	public function test_get_version_logs_when_discarding_invalid_cached_value(): void {
		$cache_key = $this->get_version_cache_key( 'logged-invalid-value' );
		wp_cache_set( $cache_key, 42, $this->get_cache_group() );

		$this->sut->get_version( 'logged-invalid-value' );

		$this->assertLogged(
			'warning',
			'Discarded an invalid version string cache entry for ID "logged-invalid-value" (got integer); the version will be regenerated.',
			array( 'source' => 'version-string-generator' )
		);
	}

	/**
	 * @testdox get_version logs the deletion outcome when generation is disabled.
	 */
	public function test_get_version_logs_deletion_outcome_when_generation_disabled(): void {
		$cache_key = $this->get_version_cache_key( 'logged-invalid-value-no-generate' );
		wp_cache_set( $cache_key, 42, $this->get_cache_group() );

		$this->sut->get_version( 'logged-invalid-value-no-generate', false );

		// Nothing is regenerated on this path, so the message must not claim it is.
		$this->assertLogged(
			'warning',
			'Discarded an invalid version string cache entry for ID "logged-invalid-value-no-generate" (got integer); the entry will be deleted.',
			array( 'source' => 'version-string-generator' )
		);
	}

	/**
	 * @testdox get_version does not log on a genuine cache miss.
	 */
	public function test_get_version_does_not_log_on_genuine_cache_miss(): void {
		$this->sut->get_version( 'unlogged-cache-miss' );

		$this->assertEmpty(
			$this->captured_logs,
			'A genuine cache miss is the common path and should never be logged'
		);
	}

	/**
	 * @testdox get_version generates version by default when it doesn't exist.
	 */
	public function test_get_version_generates_by_default() {
		$version = $this->sut->get_version( 'auto-generate-id' );

		$this->assertNotNull( $version, 'Should generate version by default' );
		$this->assertIsString( $version, 'Generated version should be a string' );

		$cache_key    = 'wc_version_string_' . md5( 'auto-generate-id' );
		$cached_value = wp_cache_get( $cache_key, $this->get_cache_group() );
		$this->assertNotFalse( $cached_value, 'Cache entry should be created' );
		$this->assertEquals( $version, $cached_value );
	}

	/**
	 * Invalid values that can be returned by an object cache.
	 *
	 * @return array<string, array{mixed}>
	 */
	public static function invalid_cached_version_values(): array {
		return array(
			'boolean false' => array( false ),
			'empty string'  => array( '' ),
			'null'          => array( null ),
			'array'         => array( array( 'unexpected' ) ),
			'integer'       => array( 42 ),
		);
	}

	/**
	 * @testdox get_version replaces invalid cached values when generation is enabled.
	 * @dataProvider invalid_cached_version_values
	 *
	 * @param mixed $invalid_value Invalid cached value.
	 */
	public function test_get_version_replaces_invalid_cached_value_when_generation_enabled( $invalid_value ): void {
		$cache_key = 'wc_version_string_' . md5( 'invalid-cached-version' );
		wp_cache_set( $cache_key, $invalid_value, $this->get_cache_group() );

		$version = $this->sut->get_version( 'invalid-cached-version' );

		$this->assertIsString( $version, 'A new version string should be generated' );
		$this->assertTrue( wp_is_uuid( (string) $version, 4 ), 'The generated version should be a UUID' );
		$this->assertSame(
			$version,
			wp_cache_get( $cache_key, $this->get_cache_group() ),
			'The invalid cached value should be replaced'
		);
	}

	/**
	 * @testdox get_version deletes invalid cached values when generation is disabled.
	 * @dataProvider invalid_cached_version_values
	 *
	 * @param mixed $invalid_value Invalid cached value.
	 */
	public function test_get_version_deletes_invalid_cached_value_when_generation_disabled( $invalid_value ): void {
		$cache_key = 'wc_version_string_' . md5( 'invalid-cached-version' );
		wp_cache_set( $cache_key, $invalid_value, $this->get_cache_group() );

		$version = $this->sut->get_version( 'invalid-cached-version', false );

		$this->assertNull( $version, 'Generation-disabled cache misses should return null' );

		$found = null;
		wp_cache_get( $cache_key, $this->get_cache_group(), false, $found );
		$this->assertFalse( $found, 'The invalid cached value should be deleted' );
	}

	/**
	 * @testdox generate_version sets a new version for a not yet versioned ID.
	 */
	public function test_generate_version_creates_new() {
		$version = $this->sut->generate_version( 'new-id-111' );

		$this->assertNotEmpty( $version, 'Version should not be empty' );
		$this->assertIsString( $version, 'Version should be a string' );

		$cache_key    = 'wc_version_string_' . md5( 'new-id-111' );
		$cached_value = wp_cache_get( $cache_key, $this->get_cache_group() );
		$this->assertNotFalse( $cached_value, 'Cache entry should be created' );
		$this->assertEquals( $version, $cached_value );
	}

	/**
	 * @testdox generate_version changes the version of an already versioned ID.
	 */
	public function test_generate_version_updates_existing() {
		$old_version = 'old-version-uuid';
		$cache_key   = 'wc_version_string_' . md5( 'updated-id-222' );
		wp_cache_set( $cache_key, $old_version, $this->get_cache_group() );

		$new_version = $this->sut->generate_version( 'updated-id-222' );

		$this->assertNotEmpty( $new_version, 'New version should not be empty' );
		$this->assertNotEquals( $old_version, $new_version, 'New version should differ from old version' );

		$cached_value = wp_cache_get( $cache_key, $this->get_cache_group() );
		$this->assertEquals( $new_version, $cached_value, 'Stored version should be updated' );
	}

	/**
	 * @testdox delete_version removes the cached entry for an already versioned ID.
	 */
	public function test_delete_version_removes_existing() {
		$cache_key = 'wc_version_string_' . md5( 'forgotten-id-333' );
		wp_cache_set( $cache_key, 'version-to-forget', $this->get_cache_group() );

		$result = $this->sut->delete_version( 'forgotten-id-333' );

		$this->assertTrue( $result, 'delete_version should return true when entry existed' );
		$cached_value = wp_cache_get( $cache_key, $this->get_cache_group() );
		$this->assertFalse( $cached_value, 'Cache entry should be deleted' );
	}

	/**
	 * @testdox delete_version does nothing for an ID that isn't versioned.
	 */
	public function test_delete_version_nonexistent() {
		$result = $this->sut->delete_version( 'nonexistent-id-999' );

		$this->assertFalse( $result, 'delete_version should return false when entry does not exist' );
	}

	/**
	 * @testdox woocommerce_version_string_generator_ttl filter works correctly.
	 */
	public function test_cached_version_ttl_filter() {
		$custom_ttl   = 7200; // 2 hours.
		$filter_calls = array();

		add_filter(
			'woocommerce_version_string_generator_ttl',
			function ( $ttl, $id ) use ( $custom_ttl, &$filter_calls ) {
				$filter_calls[] = array(
					'ttl' => $ttl,
					'id'  => $id,
				);
				if ( 'custom-id-555' === $id ) {
					return $custom_ttl;
				}
				return $ttl;
			},
			10,
			2
		);

		$this->sut->generate_version( 'custom-id-555' );

		$this->assertCount( 1, $filter_calls, 'TTL filter should be called once' );
		$this->assertEquals( 'custom-id-555', $filter_calls[0]['id'] );
		$this->assertEquals( DAY_IN_SECONDS, $filter_calls[0]['ttl'], 'Default TTL should be passed to filter' );
	}

	/**
	 * @testdox String IDs work correctly.
	 */
	public function test_string_ids() {
		$id = 'string-id-abc-123';

		// Get version for ID (creates new version).

		$version1 = $this->sut->get_version( $id );
		$this->assertNotEmpty( $version1, 'Should create version for string ID' );

		// Retrieving again should return same version.

		$version2 = $this->sut->get_version( $id );
		$this->assertEquals( $version1, $version2, 'Same ID should return same version' );

		// Generating new version should create new UUID.

		$version3 = $this->sut->generate_version( $id );
		$this->assertNotEquals( $version1, $version3, 'Generated version should be different' );

		// Deleting version should work.

		$result = $this->sut->delete_version( $id );
		$this->assertTrue( $result, 'Should successfully delete version' );

		// After deleting, getting should create new version.

		$version4 = $this->sut->get_version( $id );
		$this->assertNotEquals( $version3, $version4, 'After deleting, new version should be created' );
	}

	/**
	 * @testdox Numeric-looking string IDs are treated as strings.
	 */
	public function test_numeric_string_ids() {
		$version1 = $this->sut->get_version( '123' );
		$version2 = $this->sut->get_version( '123' );

		$this->assertEquals( $version1, $version2, 'Numeric string "123" should have consistent version' );

		$this->sut->generate_version( '123' );
		$new_version = $this->sut->get_version( '123' );

		$this->assertNotEquals( $version1, $new_version, 'Version should have changed after generation' );
	}

	/**
	 * @testdox get_version throws InvalidArgumentException when id is empty.
	 */
	public function test_get_version_throws_on_empty_id() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'ID cannot be empty.' );

		$this->sut->get_version( '' );
	}

	/**
	 * @testdox generate_version throws InvalidArgumentException when id is empty.
	 */
	public function test_generate_version_throws_on_empty_id() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'ID cannot be empty.' );

		$this->sut->generate_version( '' );
	}

	/**
	 * @testdox delete_version throws InvalidArgumentException when id is empty.
	 */
	public function test_delete_version_throws_on_empty_id() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'ID cannot be empty.' );

		$this->sut->delete_version( '' );
	}

	/**
	 * @testdox delete_version returns true when wp_cache_delete returns a non-boolean value.
	 */
	public function test_delete_version_returns_true_for_non_bool_cache_delete(): void {
		global $wp_object_cache;
		$original_cache = $wp_object_cache;

		try {
			$mock_cache = $this->createMock( \WP_Object_Cache::class );
			$mock_cache->method( 'delete' )->willReturn( null );
			$wp_object_cache = $mock_cache; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			$result = $this->sut->delete_version( 'some-id' );

			$this->assertTrue( $result, 'delete_version should return true when wp_cache_delete returns non-boolean' );
		} finally {
			$wp_object_cache = $original_cache; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * @testdox store_version succeeds when wp_cache_set returns non-boolean but the value is correctly stored.
	 */
	public function test_store_version_succeeds_for_non_bool_cache_set_with_correct_value(): void {
		global $wp_object_cache;
		$original_cache = $wp_object_cache;

		try {
			// Mock that returns null from set() but delegates get() to the real cache,
			// simulating a non-standard cache that stores correctly but returns null.
			$mock_cache = $this->createMock( \WP_Object_Cache::class );
			$mock_cache->method( 'set' )->willReturnCallback(
				function ( $key, $data, $group, $expire ) use ( $original_cache ) {
					$original_cache->set( $key, $data, $group, $expire );
					return null;
				}
			);
			$mock_cache->method( 'get' )->willReturnCallback(
				function ( $key, $group, $force, &$found ) use ( $original_cache ) {
					return $original_cache->get( $key, $group, $force, $found );
				}
			);
			$wp_object_cache = $mock_cache; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			$version = $this->sut->generate_version( 'test-store-ok' );

			$this->assertNotEmpty( $version, 'generate_version should return a version even when wp_cache_set returns non-boolean' );
		} finally {
			$wp_object_cache = $original_cache; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * @testdox store_version fails and cleans up when wp_cache_set returns non-boolean and the stored value doesn't match.
	 */
	public function test_store_version_fails_for_non_bool_cache_set_with_wrong_value(): void {
		global $wp_object_cache;
		$original_cache = $wp_object_cache;

		try {
			$mock_cache = $this->createMock( \WP_Object_Cache::class );
			$mock_cache->method( 'set' )->willReturnCallback(
				function ( $key, $data, $group, $expire ) use ( $original_cache ) {
					// Store a wrong value to simulate a corrupted write.
					$original_cache->set( $key, 'wrong-value', $group, $expire );
					return null;
				}
			);
			$mock_cache->method( 'get' )->willReturnCallback(
				function ( $key, $group, $force, &$found ) use ( $original_cache ) {
					return $original_cache->get( $key, $group, $force, $found );
				}
			);
			$mock_cache->method( 'delete' )->willReturnCallback(
				function ( $key, $group ) use ( $original_cache ) {
					return $original_cache->delete( $key, $group );
				}
			);
			$wp_object_cache = $mock_cache; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			$version = $this->sut->generate_version( 'test-store-fail' );

			// generate_version still returns a UUID string, but the store failed silently.
			$this->assertNotEmpty( $version );
		} finally {
			$wp_object_cache = $original_cache; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		// The corrupted value should have been cleaned up.
		$cache_key    = 'wc_version_string_' . md5( 'test-store-fail' );
		$cached_value = wp_cache_get( $cache_key, $this->get_cache_group() );
		$this->assertFalse( $cached_value, 'Mismatched cached value should have been deleted' );
	}

	/**
	 * @testdox Negative TTL from filter is converted to 0 and cache operations still succeed.
	 */
	public function test_negative_ttl_is_converted_to_zero() {
		$captured_ttl = null;

		add_filter(
			'woocommerce_version_string_generator_ttl',
			function ( $ttl ) use ( &$captured_ttl ) {
				$captured_ttl = $ttl; // Capture the default TTL passed to filter.
				return -100; // Return negative value to test conversion.
			}
		);

		$this->sut->generate_version( 'test-id-123' );

		$this->assertEquals( DAY_IN_SECONDS, $captured_ttl, 'Filter should receive default TTL' );

		$version = $this->sut->get_version( 'test-id-123', false );
		$this->assertNotNull( $version, 'Version should be stored even with negative TTL converted to 0' );
	}
}
