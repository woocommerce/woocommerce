<?php

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Caches\OrderDataCache;
use Automattic\WooCommerce\Caching\CacheException;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
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

		$this->sut = wc_get_container()->get( OrderCache::class );
		$this->sut->flush();
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
	 * @testdox get_data throws an exception if there's no order with the specified id.
	 */
	public function test_get_data_throws_if_order_not_cached() {
		$this->expectException( CacheException::class );

		$this->sut->get_data(1, 'the_key');
	}

	/**
	 * @testdox set_data throws an exception if there's no order with the specified id.
	 */
	public function test_set_data_throws_if_order_not_cached() {
		$this->expectException( CacheException::class );

		$this->sut->set_data(1, 'the_key', 'the_value');
	}

	/**
	 * @testdox get_data and set_data preserve the cached order object.
	 */
	public function test_get_and_set_data_preserve_the_order_object() {
		$order = new WC_Order();
		$order->set_id( 1234 );
		$this->sut->set( $order );

		$this->sut->set_data(1234, 'the_data', 'the_key');
		$this->sut->get_data(1234, 'the_data');

		$retrieved = $this->sut->get( 1234 );
		$this->assertEquals( $order->get_id(), $retrieved->get_id() );
	}

	/**
	 * @testdox get_data returns null if there's no data cached with the specified key and no default value is provided.
	 */
	public function test_get_data_returns_null_for_not_cached_data_and_no_default_provided() {
		$order = new WC_Order();
		$order->set_id( 1234 );
		$this->sut->set( $order );

		$retrieved = $this->sut->get_data(1234, 'the_data');
		$this->assertNull($retrieved);
	}

	/**
	 * @testdox get_data returns the provided default value if there's no data cached with the specified key.
	 */
	public function test_get_data_returns_provided_default_for_not_cached_data() {
		$order = new WC_Order();
		$order->set_id( 1234 );
		$this->sut->set( $order );

		$retrieved = $this->sut->get_data(1234, 'the_data', 'the_default');
		$this->assertEquals('the_default', $retrieved);
	}

	/**
	 * @testdox get_data and set_data work together consistently (get_data will return what was set with set_data).
	 */
	public function test_get_and_set_data_work_consistently() {
		$order1 = new WC_Order();
		$order1->set_id( 1 );
		$this->sut->set( $order1 );

		$order2 = new WC_Order();
		$order2->set_id( 2 );
		$this->sut->set( $order2 );

		$this->sut->set_data(1, 'foo', 'foo_1');
		$this->sut->set_data(1, 'bar', 'bar_1');
		$this->sut->set_data(2, 'foo', 'foo_2');
		$this->sut->set_data(2, 'bar', 'bar_2');

		$this->assertEquals('foo_1', $this->sut->get_data(1, 'foo'));
		$this->assertEquals('bar_1', $this->sut->get_data(1, 'bar'));
		$this->assertEquals('foo_2', $this->sut->get_data(2, 'foo'));
		$this->assertEquals('bar_2', $this->sut->get_data(2, 'bar'));
	}

	/**
	 * @testdox remove_all_data uncaches all the data cached for an order, while preserving the order itself in the cache.
	 */
	public function test_remove_all_data() {
		$order = new WC_Order();
		$order->set_id( 1234 );
		$this->sut->set( $order );

		$this->sut->set_data(1234, 'foo', 'foo_data');
		$this->sut->set_data(1234, 'bar', 'bar_data');
		$this->assertEquals('foo_data', $this->sut->get_data(1234, 'foo'));
		$this->assertEquals('bar_data', $this->sut->get_data(1234, 'bar'));

		$this->sut->remove_all_data(1234);

		$this->assertNull($this->sut->get_data(1234, 'foo'));
		$this->assertNull($this->sut->get_data(1234, 'bar'));
	}
}
