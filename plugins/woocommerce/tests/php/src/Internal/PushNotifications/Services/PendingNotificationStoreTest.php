<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\InternalNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Stubs\StubOrderNotification;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Stubs\StubReviewNotification;
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
		$this->store->add( new StubOrderNotification( 42 ) );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should deduplicate notifications with the same type and resource ID.
	 */
	public function test_add_deduplicates_same_type_and_resource(): void {
		$this->store->add( new StubOrderNotification( 42 ) );
		$this->store->add( new StubOrderNotification( 42 ) );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should store notifications with different types separately.
	 */
	public function test_add_allows_different_types_for_same_resource(): void {
		$this->store->add( new StubOrderNotification( 42 ) );
		$this->store->add( new StubReviewNotification( 42 ) );

		$this->assertSame( 2, $this->store->count() );
	}

	/**
	 * @testdox Should store notifications with different resource IDs separately.
	 */
	public function test_add_allows_same_type_for_different_resources(): void {
		$this->store->add( new StubOrderNotification( 42 ) );
		$this->store->add( new StubOrderNotification( 43 ) );

		$this->assertSame( 2, $this->store->count() );
	}

	/**
	 * @testdox Should not add notifications when store has not been registered.
	 */
	public function test_add_does_nothing_when_not_registered(): void {
		$dispatcher = $this->createMock( InternalNotificationDispatcher::class );
		$store      = new PendingNotificationStore();
		$store->init( $dispatcher );

		$store->add( new StubOrderNotification( 42 ) );

		$this->assertSame( 0, $store->count() );
	}

	/**
	 * @testdox Should register shutdown hook only once regardless of how many notifications are added.
	 */
	public function test_add_registers_shutdown_hook_once(): void {
		$this->store->add( new StubOrderNotification( 1 ) );
		$this->store->add( new StubOrderNotification( 2 ) );
		$this->store->add( new StubOrderNotification( 3 ) );

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
		$this->store->add( new StubOrderNotification( 1 ) );

		$this->store->dispatch_all();

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should return all pending notifications via get_all.
	 */
	public function test_get_all_returns_pending_notifications(): void {
		$this->store->add( new StubOrderNotification( 1 ) );
		$this->store->add( new StubReviewNotification( 2 ) );

		$all = $this->store->get_all();

		$this->assertCount( 2, $all );
		$this->assertSame( 1, $all[0]->get_resource_id() );
		$this->assertSame( 2, $all[1]->get_resource_id() );
	}
}
