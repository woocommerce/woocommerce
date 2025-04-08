<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Tests\Caching;

use WC_Helper_Order;
use Automattic\WooCommerce\Caches\OrderCountCache;
use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class OrderCountCacheTest.
 */
class OrderCountCacheServiceTest extends \WC_Unit_Test_Case {

	/**
	 * OrderCache instance.
	 *
	 * @var OrderCache
	 */
	private $order_cache;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->order_cache = new OrderCountCache();
		$this->order_cache->flush();
	}

	/**
	 * Test that count gets incremented on new orders.
	 */
	public function test_count_incremented_on_order_create() {
		$initial_count = OrderUtil::get_count_for_type( 'shop_order' )[ OrderInternalStatus::PENDING ];

		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderInternalStatus::PENDING );
		$order->save();

		$counts = OrderUtil::get_count_for_type( 'shop_order' );

		$this->assertEquals( $initial_count + 1, $counts[ OrderInternalStatus::PENDING ] );
	}

	/**
	 * Test that order count gets reduced when order is deleted.
	 */
	public function test_count_decremented_on_order_delete() {
		$initial_count = OrderUtil::get_count_for_type( 'shop_order' );

		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderInternalStatus::PENDING );
		$order->save();
		$order->delete( true );

		$counts = OrderUtil::get_count_for_type( 'shop_order' );

		$this->assertEquals( $initial_count[ OrderInternalStatus::PENDING ], $counts[ OrderInternalStatus::PENDING ] );
	}

	/**
	 * Test that order counts get updated respectively when changing an order status.
	 */
	public function test_count_on_order_status_change() {
		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderInternalStatus::PENDING );
		$order->save();

		$initial_count = OrderUtil::get_count_for_type( 'shop_order' );

		$order->set_status( OrderInternalStatus::COMPLETED );
		$order->save();

		$count = OrderUtil::get_count_for_type( 'shop_order' );

		$this->assertEquals( $initial_count[ OrderInternalStatus::PENDING ] - 1, $count[ OrderInternalStatus::PENDING ] );
		$this->assertEquals( $initial_count[ OrderInternalStatus::COMPLETED ] + 1, $count[ OrderInternalStatus::COMPLETED ] );
	}
}
