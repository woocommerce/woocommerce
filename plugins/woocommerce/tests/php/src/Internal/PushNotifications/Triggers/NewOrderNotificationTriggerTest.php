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

		$this->trigger = new NewOrderNotificationTrigger( $this->store );
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
	 * @testdox Should add a notification when a new order is created with a notifiable status.
	 */
	public function test_new_order_with_notifiable_status_adds_notification(): void {
		wc_create_order( array( 'status' => 'processing' ) );

		$this->assertSame( 1, $this->store->count(), 'Exactly one notification should be stored even though both hooks fire' );
	}

	/**
	 * @testdox Should not add a notification when a new order is created with a non-notifiable status.
	 */
	public function test_new_order_with_non_notifiable_status_is_ignored(): void {
		wc_create_order( array( 'status' => 'pending' ) );

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should add a notification when an order status changes to a notifiable status.
	 */
	public function test_status_change_to_notifiable_adds_notification(): void {
		$order = wc_create_order( array( 'status' => 'pending' ) );
		$this->assertSame( 0, $this->store->count() );

		$order->set_status( 'processing' );
		$order->save();

		$this->assertSame( 1, $this->store->count() );
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
	 * @testdox Should not add a notification when order status changes between two notifiable statuses.
	 */
	public function test_status_change_between_notifiable_statuses_is_ignored(): void {
		$order = $this->createMock( WC_Order::class );

		$this->trigger->on_order_status_changed( 1, 'processing', 'completed', $order );

		$this->assertSame( 0, $this->store->count() );
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
