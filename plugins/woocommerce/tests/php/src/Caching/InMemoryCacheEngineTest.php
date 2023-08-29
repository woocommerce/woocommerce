<?php

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caches\OrderDataCache;

/**
 * Tests for the InMemoryCacheEngine class.
 */
class InMemoryCacheEngineTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var InMemoryCacheEngine
	 */
	public InMemoryCacheEngine $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new InMemoryCacheEngine();
	}

	/**
	 * @testdox Test that the cache_object is_cached and get_cached_object methods work as expected.
	 */
	public function test_cache_object_is_cached_and_get_cached_object() {
		$this->sut->cache_object( 'key1', 'object1_1', 0, 'group1' );
		$this->sut->cache_object( 'key2', 'object2_1', 0, 'group1' );
		$this->sut->cache_object( 'key1', 'object1_2', 0, 'group2' );

		$this->assertTrue( $this->sut->is_cached( 'key1', 'group1' ) );
		$this->assertEquals( 'object1_1', $this->sut->get_cached_object( 'key1', 'group1' ) );

		$this->assertTrue( $this->sut->is_cached( 'key2', 'group1' ) );
		$this->assertEquals( 'object2_1', $this->sut->get_cached_object( 'key2', 'group1' ) );

		$this->assertTrue( $this->sut->is_cached( 'key1', 'group2' ) );
		$this->assertEquals( 'object1_2', $this->sut->get_cached_object( 'key1', 'group2' ) );

		$this->assertFalse( $this->sut->is_cached( 'key2', 'group2' ) );
		$this->assertNull( $this->sut->get_cached_object( 'key2', 'group2' ) );
	}

	/**
	 * @testdox Test that the delete_cached_object method works as expected.
	 */
	public function test_delete_cached_object() {
		$this->sut->cache_object( 'key1', 'object1_1', 0, 'group1' );
		$this->sut->cache_object( 'key2', 'object2_1', 0, 'group1' );
		$this->sut->cache_object( 'key1', 'object1_2', 0, 'group2' );

		$success = $this->sut->delete_cached_object( 'key1', 'group1' );
		$this->assertTrue( $success );

		$success = $this->sut->delete_cached_object( 'key1', 'FOOBAR' );
		$this->assertFalse( $success );

		$this->assertFalse( $this->sut->is_cached( 'key1', 'group1' ) );
		$this->assertTrue( $this->sut->is_cached( 'key2', 'group1' ) );
		$this->assertTrue( $this->sut->is_cached( 'key1', 'group2' ) );
	}

	/**
	 * @testdox Test that the delete_cache_group method works as expected (and always returns true).
	 */
	public function test_delete_cache_group() {
		$this->sut->cache_object( 'key1', 'object1_1', 0, 'group1' );
		$this->sut->cache_object( 'key2', 'object2_1', 0, 'group1' );
		$this->sut->cache_object( 'key1', 'object1_2', 0, 'group2' );

		$success = $this->sut->delete_cache_group( 'group1' );
		$this->assertTrue( $success );

		$success = $this->sut->delete_cache_group( 'FOOBAR' );
		$this->assertTrue( $success );

		$this->assertFalse( $this->sut->is_cached( 'key1', 'group1' ) );
		$this->assertFalse( $this->sut->is_cached( 'key2', 'group1' ) );
		$this->assertTrue( $this->sut->is_cached( 'key1', 'group2' ) );
	}
}
