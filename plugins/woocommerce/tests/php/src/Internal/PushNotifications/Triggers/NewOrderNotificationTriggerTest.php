<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Triggers;

use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\NewOrderNotificationTrigger;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the NewOrderNotificationTrigger class.
 */
class NewOrderNotificationTriggerTest extends WC_Unit_Test_Case {
	/**
	 * An instance of NewOrderNotificationTrigger.
	 *
	 * @var NewOrderNotificationTrigger
	 */
	private $trigger;

	/**
	 * The notification store used by the trigger.
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

		wc_get_container()->replace( PendingNotificationStore::class, $this->store );
		wc_get_container()->reset_all_resolved();

		$this->trigger = new NewOrderNotificationTrigger();
		$this->trigger->init( $this->store );
		$this->trigger->register();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'woocommerce_new_order', array( $this->trigger, 'on_new_order' ) );
		remove_action( 'woocommerce_order_status_changed', array( $this->trigger, 'on_order_status_changed' ) );
		remove_action( 'shutdown', array( $this->store, 'dispatch_all' ) );

		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();

		parent::tearDown();
	}

	/**
	 * @testdox Should register the woocommerce_new_order hook.
	 */
	public function test_register_adds_new_order_hook(): void {
		$this->assertNotFalse(
			has_action(
				'woocommerce_new_order',
				array( $this->trigger, 'on_new_order' )
			),
			'woocommerce_new_order hook should be registered'
		);
	}

	/**
	 * @testdox Should register the woocommerce_order_status_changed hook.
	 */
	public function test_register_adds_status_changed_hook(): void {
		$this->assertNotFalse(
			has_action(
				'woocommerce_order_status_changed',
				array( $this->trigger, 'on_order_status_changed' )
			),
			'woocommerce_order_status_changed hook should be registered'
		);
	}

	/**
	 * @testdox Should add a notification when a new order has a notifiable status.
	 */
	public function test_on_new_order_adds_notification_for_notifiable_status(): void {
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_status' )->willReturn( 'processing' );

		$this->trigger->on_new_order( 1, $order );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should not add a notification when a new order has a non-notifiable status.
	 */
	public function test_on_new_order_ignores_non_notifiable_status(): void {
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_status' )->willReturn( 'pending' );

		$this->trigger->on_new_order( 1, $order );

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should add a notification when order status changes to a notifiable status.
	 */
	public function test_on_order_status_changed_adds_notification_for_notifiable_status(): void {
		$order = $this->createMock( WC_Order::class );

		$this->trigger->on_order_status_changed( 1, 'pending', 'processing', $order );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should not add a notification when order status changes to a non-notifiable status.
	 */
	public function test_on_order_status_changed_ignores_non_notifiable_status(): void {
		$order = $this->createMock( WC_Order::class );

		$this->trigger->on_order_status_changed( 1, 'pending', 'cancelled', $order );

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should deduplicate notifications for the same order across hooks.
	 */
	public function test_same_order_deduplicated_across_hooks(): void {
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_status' )->willReturn( 'processing' );

		$this->trigger->on_new_order( 1, $order );
		$this->trigger->on_order_status_changed( 1, 'pending', 'processing', $order );

		$this->assertSame( 1, $this->store->count(), 'Same order should be deduplicated' );
	}

	/**
	 * @testdox Should accept all notifiable statuses.
	 * @dataProvider notifiable_statuses_provider
	 *
	 * @param string $status The order status.
	 */
	public function test_all_notifiable_statuses_accepted( string $status ): void {
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_status' )->willReturn( $status );

		$this->trigger->on_new_order( 1, $order );

		$this->assertSame( 1, $this->store->count(), "Status '$status' should be notifiable" );
	}

	/**
	 * Data provider for all notifiable statuses.
	 *
	 * @return array<string, array{string}>
	 */
	public function notifiable_statuses_provider(): array {
		return array(
			'processing'      => array( 'processing' ),
			'on-hold'         => array( 'on-hold' ),
			'completed'       => array( 'completed' ),
			'pre-order'       => array( 'pre-order' ),
			'pre-ordered'     => array( 'pre-ordered' ),
			'partial-payment' => array( 'partial-payment' ),
		);
	}
}
