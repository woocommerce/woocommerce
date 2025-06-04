<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Tests\Caching;

use WC_Helper_Order;
use WC_Order;
use Automattic\WooCommerce\Caches\OrderCountCache;
use Automattic\WooCommerce\Caches\OrderCountCacheService;
use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\OrderStatus;
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

	/**
	 * Test that count gets incremented on new orders with initial status and does not incorrectly decrement the pending count.
	 */
	public function test_count_on_new_order_with_initial_status() {
		$initial_count = OrderUtil::get_count_for_type( 'shop_order' );
		$order_data    = array(
			'status'        => OrderStatus::COMPLETED,
			'customer_id'   => 1,
			'customer_note' => '',
			'total'         => '',
		);

		$order = wc_create_order( $order_data );

		$count = OrderUtil::get_count_for_type( 'shop_order' );

		$this->assertEquals( $initial_count[ OrderInternalStatus::PENDING ], $count[ OrderInternalStatus::PENDING ] );
		$this->assertEquals( $initial_count[ OrderInternalStatus::COMPLETED ] + 1, $count[ OrderInternalStatus::COMPLETED ] );
	}

	/**
	 * Test that count works when status change hook is triggered on new orders.
	 */
	public function test_count_on_new_order_with_status_change() {
		$initial_count = OrderUtil::get_count_for_type( 'shop_order' );

		$order = new WC_Order();
		$order->set_status( OrderStatus::CANCELLED );
		$order->save();

		$count = OrderUtil::get_count_for_type( 'shop_order' );

		$this->assertEquals( $initial_count[ OrderInternalStatus::CANCELLED ] + 1, $count[ OrderInternalStatus::CANCELLED ] );
		$this->assertEquals( $initial_count[ OrderInternalStatus::PENDING ], $count[ OrderInternalStatus::PENDING ] );
	}

	/**
	 * Test that count works when status change hook is triggered on multiple status changes.
	 */
	public function test_count_on_multiple_status_changes() {
		$initial_count = OrderUtil::get_count_for_type( 'shop_order' );

		$order = new WC_Order();
		$order->set_status( OrderInternalStatus::COMPLETED );
		$order->set_status( OrderInternalStatus::CANCELLED );
		$order->save();

		$count = OrderUtil::get_count_for_type( 'shop_order' );

		$this->assertEquals( $initial_count[ OrderInternalStatus::PENDING ], $count[ OrderInternalStatus::PENDING ] );
		$this->assertEquals( $initial_count[ OrderInternalStatus::COMPLETED ], $count[ OrderInternalStatus::COMPLETED ] );
		$this->assertEquals( $initial_count[ OrderInternalStatus::CANCELLED ] + 1, $count[ OrderInternalStatus::CANCELLED ] );
	}

	/**
	 * Test that background actions are scheduled.
	 */
	public function test_background_actions_scheduled() {
		$order_count_cache_service = wc_get_container()->get( OrderCountCacheService::class );
		$order_count_cache_service->schedule_background_actions();
		$this->assertTrue( as_has_scheduled_action( 'woocommerce_refresh_order_count_cache' ) );
	}

	/**
	 * Test that refresh cache works.
	 */
	public function test_refresh_cache() {
		$count         = OrderUtil::get_count_for_type( 'shop_order' );
		$pending_count = $count[ OrderInternalStatus::PENDING ];
		// Set the pending count to a higher value to ensure it is refreshed.
		$this->order_cache->set( 'shop_order', OrderInternalStatus::PENDING, $pending_count + 10 );

		$order_count_cache_service = wc_get_container()->get( OrderCountCacheService::class );
		$order_count_cache_service->refresh_cache( 'shop_order' );

		$this->assertSame( $pending_count, $this->order_cache->get( 'shop_order', array( OrderInternalStatus::PENDING ) )[ OrderInternalStatus::PENDING ] );
	}
}
