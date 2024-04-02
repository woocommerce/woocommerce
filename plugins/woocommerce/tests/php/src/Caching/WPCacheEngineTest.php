<?php

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\WPCacheEngine;

/**
 * Tests for WPCacheEngine.
 */
class WPCacheEngineTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var WPCacheEngine
	 */
	private $sut;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WPCacheEngine::class );
	}

	/**
	 * @testDox Test that the cache engine can be used to cache and retrieve objects.
	 */
	public function test_caching_crud() {
		$object_to_cache         = new \stdClass();
		$object_to_cache->prop1  = 'dummy_value_1';
		$object_to_cache->prop2  = 'dummy_value_2';
		$key                     = 'dummy_object_key';
		$object_to_cache2        = new \stdClass();
		$object_to_cache2->prop1 = 'dummy2_value_1';
		$object_to_cache2->prop2 = 'dummy2_value_2';
		$key2                    = 'dummy2_object_key';
		$group                   = 'dummy_group';

		$this->assertFalse( $this->sut->is_cached( $key, $group ) );
		$this->sut->cache_object( $key, $object_to_cache, HOUR_IN_SECONDS, $group );
		$this->assertTrue( $this->sut->is_cached( $key, $group ) );
		$this->assertFalse( $this->sut->is_cached( $key2, $group ) );

		$cached_obj = $this->sut->get_cached_object( $key, $group );
		$this->assertEquals( $object_to_cache->prop1, $cached_obj->prop1 );
		$this->assertEquals( $object_to_cache->prop2, $cached_obj->prop2 );

		$this->sut->delete_cached_object( $key, $group );
		$this->assertFalse( $this->sut->is_cached( $key, $group ) );
		$this->assertNull( ( $this->sut->get_cached_object( $key, $group ) ) );

		$this->sut->cache_object( $key, $object_to_cache, HOUR_IN_SECONDS, $group );
		$this->assertTrue( $this->sut->is_cached( $key, $group ) );
		$this->sut->delete_cache_group( $group );
		$this->assertFalse( $this->sut->is_cached( $key, $group ) );
		$this->assertNull( ( $this->sut->get_cached_object( $group ) ) );

		$this->sut->cache_objects(
			[
				$key  => $cached_obj,
				$key2 => $object_to_cache2
			],
			HOUR_IN_SECONDS,
			$group
		);
		$cached_obj = $this->sut->get_cached_object( $key, $group );
		$this->assertEquals( $object_to_cache->prop1, $cached_obj->prop1 );
		$this->assertEquals( $object_to_cache->prop2, $cached_obj->prop2 );
		$cached_obj2 = $this->sut->get_cached_object( $key2, $group );
		$this->assertEquals( $object_to_cache2->prop1, $cached_obj2->prop1 );
		$this->assertEquals( $object_to_cache2->prop2, $cached_obj2->prop2 );

		$cached_objects = $this->sut->get_cached_objects( [ $key, $key2 ], $group );
		$this->assertArrayHasKey( $key, $cached_objects );
		$this->assertEquals( $object_to_cache->prop1, $cached_objects[ $key ]->prop1 );
		$this->assertEquals( $object_to_cache->prop2, $cached_objects[ $key ]->prop2 );
		$this->assertArrayHasKey( $key2, $cached_objects );
		$this->assertEquals( $object_to_cache2->prop1, $cached_objects[ $key2 ]->prop1 );
		$this->assertEquals( $object_to_cache2->prop2, $cached_objects[ $key2 ]->prop2 );
	}

}
