<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\InternalNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewReviewNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
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

		$this->store->init( $dispatcher, $this->create_data_store_with_tokens( true ) );
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
		$store->init( $dispatcher, $this->create_data_store_with_tokens( true ) );

		$store->add( $this->create_order_mock( 42 ) );

		$this->assertSame( 0, $store->count() );
	}

	/**
	 * @testdox Should not add notifications when the store has no registered push tokens.
	 */
	public function test_add_does_nothing_when_no_tokens_registered(): void {
		$dispatcher = $this->createMock( InternalNotificationDispatcher::class );
		$store      = new PendingNotificationStore();
		$store->init( $dispatcher, $this->create_data_store_with_tokens( false ) );
		$store->register();

		$store->add( $this->create_order_mock( 42 ) );

		$this->assertSame( 0, $store->count() );
	}

	/**
	 * @testdox Should not schedule a safety net when the store has no registered push tokens.
	 */
	public function test_add_does_not_schedule_safety_net_when_no_tokens_registered(): void {
		$notification = $this->create_order_mock( 42 );

		$dispatcher = $this->createMock( InternalNotificationDispatcher::class );
		$store      = new PendingNotificationStore();
		$store->init( $dispatcher, $this->create_data_store_with_tokens( false ) );
		$store->register();

		$store->add( $notification );

		$this->assertFalse(
			as_has_scheduled_action(
				NotificationProcessor::SAFETY_NET_HOOK,
				$notification->get_safety_net_args(),
				NotificationProcessor::ACTION_SCHEDULER_GROUP
			)
		);
	}

	/**
	 * @testdox Should not register the shutdown hook when the store has no registered push tokens.
	 */
	public function test_add_does_not_register_shutdown_hook_when_no_tokens_registered(): void {
		$dispatcher = $this->createMock( InternalNotificationDispatcher::class );
		$store      = new PendingNotificationStore();
		$store->init( $dispatcher, $this->create_data_store_with_tokens( false ) );
		$store->register();

		$store->add( $this->create_order_mock( 42 ) );

		$this->assertFalse( has_action( 'shutdown', array( $store, 'dispatch_all' ) ) );
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
		$store->init( $dispatcher, $this->create_data_store_with_tokens( true ) );
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
		$store->init( $dispatcher, $this->create_data_store_with_tokens( true ) );
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

	/**
	 * Creates a push tokens data store whose has_tokens() returns $has_tokens.
	 *
	 * @param bool $has_tokens What has_tokens() should report.
	 * @return PushTokensDataStore
	 */
	private function create_data_store_with_tokens( bool $has_tokens ): PushTokensDataStore {
		$data_store = $this->createMock( PushTokensDataStore::class );
		$data_store->method( 'has_tokens' )->willReturn( $has_tokens );

		return $data_store;
	}
}
