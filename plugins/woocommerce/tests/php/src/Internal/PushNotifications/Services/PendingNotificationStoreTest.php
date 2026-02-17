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
		remove_all_actions( PendingNotificationStore::DISPATCH_HOOK );
		parent::tearDown();
	}

	/**
	 * @testdox Should add a notification to the store.
	 */
	public function test_add_stores_notification(): void {
		$notification = $this->create_notification( 'store_order', 42, 1 );

		$this->store->add( $notification );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should deduplicate notifications with the same type and resource ID.
	 */
	public function test_add_deduplicates_same_type_and_resource(): void {
		$first  = $this->create_notification( 'store_order', 42, 1 );
		$second = $this->create_notification( 'store_order', 42, 1 );

		$this->store->add( $first );
		$this->store->add( $second );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should store notifications with different types separately.
	 */
	public function test_add_allows_different_types_for_same_resource(): void {
		$order  = $this->create_notification( 'store_order', 42, 1 );
		$review = $this->create_notification( 'store_review', 42, 1 );

		$this->store->add( $order );
		$this->store->add( $review );

		$this->assertSame( 2, $this->store->count() );
	}

	/**
	 * @testdox Should store notifications with different resource IDs separately.
	 */
	public function test_add_allows_same_type_for_different_resources(): void {
		$order_1 = $this->create_notification( 'store_order', 42, 1 );
		$order_2 = $this->create_notification( 'store_order', 43, 1 );

		$this->store->add( $order_1 );
		$this->store->add( $order_2 );

		$this->assertSame( 2, $this->store->count() );
	}

	/**
	 * @testdox Should not add notifications when store has not been registered.
	 */
	public function test_add_does_nothing_when_not_registered(): void {
		$store = new PendingNotificationStore();

		$store->add( $this->create_notification( 'store_order', 42, 1 ) );

		$this->assertSame( 0, $store->count() );
	}

	/**
	 * @testdox Should register shutdown hook only once regardless of how many notifications are added.
	 */
	public function test_add_registers_shutdown_hook_once(): void {
		$this->store->add( $this->create_notification( 'store_order', 1, 1 ) );
		$this->store->add( $this->create_notification( 'store_order', 2, 1 ) );
		$this->store->add( $this->create_notification( 'store_order', 3, 1 ) );

		$hook_count = 0;

		global $wp_filter;

		if ( isset( $wp_filter['shutdown'] ) ) {
			foreach ( $wp_filter['shutdown']->callbacks as $priority => $callbacks ) {
				unset( $priority );

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
	 * @testdox Should fire dispatch action with all pending notifications.
	 */
	public function test_dispatch_all_fires_action_with_notifications(): void {
		$dispatched = array();

		add_action(
			PendingNotificationStore::DISPATCH_HOOK,
			function ( $notifications ) use ( &$dispatched ) {
				$dispatched = $notifications;
			}
		);

		$this->store->add( $this->create_notification( 'store_order', 1, 1 ) );
		$this->store->add( $this->create_notification( 'store_review', 2, 1 ) );

		$this->store->dispatch_all();

		$this->assertCount( 2, $dispatched );
		$this->assertSame( 'store_order', $dispatched[0]->get_type() );
		$this->assertSame( 'store_review', $dispatched[1]->get_type() );
	}

	/**
	 * @testdox Should clear pending notifications after dispatch.
	 */
	public function test_dispatch_all_clears_store(): void {
		$this->store->add( $this->create_notification( 'store_order', 1, 1 ) );

		$this->store->dispatch_all();

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should not fire dispatch action when store is empty.
	 */
	public function test_dispatch_all_does_nothing_when_empty(): void {
		$fired = false;

		add_action(
			PendingNotificationStore::DISPATCH_HOOK,
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		$this->store->dispatch_all();

		$this->assertFalse( $fired, 'Dispatch action should not fire when store is empty' );
	}

	/**
	 * @testdox Should return all pending notifications via get_all.
	 */
	public function test_get_all_returns_pending_notifications(): void {
		$this->store->add( $this->create_notification( 'store_order', 1, 1 ) );
		$this->store->add( $this->create_notification( 'store_review', 2, 1 ) );

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
	 * @param int    $blog_id     The blog ID.
	 * @return Notification
	 */
	private function create_notification( string $type, int $resource_id, int $blog_id = 1 ): Notification {
		return new class( $type, $resource_id, $blog_id ) extends Notification {
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
