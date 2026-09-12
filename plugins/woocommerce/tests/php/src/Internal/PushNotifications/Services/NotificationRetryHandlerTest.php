<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\WpcomNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationRetryHandler;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use stdClass;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the NotificationRetryHandler class.
 */
class NotificationRetryHandlerTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The System Under Test.
	 *
	 * @var NotificationRetryHandler
	 */
	private $sut;

	/**
	 * A test order ID.
	 *
	 * @var int
	 */
	private int $order_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut      = new NotificationRetryHandler();
		$this->order_id = wc_create_order( array( 'status' => 'processing' ) )->get_id();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		// Container first: a throw in the unschedule would otherwise leak the
		// replacement into every later test in the process.
		$this->reset_container_replacements();
		as_unschedule_all_actions( NotificationRetryHandler::RETRY_HOOK );
		parent::tearDown();
	}

	/**
	 * @testdox Should schedule retry with default backoff delay for attempt $attempt.
	 * @testWith [0, 1, 60]
	 *           [1, 2, 300]
	 *           [2, 3, 900]
	 *           [3, 4, 3600]
	 *
	 * @param int $current_attempt The attempt that just failed.
	 * @param int $expected_next   The expected next attempt number in the AS args.
	 * @param int $expected_delay  The expected delay in seconds.
	 */
	public function test_schedule_uses_default_backoff( int $current_attempt, int $expected_next, int $expected_delay ): void {
		$notification = new NewOrderNotification( $this->order_id );

		$this->sut->schedule( $notification, null, $current_attempt );

		$scheduled = as_next_scheduled_action(
			NotificationRetryHandler::RETRY_HOOK,
			array(
				'type'        => 'store_order',
				'resource_id' => $this->order_id,
				'attempt'     => $expected_next,
			),
			NotificationProcessor::ACTION_SCHEDULER_GROUP
		);

		$this->assertNotFalse( $scheduled, 'A retry action should be scheduled.' );
		$this->assertEqualsWithDelta( time() + $expected_delay, $scheduled, 2, 'Retry should be scheduled with the correct backoff delay.' );
	}

	/**
	 * @testdox Should use Retry-After value when provided instead of default backoff.
	 */
	public function test_schedule_respects_retry_after(): void {
		$notification = new NewOrderNotification( $this->order_id );

		$this->sut->schedule( $notification, 120, 0 );

		$scheduled = as_next_scheduled_action(
			NotificationRetryHandler::RETRY_HOOK,
			array(
				'type'        => 'store_order',
				'resource_id' => $this->order_id,
				'attempt'     => 1,
			),
			NotificationProcessor::ACTION_SCHEDULER_GROUP
		);

		$this->assertNotFalse( $scheduled );
		$this->assertEqualsWithDelta( time() + 120, $scheduled, 2, 'Retry should use the Retry-After delay.' );
	}

	/**
	 * @testdox Should log permanent failure and not schedule when max retries exceeded.
	 */
	public function test_schedule_logs_permanent_failure_after_max_retries(): void {
		$notification = new NewOrderNotification( $this->order_id );

		$this->sut->schedule( $notification, null, NotificationRetryHandler::MAX_RETRIES );

		$scheduled = as_next_scheduled_action(
			NotificationRetryHandler::RETRY_HOOK,
			array(
				'type'        => 'store_order',
				'resource_id' => $this->order_id,
				'attempt'     => NotificationRetryHandler::MAX_RETRIES + 1,
			),
			NotificationProcessor::ACTION_SCHEDULER_GROUP
		);

		$this->assertFalse( $scheduled, 'No retry should be scheduled after max retries.' );
		$this->assertLogged( 'error', 'permanently failed after 5 attempts', array( 'source' => PushNotifications::FEATURE_NAME ) );
	}

	/**
	 * @testdox Should clamp a negative current_attempt to zero and schedule attempt 1.
	 */
	public function test_schedule_clamps_negative_attempt(): void {
		$notification = new NewOrderNotification( $this->order_id );

		$this->sut->schedule( $notification, null, -3 );

		$scheduled = as_next_scheduled_action(
			NotificationRetryHandler::RETRY_HOOK,
			array(
				'type'        => 'store_order',
				'resource_id' => $this->order_id,
				'attempt'     => 1,
			),
			NotificationProcessor::ACTION_SCHEDULER_GROUP
		);

		$this->assertNotFalse( $scheduled, 'A retry action should be scheduled for attempt 1.' );
		$this->assertEqualsWithDelta( time() + 60, $scheduled, 2, 'Retry should use the attempt-1 backoff delay.' );
	}

	/**
	 * @testdox Should drop notification and log warning when retry delay exceeds the 24-hour cap.
	 */
	public function test_schedule_drops_notification_when_retry_after_exceeds_max(): void {
		$notification = new NewOrderNotification( $this->order_id );

		$this->sut->schedule( $notification, NotificationRetryHandler::MAX_RETRY_DELAY + 1, 0 );

		$scheduled = as_next_scheduled_action(
			NotificationRetryHandler::RETRY_HOOK,
			array(
				'type'        => 'store_order',
				'resource_id' => $this->order_id,
				'attempt'     => 1,
			),
			NotificationProcessor::ACTION_SCHEDULER_GROUP
		);

		$this->assertFalse( $scheduled, 'No retry should be scheduled when delay exceeds maximum.' );
		$this->assertLogged( 'warning', 'retry delay', array( 'source' => PushNotifications::FEATURE_NAME ) );
	}

	/**
	 * @testdox Should schedule retry when retry delay equals the 24-hour cap exactly.
	 */
	public function test_schedule_allows_retry_at_max_delay(): void {
		$notification = new NewOrderNotification( $this->order_id );

		$this->sut->schedule( $notification, NotificationRetryHandler::MAX_RETRY_DELAY, 0 );

		$scheduled = as_next_scheduled_action(
			NotificationRetryHandler::RETRY_HOOK,
			array(
				'type'        => 'store_order',
				'resource_id' => $this->order_id,
				'attempt'     => 1,
			),
			NotificationProcessor::ACTION_SCHEDULER_GROUP
		);

		$this->assertNotFalse( $scheduled, 'A retry should be scheduled when delay equals the maximum.' );
	}

	/**
	 * @testdox Should delegate to NotificationProcessor with is_retry and attempt on retry callback.
	 */
	public function test_handle_retry_delegates_to_processor(): void {
		$dispatcher          = $this->createMock( WpcomNotificationDispatcher::class );
		$data_store          = $this->createMock( PushTokensDataStore::class );
		$preferences_service = $this->createMock( NotificationPreferencesService::class );
		$retry_handler       = $this->createMock( NotificationRetryHandler::class );

		$preferences_service->method( 'get_preferences' )->willReturn(
			array( 'store_order' => array( 'enabled' => true ) )
		);

		$dispatcher->expects( $this->once() )->method( 'dispatch' )->willReturn(
			array(
				'success'     => true,
				'retry_after' => null,
			)
		);

		$data_store->method( 'get_tokens_for_roles' )->willReturn(
			array(
				new PushToken(
					array(
						'user_id'       => 1,
						'token'         => 'test-token',
						'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
						'platform'      => PushToken::PLATFORM_APPLE,
						'device_locale' => 'en_US',
						'device_uuid'   => 'test-uuid',
					)
				),
			)
		);

		$processor = new NotificationProcessor();
		$processor->init( $dispatcher, $data_store, $preferences_service, $retry_handler );
		wc_get_container()->replace( NotificationProcessor::class, $processor );

		$this->sut->handle_retry( 'store_order', $this->order_id, 2 );

		$order = wc_get_order( $this->order_id );
		$this->assertNotEmpty( $order->get_meta( NotificationProcessor::SENT_META_KEY ) );
	}

	/**
	 * @testdox Should catch and log exception when retry receives an unknown type.
	 */
	public function test_handle_retry_logs_error_for_unknown_type(): void {
		$this->sut->handle_retry( 'unknown_type', 1, 1 );

		$this->assertLogged( 'error', 'Retry failed:', array( 'source' => PushNotifications::FEATURE_NAME ) );
	}

	/**
	 * @testdox Should carry the stock event type in the scheduled retry arguments.
	 */
	public function test_schedule_carries_the_stock_event_type(): void {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new StockNotification( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );

		$this->sut->schedule( $notification, null, 0 );

		$scheduled = as_next_scheduled_action(
			NotificationRetryHandler::RETRY_HOOK,
			array(
				'type'        => 'store_stock',
				'resource_id' => $product->get_id(),
				'attempt'     => 1,
				'extra'       => array( 'event_type' => StockNotification::EVENT_OUT_OF_STOCK ),
			),
			NotificationProcessor::ACTION_SCHEDULER_GROUP
		);

		$this->assertNotFalse( $scheduled, 'The retry should record the event type it was scheduled for.' );
	}

	/**
	 * Action Scheduler deduplicates a unique action on its serialized arguments,
	 * so without the event type a second stock event for the same product would
	 * match the first and never be scheduled.
	 *
	 * @testdox Should schedule a separate retry for each stock event type on one product.
	 */
	public function test_schedule_keeps_a_separate_retry_per_stock_event_type(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->sut->schedule( new StockNotification( $product->get_id(), StockNotification::EVENT_LOW_STOCK ), null, 0 );
		$this->sut->schedule( new StockNotification( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK ), null, 0 );

		foreach ( array( StockNotification::EVENT_LOW_STOCK, StockNotification::EVENT_OUT_OF_STOCK ) as $event_type ) {
			$this->assertNotFalse(
				as_next_scheduled_action(
					NotificationRetryHandler::RETRY_HOOK,
					array(
						'type'        => 'store_stock',
						'resource_id' => $product->get_id(),
						'attempt'     => 1,
						'extra'       => array( 'event_type' => $event_type ),
					),
					NotificationProcessor::ACTION_SCHEDULER_GROUP
				),
				sprintf( 'A retry should be scheduled for %s.', $event_type )
			);
		}
	}

	/**
	 * The argument list stays byte-identical for types with no identity data, so
	 * a retry scheduled before this field existed still deduplicates against one
	 * scheduled after it.
	 *
	 * @testdox Should omit the extra argument for notification types with no identity data.
	 */
	public function test_schedule_omits_the_extra_argument_for_simple_types(): void {
		$notification = new NewOrderNotification( $this->order_id );

		$this->sut->schedule( $notification, null, 0 );

		$this->assertNotFalse(
			as_next_scheduled_action(
				NotificationRetryHandler::RETRY_HOOK,
				array(
					'type'        => 'store_order',
					'resource_id' => $this->order_id,
					'attempt'     => 1,
				),
				NotificationProcessor::ACTION_SCHEDULER_GROUP
			)
		);
	}

	/**
	 * @testdox Should rebuild the retried notification with the stock event type it was scheduled for.
	 */
	public function test_handle_retry_rebuilds_the_stock_event_type(): void {
		$product  = WC_Helper_Product::create_simple_product();
		$captured = $this->stub_processor_with_successful_dispatch();

		$this->sut->handle_retry(
			'store_stock',
			$product->get_id(),
			1,
			array( 'event_type' => StockNotification::EVENT_OUT_OF_STOCK )
		);

		$this->assertSame( StockNotification::EVENT_OUT_OF_STOCK, $captured->notification->get_event_type() );

		$refreshed = wc_get_product( $product->get_id() );
		$this->assertNotEmpty( $refreshed->get_meta( NotificationProcessor::SENT_META_KEY . '_out_of_stock' ) );
		$this->assertEmpty( $refreshed->get_meta( NotificationProcessor::SENT_META_KEY . '_low_stock' ) );
	}

	/**
	 * @testdox Should ignore type and resource_id keys smuggled into the extra argument.
	 */
	public function test_handle_retry_extra_cannot_override_positional_params(): void {
		$product  = WC_Helper_Product::create_simple_product();
		$captured = $this->stub_processor_with_successful_dispatch();

		$this->sut->handle_retry(
			'store_stock',
			$product->get_id(),
			1,
			array(
				'event_type'  => StockNotification::EVENT_LOW_STOCK,
				'type'        => 'store_order',
				'resource_id' => 999999,
			)
		);

		$this->assertSame( 'store_stock', $captured->notification->get_type() );
		$this->assertSame( $product->get_id(), $captured->notification->get_resource_id() );
		$this->assertSame( StockNotification::EVENT_LOW_STOCK, $captured->notification->get_event_type() );
	}

	/**
	 * Replaces the container's processor with one whose dispatcher reports
	 * success, so a retry runs end to end and the notification it was handed
	 * can be inspected.
	 *
	 * @return stdClass Holder whose `notification` property receives the dispatched notification.
	 */
	private function stub_processor_with_successful_dispatch(): stdClass {
		$captured               = new stdClass();
		$captured->notification = null;

		$dispatcher          = $this->createMock( WpcomNotificationDispatcher::class );
		$data_store          = $this->createMock( PushTokensDataStore::class );
		$preferences_service = $this->createMock( NotificationPreferencesService::class );

		$preferences_service->method( 'get_preferences' )->willReturn( array() );

		$dispatcher
			->expects( $this->once() )
			->method( 'dispatch' )
			->with(
				$this->callback(
					function ( $notification ) use ( $captured ) {
						$captured->notification = $notification;
						return true;
					}
				)
			)
			->willReturn(
				array(
					'success'     => true,
					'retry_after' => null,
				)
			);

		$data_store->method( 'get_tokens_for_roles' )->willReturn(
			array(
				new PushToken(
					array(
						'user_id'       => 1,
						'token'         => 'test-token',
						'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
						'platform'      => PushToken::PLATFORM_APPLE,
						'device_locale' => 'en_US',
						'device_uuid'   => 'test-uuid',
					)
				),
			)
		);

		$processor = new NotificationProcessor();
		$processor->init( $dispatcher, $data_store, $preferences_service, $this->createMock( NotificationRetryHandler::class ) );
		wc_get_container()->replace( NotificationProcessor::class, $processor );

		return $captured;
	}
}
