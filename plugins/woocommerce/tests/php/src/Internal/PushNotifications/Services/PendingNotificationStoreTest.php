<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
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

		$this->store = new PendingNotificationStore();
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
		$notification = $this->create_notification( 'store_order', 42 );

		$this->store->add( $notification );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should deduplicate notifications with the same type and resource ID.
	 */
	public function test_add_deduplicates_same_type_and_resource(): void {
		$first  = $this->create_notification( 'store_order', 42 );
		$second = $this->create_notification( 'store_order', 42 );

		$this->store->add( $first );
		$this->store->add( $second );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should store notifications with different types separately.
	 */
	public function test_add_allows_different_types_for_same_resource(): void {
		$order  = $this->create_notification( 'store_order', 42 );
		$review = $this->create_notification( 'store_review', 42 );

		$this->store->add( $order );
		$this->store->add( $review );

		$this->assertSame( 2, $this->store->count() );
	}

	/**
	 * @testdox Should store notifications with different resource IDs separately.
	 */
	public function test_add_allows_same_type_for_different_resources(): void {
		$order_1 = $this->create_notification( 'store_order', 42 );
		$order_2 = $this->create_notification( 'store_order', 43 );

		$this->store->add( $order_1 );
		$this->store->add( $order_2 );

		$this->assertSame( 2, $this->store->count() );
	}

	/**
	 * @testdox Should not add notifications when store has not been registered.
	 */
	public function test_add_does_nothing_when_not_registered(): void {
		$store = new PendingNotificationStore();

		$store->add( $this->create_notification( 'store_order', 42 ) );

		$this->assertSame( 0, $store->count() );
	}

	/**
	 * @testdox Should register shutdown hook only once regardless of how many notifications are added.
	 */
	public function test_add_registers_shutdown_hook_once(): void {
		$this->store->add( $this->create_notification( 'store_order', 1 ) );
		$this->store->add( $this->create_notification( 'store_order', 2 ) );
		$this->store->add( $this->create_notification( 'store_order', 3 ) );

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
		$this->store->add( $this->create_notification( 'store_order', 1 ) );

		$this->store->dispatch_all();

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should return all pending notifications via get_all.
	 */
	public function test_get_all_returns_pending_notifications(): void {
		$this->store->add( $this->create_notification( 'store_order', 1 ) );
		$this->store->add( $this->create_notification( 'store_review', 2 ) );

		$all = $this->store->get_all();

		$this->assertCount( 2, $all );
		$this->assertSame( 1, $all[0]->get_resource_id() );
		$this->assertSame( 2, $all[1]->get_resource_id() );
	}

	/**
	 * Creates a concrete Notification instance for testing.
	 *
	 * @param string $type        The notification type.
	 * @param int    $resource_id The resource ID.
	 * @return Notification
	 */
	private function create_notification( string $type, int $resource_id ): Notification {
		return new class( $type, $resource_id ) extends Notification {
			/**
			 * Returns a test payload.
			 *
			 * @return array
			 */
			public function to_payload(): array {
				return array( 'test' => true );
			}
		};
	}
}
