<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Triggers;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\InternalNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationStepLogger;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\NewOrderNotificationTrigger;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Helpers\PushNotificationsTestTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Exception;
use WC_Logger;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the NewOrderNotificationTrigger class.
 */
class NewOrderNotificationTriggerTest extends WC_Unit_Test_Case {

	use PushNotificationsTestTrait;

	/**
	 * A custom order status, like those fulfilment plugins add.
	 */
	private const CUSTOM_STATUS = 'in-production';

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

		$dispatcher  = $this->createMock( InternalNotificationDispatcher::class );
		$this->store = new PendingNotificationStore();

		$this->store->init( $dispatcher, $this->create_data_store_with_tokens( true ), $this->createMock( NotificationStepLogger::class ) );
		$this->store->register();

		wc_get_container()->replace( PendingNotificationStore::class, $this->store );
		wc_get_container()->reset_all_resolved();

		$this->trigger = new NewOrderNotificationTrigger();
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
	 * @testdox Should notify about an order leaving a custom status only when it was not created before push notifications were enabled.
	 * @testWith [60, 0]
	 *           [0, 1]
	 *           [-60, 1]
	 *
	 * @param int $enabled_offset Seconds between the order's creation and enablement.
	 * @param int $expected_count The number of notifications expected.
	 */
	public function test_status_change_from_custom_status_respects_enablement_time( int $enabled_offset, int $expected_count ): void {
		$order = $this->create_order_in_custom_status();
		update_option( PushNotifications::ENABLED_AT_OPTION, $order->get_date_created()->getTimestamp() + $enabled_offset );

		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->assertSame( $expected_count, $this->store->count(), "Enablement {$enabled_offset}s after the order was created" );
	}

	/**
	 * @testdox Should notify about an order created before push notifications were enabled when it moves out of an unpaid status.
	 * @testWith ["pending"]
	 *           ["failed"]
	 *           ["checkout-draft"]
	 *
	 * @param string $previous_status The unpaid status the order leaves.
	 */
	public function test_status_change_from_unpaid_status_notifies_order_predating_enablement( string $previous_status ): void {
		$order = wc_create_order();
		update_option( PushNotifications::ENABLED_AT_OPTION, $order->get_date_created()->getTimestamp() + 60 );

		$this->trigger->on_order_status_changed( $order->get_id(), $previous_status, OrderStatus::PROCESSING, $order );

		$this->assertSame( 1, $this->store->count(), "A payment arriving on a '{$previous_status}' order should still be announced" );
	}

	/**
	 * @testdox Should log the skipped notification when the order predates enablement.
	 */
	public function test_status_change_on_order_predating_enablement_logs_step(): void {
		$step_logger = $this->createMock( NotificationStepLogger::class );
		wc_get_container()->replace( NotificationStepLogger::class, $step_logger );

		$order = $this->create_order_in_custom_status();
		update_option( PushNotifications::ENABLED_AT_OPTION, $order->get_date_created()->getTimestamp() + 60 );

		$step_logger->expects( $this->once() )
			->method( 'log_notification_step' )
			->with( $this->isInstanceOf( NewOrderNotification::class ), 'triggered', 'predates_push_notifications' );

		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->assertSame( 0, $this->store->count(), 'The skipped order should not be queued' );
	}

	/**
	 * @testdox Should add a notification when the order has no creation date.
	 */
	public function test_status_change_on_order_without_creation_date_adds_notification(): void {
		update_option( PushNotifications::ENABLED_AT_OPTION, time() );
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_date_created' )->willReturn( null );

		$this->trigger->on_order_status_changed( 1, self::CUSTOM_STATUS, OrderStatus::COMPLETED, $order );

		$this->assertSame( 1, $this->store->count(), 'An order without a creation date cannot be shown to predate enablement' );
	}

	/**
	 * @testdox Should mark an order the first time it reaches a notifiable status.
	 */
	public function test_status_change_to_notifiable_status_marks_order(): void {
		$order = wc_create_order( array( 'status' => OrderStatus::PENDING ) );

		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		$this->assertTrue( wc_get_order( $order->get_id() )->meta_exists( NewOrderNotificationTrigger::NOTIFIABLE_META_KEY ), 'The order should be marked as notifiable' );
	}

	/**
	 * @testdox Should notify about a new order that a plugin completes from the processing status hook.
	 */
	public function test_new_order_completed_from_processing_hook_adds_notification(): void {
		add_action(
			'woocommerce_order_status_processing',
			static function ( $order_id, $order ) {
				unset( $order_id ); // Avoid parameter not used PHPCS errors.
				$order->update_status( OrderStatus::COMPLETED );
			},
			10,
			2
		);
		$order = wc_create_order( array( 'status' => OrderStatus::PENDING ) );

		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		$this->assertSame( OrderStatus::COMPLETED, wc_get_order( $order->get_id() )->get_status(), 'The plugin should have completed the order' );
		$this->assertSame( 1, $this->store->count(), 'The nested processing to completed change should not hide the new order' );
	}

	/**
	 * @testdox Should not mark an order moving between two notifiable statuses.
	 * @testWith ["on_order_status_changed"]
	 *           ["on_order_status_changed_while_disabled"]
	 *
	 * @param string $handler The trigger callback that handles the status change.
	 */
	public function test_status_change_between_notifiable_statuses_does_not_mark_order( string $handler ): void {
		$order = wc_create_order( array( 'status' => OrderStatus::PENDING ) );

		$this->trigger->{$handler}( $order->get_id(), OrderStatus::PROCESSING, OrderStatus::COMPLETED, $order );

		$this->assertFalse( wc_get_order( $order->get_id() )->meta_exists( NewOrderNotificationTrigger::NOTIFIABLE_META_KEY ), "{$handler} should only mark an order moving into a notifiable status" );
	}

