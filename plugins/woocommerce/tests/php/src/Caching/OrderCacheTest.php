<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class OrderCacheTest.
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
		$this->sut = new OrderCache();
	}

	/**
	 * Test that the order cache does not cause duplicate data storage.
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

}
