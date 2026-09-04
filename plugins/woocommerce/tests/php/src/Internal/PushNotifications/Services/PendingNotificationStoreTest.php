<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\InternalNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewReviewNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the PendingNotificationStore class.
 */
class PendingNotificationStoreTest extends WC_Unit_Test_Case {

	/**
	 * An instance of PendingNotificationStore.
	 *
	 * @var PendingNotificationStore
	 */
	private $store;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$dispatcher  = $this->createMock( InternalNotificationDispatcher::class );
		$this->store = new PendingNotificationStore();

		$this->store->init( $dispatcher );
		$this->store->register();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'shutdown', array( $this->store, 'dispatch_all' ) );
		parent::tearDown();
	}

	/**
	 * @testdox Should add a notification to the store.
	 */
	public function test_add_stores_notification(): void {
		$this->store->add( $this->create_order_mock( 42 ) );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * Adding must not write to the resource. The write would otherwise land
	 * inside the order-creation call stack, where an HPOS meta save can
	 * escalate into a full order save before line items exist.
	 *
	 * @testdox Should not write to the resource when a notification is added.
	 */
	public function test_add_does_not_write_to_the_resource(): void {
		$notification = $this->create_order_mock( 42 );
		$notification->expects( $this->never() )
			->method( 'write_meta' );

		$this->store->add( $notification );
	}

	/**
	 * Capturing the time is what makes the recorded value the moment the store
	 * event fired rather than the moment of dispatch. Asserted on `add()` itself
	 * because the write happens later, on shutdown.
	 *
	 * @testdox Should capture the trigger time when a notification is added.
	 */
	public function test_add_captures_the_trigger_time(): void {
		$notification = $this->create_order_mock( 42 );

		$this->assertNull( $notification->get_triggered_at() );

		$this->store->add( $notification );

		$this->assertEqualsWithDelta( time(), $notification->get_triggered_at(), 10 );
	}

	/**
	 * @testdox Should record the trigger time on the resource when dispatching.
	 */
	public function test_dispatch_all_records_trigger_time(): void {
		$order = WC_Helper_Order::create_order();

		$this->store->add( new NewOrderNotification( $order->get_id() ) );
		$this->store->dispatch_all();

		$recorded = (int) wc_get_order( $order->get_id() )->get_meta( NotificationProcessor::TRIGGERED_META_KEY );

		$this->assertEqualsWithDelta( time(), $recorded, 10 );
	}

	/**
	 * The captured time is what gets persisted, so a request that spends time
	 * between the store event and shutdown still records the earlier moment
	 * rather than the moment of the write.
	 *
	 * @testdox Should record the time the event fired, not the time of dispatch.
	 */
	public function test_dispatch_all_records_the_captured_time_not_the_dispatch_time(): void {
		$order        = WC_Helper_Order::create_order();
		$notification = new NewOrderNotification( $order->get_id() );

		$this->store->add( $notification );
		$notification->set_triggered_at( time() - 300 );
		$this->store->dispatch_all();

		$recorded = (int) wc_get_order( $order->get_id() )->get_meta( NotificationProcessor::TRIGGERED_META_KEY );

		$this->assertEqualsWithDelta( time() - 300, $recorded, 10 );
	}

	/**
	 * A repeat trigger for an already-sent notification (e.g.
	 * `woocommerce_low_stock` firing on every reduction below the threshold)
	 * must not pay for a meta write that nothing will read.
	 *
	 * @testdox Should not record a trigger time for a notification already sent.
	 */
	public function test_dispatch_all_skips_notifications_already_sent(): void {
		$order        = WC_Helper_Order::create_order();
		$notification = new NewOrderNotification( $order->get_id() );
		$notification->write_meta( NotificationProcessor::SENT_META_KEY );

		$this->store->add( $notification );
		$this->store->dispatch_all();

		$recorded = wc_get_order( $order->get_id() )->get_meta( NotificationProcessor::TRIGGERED_META_KEY );

		$this->assertSame( '', $recorded );
	}

	/**
	 * @testdox Should deduplicate notifications with the same type and resource ID.
	 */
	public function test_add_deduplicates_same_type_and_resource(): void {
		$this->store->add( $this->create_order_mock( 42 ) );
		$this->store->add( $this->create_order_mock( 42 ) );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should store notifications with different types separately.
	 */
	public function test_add_allows_different_types_for_same_resource(): void {
		$this->store->add( $this->create_order_mock( 42 ) );
		$this->store->add( $this->create_review_mock( 42 ) );

		$this->assertSame( 2, $this->store->count() );
	}

	/**
	 * @testdox Should store notifications with different resource IDs separately.
	 */
	public function test_add_allows_same_type_for_different_resources(): void {
		$this->store->add( $this->create_order_mock( 42 ) );
		$this->store->add( $this->create_order_mock( 43 ) );

		$this->assertSame( 2, $this->store->count() );
	}

	/**
	 * @testdox Should not add notifications when store has not been registered.
	 */
	public function test_add_does_nothing_when_not_registered(): void {
		$dispatcher = $this->createMock( InternalNotificationDispatcher::class );
		$store      = new PendingNotificationStore();
		$store->init( $dispatcher );

		$store->add( $this->create_order_mock( 42 ) );

		$this->assertSame( 0, $store->count() );
	}

	/**
	 * @testdox Should register shutdown hook only once regardless of how many notifications are added.
	 */
	public function test_add_registers_shutdown_hook_once(): void {
		$this->store->add( $this->create_order_mock( 1 ) );
		$this->store->add( $this->create_order_mock( 2 ) );
		$this->store->add( $this->create_order_mock( 3 ) );

		$hook_count = 0;

		global $wp_filter;

		if ( isset( $wp_filter['shutdown'] ) ) {
			foreach ( $wp_filter['shutdown']->callbacks as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					if ( is_array( $callback['function'] ) && $callback['function'][0] === $this->store ) {
						++$hook_count;
					}
				}
			}
		}

		$this->assertSame( 1, $hook_count, 'Shutdown hook should be registered exactly once' );
	}

	/**
	 * @testdox Should clear pending notifications after dispatch.
	 */
	public function test_dispatch_all_clears_store(): void {
		$this->store->add( $this->create_order_mock( 1 ) );

		$this->store->dispatch_all();

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should call the dispatcher when dispatching pending notifications.
	 */
	public function test_dispatch_all_calls_dispatcher(): void {
		$dispatcher = $this->createMock( InternalNotificationDispatcher::class );
		$dispatcher->expects( $this->once() )
			->method( 'dispatch' );

		$store = new PendingNotificationStore();
		$store->init( $dispatcher );
		$store->register();
		$store->add( $this->create_order_mock( 1 ) );

		$store->dispatch_all();

		remove_action( 'shutdown', array( $store, 'dispatch_all' ) );
	}

	/**
	 * @testdox Should not call the dispatcher when there are no pending notifications.
	 */
	public function test_dispatch_all_does_not_call_dispatcher_when_empty(): void {
		$dispatcher = $this->createMock( InternalNotificationDispatcher::class );
		$dispatcher->expects( $this->never() )
			->method( 'dispatch' );

		$store = new PendingNotificationStore();
		$store->init( $dispatcher );
		$store->register();

		$store->dispatch_all();
	}

	/**
	 * @testdox Should return all pending notifications via get_all.
	 */
	public function test_get_all_returns_pending_notifications(): void {
		$this->store->add( $this->create_order_mock( 1 ) );
		$this->store->add( $this->create_review_mock( 2 ) );

		$all = $this->store->get_all();

		$this->assertCount( 2, $all );
		$this->assertSame( 1, $all[0]->get_resource_id() );
		$this->assertSame( 2, $all[1]->get_resource_id() );
	}

	/**
	 * Creates a mock NewOrderNotification that avoids database calls.
	 *
	 * @param int $resource_id The resource ID.
	 * @return NewOrderNotification
	 */
	private function create_order_mock( int $resource_id ): NewOrderNotification {
		return $this->getMockBuilder( NewOrderNotification::class )
			->setConstructorArgs( array( $resource_id ) )
			->onlyMethods( array( 'to_payload', 'has_meta', 'write_meta' ) )
			->getMock();
	}

	/**
	 * Creates a mock NewReviewNotification that avoids database calls.
	 *
	 * @param int $resource_id The resource ID.
	 * @return NewReviewNotification
	 */
	private function create_review_mock( int $resource_id ): NewReviewNotification {
		return $this->getMockBuilder( NewReviewNotification::class )
			->setConstructorArgs( array( $resource_id ) )
			->onlyMethods( array( 'to_payload', 'has_meta', 'write_meta' ) )
			->getMock();
	}

	/**
	 * Creates a mock StockNotification that avoids database calls.
	 *
	 * @param int    $resource_id The resource ID.
	 * @param string $event_type  The stock event type.
	 * @return StockNotification
	 */
	private function create_stock_mock( int $resource_id, string $event_type ): StockNotification {
		return $this->getMockBuilder( StockNotification::class )
			->setConstructorArgs( array( $resource_id, $event_type ) )
			->onlyMethods( array( 'to_payload', 'has_meta', 'write_meta' ) )
			->getMock();
	}

	/**
	 * @testdox Should store different stock event types for the same product separately.
	 */
	public function test_add_allows_different_stock_event_types_for_same_product(): void {
		$this->store->add( $this->create_stock_mock( 42, StockNotification::EVENT_LOW_STOCK ) );
		$this->store->add( $this->create_stock_mock( 42, StockNotification::EVENT_OUT_OF_STOCK ) );

		$this->assertSame( 2, $this->store->count() );
	}

	/**
	 * @testdox Should deduplicate the same stock event type for the same product.
	 */
	public function test_add_deduplicates_same_stock_event_type_and_product(): void {
		$this->store->add( $this->create_stock_mock( 42, StockNotification::EVENT_LOW_STOCK ) );
		$this->store->add( $this->create_stock_mock( 42, StockNotification::EVENT_LOW_STOCK ) );

		$this->assertSame( 1, $this->store->count() );
	}
}
