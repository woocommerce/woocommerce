<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\VersionStringGenerator;
use WC_Unit_Test_Case;

/**
 * Tests for VersionStringGenerator.
 */
class VersionStringGeneratorTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var VersionStringGenerator
	 */
	private $sut;

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
