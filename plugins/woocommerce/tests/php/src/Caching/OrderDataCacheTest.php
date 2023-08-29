<?php

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caches\OrderDataCache;
use Automattic\WooCommerce\Caching\CacheException;

/**
 * Tests for the OrderDataCache class.
 */
class OrderDataCacheTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var OrderDataCache
	 */
	private OrderDataCache $sut;

	/**
	 * Runds before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( OrderDataCache::class );
		$this->sut->flush();
	}

	/**
	 * @testdox get_object_type returns "order_data".
	 */
	public function test_object_type_is_order_data() {
		$this->assertEquals( 'order_data', $this->sut->get_object_type() );
	}

	/**
	 * @testdox Arrays having an "order_id" key can be cached.
	 */
	public function test_arrays_with_order_id_key_can_be_cached() {
		$order_data = array( 'order_id' => 1234 );

		$this->sut->set( $order_data );

		$retrieved = $this->sut->get( 1234 );
		$this->assertSame( $order_data, $retrieved );
	}

	/**
	 * @testdox Arrays not having an "order_id" key can't be cached.
	 */
	public function test_arrays_without_order_id_key_can_be_cached() {
		$this->expectException( CacheException::class );

		$order_data = array( 'foobar' => 1234 );
		$this->sut->set( $order_data );
	}

	/**
	 * @testdox Objects can't be cached.
	 */
	public function test_objects_can_be_cached() {
		$this->expectException( CacheException::class );

		$order = new \WC_Order();
		$this->sut->set( $order );
	}
}