	/**
	 * @testdox Should not notify about an order that already reached a notifiable status, and should log why.
	 */
	public function test_status_change_on_previously_notifiable_order_is_ignored(): void {
		$step_logger = $this->createMock( NotificationStepLogger::class );
		wc_get_container()->replace( NotificationStepLogger::class, $step_logger );

		$order = $this->create_order_in_custom_status();
		$order->update_meta_data( NewOrderNotificationTrigger::NOTIFIABLE_META_KEY, '1000' );
		$order->save_meta_data();
		update_option( PushNotifications::ENABLED_AT_OPTION, $order->get_date_created()->getTimestamp() - 60 );

		$step_logger->expects( $this->once() )
			->method( 'log_notification_step' )
			->with( $this->isInstanceOf( NewOrderNotification::class ), 'triggered', 'previously_notifiable' );

		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->assertSame( 0, $this->store->count(), 'An order marked while push notifications were off should not be announced as new' );
	}

	/**
	 * @testdox Should mark an order reaching a notifiable status while push notifications are off, without queuing a notification.
	 */
	public function test_status_change_while_disabled_marks_order_without_notifying(): void {
		$order = $this->create_order_in_custom_status();

		$this->trigger->on_order_status_changed_while_disabled( $order->get_id(), self::CUSTOM_STATUS, OrderStatus::COMPLETED, $order );

		$this->assertTrue( wc_get_order( $order->get_id() )->meta_exists( NewOrderNotificationTrigger::NOTIFIABLE_META_KEY ), 'The order should be marked as notifiable' );
		$this->assertSame( 0, $this->store->count(), 'Nothing should be queued while push notifications are off' );
	}

	/**
	 * @testdox Should not mark an order moving to a non-notifiable status while push notifications are off.
	 */
	public function test_status_change_while_disabled_ignores_non_notifiable_status(): void {
		$order = wc_create_order( array( 'status' => OrderStatus::PENDING ) );

		$this->trigger->on_order_status_changed_while_disabled( $order->get_id(), OrderStatus::PENDING, OrderStatus::CANCELLED, $order );

		$this->assertFalse( wc_get_order( $order->get_id() )->meta_exists( NewOrderNotificationTrigger::NOTIFIABLE_META_KEY ), 'A cancelled order should not be marked' );
	}

	/**
	 * @testdox Should mark the order on HPOS without a full order save, so woocommerce_update_order does not fire.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $sync_enabled Whether HPOS and posts data sync is on.
	 */
	public function test_marking_order_does_not_fire_update_order_hook( bool $sync_enabled ): void {
		$previous_hpos = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		add_filter(
			'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION,
			static function () use ( $sync_enabled ) {
				return wc_bool_to_string( $sync_enabled );
			}
		);
		OrderHelper::toggle_cot_feature_and_usage( true );

		try {
			$order = wc_create_order( array( 'status' => OrderStatus::PENDING ) );
			// HPOS saves the whole order after a meta change unless the order was modified in the current second.
			$order->set_date_modified( time() - HOUR_IN_SECONDS );
			$order->save();
			$update_order_count = 0;
			add_action(
				'woocommerce_update_order',
				function () use ( &$update_order_count ) {
					++$update_order_count;
				}
			);

			$this->trigger->on_order_status_changed_while_disabled( $order->get_id(), OrderStatus::PENDING, OrderStatus::PROCESSING, $order );

			$this->assertTrue( wc_get_order( $order->get_id() )->meta_exists( NewOrderNotificationTrigger::NOTIFIABLE_META_KEY ), 'The order should be marked as notifiable' );
			$this->assertSame( 0, $update_order_count, 'Marking the order should not fire woocommerce_update_order' );
			if ( $sync_enabled ) {
				$this->assertNotEmpty( get_post_meta( $order->get_id(), NewOrderNotificationTrigger::NOTIFIABLE_META_KEY, true ), 'The marker should be copied to the posts table when sync is on' );
			}
		} finally {
			OrderHelper::toggle_cot_feature_and_usage( $previous_hpos );
		}
	}

	/**
	 * @testdox Should log, not throw, when the order cannot be marked.
	 */
	public function test_marking_failure_is_logged_not_thrown(): void {
		$logger = $this->createMock( WC_Logger::class );
		$logger->expects( $this->once() )
			->method( 'error' )
			->with( $this->stringContains( 'Could not mark order' ), array( 'source' => PushNotifications::FEATURE_NAME ) );
		$this->register_legacy_proxy_function_mocks( array( 'wc_get_logger' => fn () => $logger ) );

		$order = $this->createMock( WC_Order::class );
		$order->method( 'save_meta_data' )->willThrowException( new Exception( 'Database unavailable' ) );

		$this->trigger->on_order_status_changed_while_disabled( 1, self::CUSTOM_STATUS, OrderStatus::COMPLETED, $order );
	}

	/**
	 * Creates an order in a custom, non-notifiable status registered for the test.
	 *
	 * @return WC_Order
	 */
	private function create_order_in_custom_status(): WC_Order {
		add_filter(
			'wc_order_statuses',
			static function ( array $statuses ): array {
				$statuses[ 'wc-' . self::CUSTOM_STATUS ] = 'In Production';
				return $statuses;
			}
		);

		return wc_create_order( array( 'status' => self::CUSTOM_STATUS ) );
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
