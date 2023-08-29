<?php

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Caches\OrderDataCache;
use Automattic\WooCommerce\Caching\CacheException;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Tests\Caching\InMemoryCacheEngine;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests for the OrderCache class..
 */
class OrderCacheTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var OrderCache
	 */
	private $sut;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_object_cache_get_engine', fn()=>new InMemoryCacheEngine() );
		$this->sut = wc_get_container()->get( OrderCache::class );
		$this->sut->flush();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'wc_object_cache_get_engine' );
	}

	/**
	 * @testdox The order cache does not cause duplicate data storage.
	 */
	public function test_meta_is_not_duplicated_when_cached() {
		global $wpdb;

		if ( ! OrderUtil::orders_cache_usage_is_enabled() ) {
			// tip: add HPOS=1 env variable to run this test.
			$this->markTestSkipped( 'HPOS based caching is not enabled.' );
		}
		$order = WC_Helper_Order::create_order();
		$order->add_meta_data( 'test', 'test' );
		$order->save_meta_data();

		$order = wc_get_order( $order->get_id() );
		$this->assertTrue( $this->sut->is_cached( $order->get_id() ), 'Order was not cached, but it was expected to be cached. Are you sure that HPOS based caching is enabled.' );

		$order2 = wc_get_order( $order->get_id() );
		$order2->save_meta_data();

		$orders_meta_table = OrdersTableDataStore::get_meta_table_name();
		$query             = $wpdb->prepare( "SELECT id FROM $orders_meta_table WHERE order_id = %d AND meta_key = %s", $order->get_id(), 'test' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertEquals( 1, count( $wpdb->get_col( $query ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Already prepared query.
	}

	/**
	 * @testdox get_object_type returns "orders".
	 */
	public function test_object_type_is_orders() {
		$this->assertEquals( 'orders', $this->sut->get_object_type() );
	}

	/**
	 * @testdox Objects derived from WC_Abstract_Order can be cached.
	 */
	public function test_objects_derived_from_abstract_order_class_can_be_cached() {
		$order = new class() extends \WC_Abstract_Order {};
		$order->set_id( 1234 );

		$this->sut->set( $order );

		$retrieved = $this->sut->get( 1234 );
		$this->assertEquals( $order->get_id(), $retrieved->get_id() );
	}

	/**
	 * @testdox Objects not derived from WC_Abstract_Order can't be cached.
	 */
	public function test_objects_not_derived_from_abstract_order_class_can_not_be_cached() {
		$this->expectException( CacheException::class );

		$order = new class() {
			// phpcs:disable Squiz.Commenting.FunctionComment.Missing
			public function get_id() {
				return 1234;
			}
			// phpcs:enable Squiz.Commenting.FunctionComment.Missing
		};
		$this->sut->set( $order );
	}

	/**
	 * @testdox "set" deletes associated order data from the orders data cache.
	 */
	public function test_set_deletes_cached_order_data() {
		$this->subtest_deletion_of_order_data(
			function() {
				$order = new \WC_Order();
				$order->set_id( 1234 );
				$this->sut->set( $order );
			}
		);
	}

	/**
	 * @testdox "update" deletes associated order data from the orders data cache.
	 */
	public function test_update_deletes_cached_order_data() {
		$this->subtest_deletion_of_order_data(
			function() {
				$order = new \WC_Order();
				$order->set_id( 1234 );
				$this->sut->update_if_cached( $order );
			}
		);
	}

	/**
	 * @testdox "remove" deletes associated order data from the orders data cache.
	 */
	public function test_remove_deletes_cached_order_data() {
		$this->subtest_deletion_of_order_data(
			function() {
				$this->sut->remove( 1234 );
			}
		);
	}

	/**
	 * @testdox "flush" deletes associated order data from the orders data cache.
	 */
	public function test_flush_deletes_cached_order_data() {
		$this->subtest_deletion_of_order_data(
			function() {
				$this->sut->flush();
			}
		);
	}

	/**
	 * Auxiliary method to run the order data deletion tests.
	 *
	 * @param callable $cache_operation The callback to execute once the order data is cached.
	 */
	private function subtest_deletion_of_order_data( callable $cache_operation ) {
		$order = new \WC_Order();
		$order->set_id( 1234 );
		$this->sut->set( $order );

		$order_data_cache = wc_get_container()->get( OrderDataCache::class );
		$order_data       = array( 'order_id' => 1234 );
		$order_data_cache->set( $order_data );
		$this->assertTrue( $order_data_cache->is_cached( 1234 ) );

		$cache_operation();

		$this->assertFalse( $order_data_cache->is_cached( 1234 ) );
	}
}
