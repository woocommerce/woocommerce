<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\InternalNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\WpcomNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewReviewNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationRetryHandler;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the NotificationProcessor class.
 */
class NotificationProcessorTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The System Under Test.
	 *
	 * @var NotificationProcessor
	 */
	private $sut;

	/**
	 * Mock WPCOM dispatcher.
	 *
	 * @var WpcomNotificationDispatcher
	 */
	private $dispatcher;

	/**
	 * Mock data store.
	 *
	 * @var PushTokensDataStore
	 */
	private $data_store;

	/**
	 * Mock preferences service.
	 *
	 * @var NotificationPreferencesService
	 */
	private $preferences_service;

	/**
	 * Mock retry handler.
	 *
	 * @var NotificationRetryHandler
	 */
	private $retry_handler;

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

		$this->dispatcher          = $this->createMock( WpcomNotificationDispatcher::class );
		$this->data_store          = $this->createMock( PushTokensDataStore::class );
		$this->preferences_service = $this->createMock( NotificationPreferencesService::class );
		$this->retry_handler       = $this->createMock( NotificationRetryHandler::class );
		$this->order_id            = wc_create_order( array( 'status' => 'processing' ) )->get_id();

		$this->sut = new NotificationProcessor();
		$this->sut->init( $this->dispatcher, $this->data_store, $this->preferences_service, $this->retry_handler );

		// By default every user has every notification type enabled, so existing
		// tests behave as before. Per-user/per-type filtering is exercised in
		// the dedicated preferences tests below.
		$this->preferences_service->method( 'get_preferences' )->willReturn(
			array(
				'store_order'  => array(
					'enabled'    => true,
					'min_amount' => null,
				),
				'store_review' => array( 'enabled' => true ),
				'store_stock'  => array(
					'enabled'      => true,
					'low_stock'    => true,
					'out_of_stock' => true,
					'on_backorder' => false,
				),
			)
		);

		$this->data_store->method( 'get_tokens_for_roles' )->willReturn(
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
	}

	/**
	 * @testdox Should return true, write sent meta, and clean up claimed meta on successful dispatch.
	 */
	public function test_process_writes_sent_meta_on_success(): void {
		$this->dispatcher->method( 'dispatch' )->willReturn(
			array(
				'success'     => true,
				'retry_after' => null,
			)
		);

		$notification = new NewOrderNotification( $this->order_id );
		$result       = $this->sut->process( $notification );

		$this->assertTrue( $result );

		$order = wc_get_order( $this->order_id );

		$this->assertNotEmpty( $order->get_meta( NotificationProcessor::SENT_META_KEY ) );
		$this->assertFalse( $notification->has_meta( NotificationProcessor::CLAIMED_META_KEY ) );
	}

	/**
	 * @testdox Should write claimed meta before sending.
	 */
	public function test_process_writes_claimed_meta_before_send(): void {
		$this->dispatcher->method( 'dispatch' )->willReturn(
			array(
				'success'     => false,
				'retry_after' => null,
			)
		);

		$notification = new NewOrderNotification( $this->order_id );

		$this->sut->process( $notification );

		$order = wc_get_order( $this->order_id );

		$this->assertNotEmpty( $order->get_meta( NotificationProcessor::CLAIMED_META_KEY ) );
	}

	/**
	 * @testdox Should skip processing when sent meta already exists.
	 */
	public function test_process_skips_when_already_sent(): void {
		$order = wc_get_order( $this->order_id );
		$order->update_meta_data( NotificationProcessor::SENT_META_KEY, (string) time() );
		$order->save_meta_data();

		$this->dispatcher->expects( $this->never() )->method( 'dispatch' );

		$notification = new NewOrderNotification( $this->order_id );
		$result       = $this->sut->process( $notification );

		$this->assertTrue( $result );
		$this->assertFalse( $notification->has_meta( NotificationProcessor::CLAIMED_META_KEY ) );
	}

	/**
	 * @testdox Should skip processing when claimed meta exists on first attempt.
	 */
	public function test_process_skips_on_first_attempt_when_already_claimed(): void {
		$order = wc_get_order( $this->order_id );

		$order->update_meta_data( NotificationProcessor::CLAIMED_META_KEY, (string) time() );
		$order->save_meta_data();

		$this->dispatcher->expects( $this->never() )->method( 'dispatch' );

		$notification = new NewOrderNotification( $this->order_id );
		$result       = $this->sut->process( $notification );

		$this->assertTrue( $result );
		$this->assertFalse( $notification->has_meta( NotificationProcessor::SENT_META_KEY ) );
	}

	/**
	 * @testdox Should proceed past claimed meta when is_retry is true.
	 */
	public function test_process_proceeds_past_claimed_on_retry(): void {
		$order = wc_get_order( $this->order_id );
		$order->update_meta_data( NotificationProcessor::CLAIMED_META_KEY, (string) time() );
		$order->save_meta_data();

		$this->dispatcher->expects( $this->once() )->method( 'dispatch' )->willReturn(
			array(
				'success'     => true,
				'retry_after' => null,
			)
		);

		$notification = new NewOrderNotification( $this->order_id );
		$result       = $this->sut->process( $notification, true );

		$this->assertTrue( $result );
		$this->assertFalse( $notification->has_meta( NotificationProcessor::CLAIMED_META_KEY ) );
	}

	/**
	 * @testdox Should return false and not write sent meta on dispatch failure.
	 */
	public function test_process_returns_false_on_failure(): void {
		$this->dispatcher->method( 'dispatch' )->willReturn(
			array(
				'success'     => false,
				'retry_after' => null,
			)
		);

		$notification = new NewOrderNotification( $this->order_id );
		$result       = $this->sut->process( $notification );

		$this->assertFalse( $result );
		$this->assertFalse( $notification->has_meta( NotificationProcessor::SENT_META_KEY ) );
	}

	/**
	 * @testdox Should mark as sent and return true when no tokens are available.
	 */
	public function test_process_marks_sent_when_no_tokens(): void {
		$data_store = $this->createMock( PushTokensDataStore::class );
		$data_store->method( 'get_tokens_for_roles' )->willReturn( array() );

		$this->dispatcher->expects( $this->never() )->method( 'dispatch' );

		$sut = new NotificationProcessor();
		$sut->init( $this->dispatcher, $data_store, $this->preferences_service, $this->retry_handler );

		$notification = new NewOrderNotification( $this->order_id );
		$result       = $sut->process( $notification );

		$this->assertTrue( $result );

		$order = wc_get_order( $this->order_id );

		$this->assertNotEmpty( $order->get_meta( NotificationProcessor::SENT_META_KEY ) );
	}

	/**
	 * @testdox Should mark as sent and skip dispatch when every owning user has the type disabled.
	 */
	public function test_process_skips_dispatch_when_all_users_opted_out(): void {
		$preferences_service = $this->createMock( NotificationPreferencesService::class );
		$preferences_service->method( 'get_preferences' )->willReturn(
			array(
				'store_order'  => array( 'enabled' => false ),
				'store_review' => array( 'enabled' => true ),
			)
		);

		$this->dispatcher->expects( $this->never() )->method( 'dispatch' );

		$sut = new NotificationProcessor();
		$sut->init( $this->dispatcher, $this->data_store, $preferences_service, $this->retry_handler );

		$notification = new NewOrderNotification( $this->order_id );
		$result       = $sut->process( $notification );

		$this->assertTrue( $result );

		$order = wc_get_order( $this->order_id );

		$this->assertNotEmpty( $order->get_meta( NotificationProcessor::SENT_META_KEY ) );
	}

	/**
	 * @testdox Should dispatch only to tokens whose owning user has the notification type enabled.
	 */
	public function test_process_filters_tokens_by_user_preferences(): void {
		$enabled_token  = new PushToken(
			array(
				'user_id'       => 1,
				'token'         => 'enabled-token',
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_locale' => 'en_US',
				'device_uuid'   => 'enabled-uuid',
			)
		);
		$disabled_token = new PushToken(
			array(
				'user_id'       => 2,
				'token'         => 'disabled-token',
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_ANDROID,
				'platform'      => PushToken::PLATFORM_ANDROID,
				'device_locale' => 'en_US',
				'device_uuid'   => 'disabled-uuid',
			)
		);

		$data_store = $this->createMock( PushTokensDataStore::class );
		$data_store->method( 'get_tokens_for_roles' )->willReturn(
			array( $enabled_token, $disabled_token )
		);

		$preferences_service = $this->createMock( NotificationPreferencesService::class );
		$preferences_service->method( 'get_preferences' )->willReturnCallback(
			function ( int $user_id ) {
				return array(
					'store_order'  => array( 'enabled' => 1 === $user_id ),
					'store_review' => array( 'enabled' => true ),
				);
			}
		);

		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch' )
			->with(
				$this->anything(),
				$this->callback(
					function ( array $tokens ) use ( $enabled_token ) {
						return 1 === count( $tokens ) && $tokens[0] === $enabled_token;
					}
				)
			)
			->willReturn(
				array(
					'success'     => true,
					'retry_after' => null,
				)
			);

		$sut = new NotificationProcessor();
		$sut->init( $this->dispatcher, $data_store, $preferences_service, $this->retry_handler );

		$notification = new NewOrderNotification( $this->order_id );
		$result       = $sut->process( $notification );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Should respect the notification type when filtering by preferences.
	 */
	public function test_process_respects_preferences_per_notification_type(): void {
		$preferences_service = $this->createMock( NotificationPreferencesService::class );
		$preferences_service->method( 'get_preferences' )->willReturn(
			array(
				'store_order'  => array( 'enabled' => true ),
				'store_review' => array( 'enabled' => false ),
			)
		);

		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch' )
			->willReturn(
				array(
					'success'     => true,
					'retry_after' => null,
				)
			);

		$sut = new NotificationProcessor();
		$sut->init( $this->dispatcher, $this->data_store, $preferences_service, $this->retry_handler );

		// store_order is enabled — should dispatch.
		$order_notification = new NewOrderNotification( $this->order_id );
		$this->assertTrue( $sut->process( $order_notification ) );

		// store_review is disabled — should mark sent without dispatching.
		$product             = WC_Helper_Product::create_simple_product();
		$comment_id          = wp_insert_comment(
			array(
				'comment_post_ID' => $product->get_id(),
				'comment_type'    => 'review',
				'comment_content' => 'Great!',
				'comment_author'  => 'Tester',
			)
		);
		$review_notification = new NewReviewNotification( $comment_id );

		$this->assertTrue( $sut->process( $review_notification ) );
		$this->assertNotEmpty(
			get_comment_meta( $comment_id, NotificationProcessor::SENT_META_KEY, true )
		);
	}

	/**
	 * @testdox Should look up preferences and decide once per user even when one user has multiple tokens.
	 */
	public function test_process_memoizes_filter_decision_per_user(): void {
		$tokens = array(
			new PushToken(
				array(
					'user_id'       => 7,
					'token'         => 'ios-token',
					'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
					'platform'      => PushToken::PLATFORM_APPLE,
					'device_locale' => 'en_US',
					'device_uuid'   => 'ios-uuid',
				)
			),
			new PushToken(
				array(
					'user_id'       => 7,
					'token'         => 'android-token',
					'origin'        => PushToken::ORIGIN_WOOCOMMERCE_ANDROID,
					'platform'      => PushToken::PLATFORM_ANDROID,
					'device_locale' => 'en_US',
					'device_uuid'   => 'android-uuid',
				)
			),
		);

		$data_store = $this->createMock( PushTokensDataStore::class );
		$data_store->method( 'get_tokens_for_roles' )->willReturn( $tokens );

		$preferences_service = $this->createMock( NotificationPreferencesService::class );
		// One user, two tokens — preferences must be read at most once for that user.
		$preferences_service->expects( $this->once() )
			->method( 'get_preferences' )
			->with( 7 )
			->willReturn( array( 'store_order' => array( 'enabled' => true ) ) );

		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch' )
			->with(
				$this->anything(),
				$this->callback(
					static function ( array $dispatched ) {
						return 2 === count( $dispatched );
					}
				)
			)
			->willReturn(
				array(
					'success'     => true,
					'retry_after' => null,
				)
			);

		$sut = new NotificationProcessor();
		$sut->init( $this->dispatcher, $data_store, $preferences_service, $this->retry_handler );

		$this->assertTrue( $sut->process( new NewOrderNotification( $this->order_id ) ) );
	}

	/**
	 * Locks the delegation contract: the processor must consult
	 * {@see Notification::should_send_to_user()} for the per-user decision and
	 * pass it the raw stored preference value (so parametrized prefs like
	 * `['enabled' => true, 'min_value' => 500]` reach the subclass intact
	 * once the storage layer is widened to support them).
	 *
	 * @testdox Should delegate the filter decision to the notification, passing the user's stored pref value.
	 */
	public function test_process_delegates_filter_decision_to_notification(): void {
		$pref_value = array(
			'enabled'   => true,
			'min_value' => 500,
		);

		$preferences_service = $this->createMock( NotificationPreferencesService::class );
		$preferences_service->method( 'get_preferences' )->willReturn(
			array( 'store_order' => $pref_value )
		);

		$notification = $this->getMockBuilder( Notification::class )
			->setConstructorArgs( array( $this->order_id ) )
			->onlyMethods(
				array(
					'get_type',
					'to_payload',
					'has_meta',
					'write_meta',
					'delete_meta',
					'should_send_to_user',
				)
			)
			->getMock();
		$notification->method( 'get_type' )->willReturn( 'store_order' );
		$notification->method( 'has_meta' )->willReturn( false );
		$notification->expects( $this->once() )
			->method( 'should_send_to_user' )
			->with( $this->equalTo( $pref_value ) )
			->willReturn( false );

		$this->dispatcher->expects( $this->never() )->method( 'dispatch' );

		$sut = new NotificationProcessor();
		$sut->init( $this->dispatcher, $this->data_store, $preferences_service, $this->retry_handler );

		$result = $sut->process( $notification );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Should write comment meta for review notifications.
	 */
	public function test_process_writes_comment_meta_for_reviews(): void {
		$this->dispatcher->method( 'dispatch' )->willReturn(
			array(
				'success'     => true,
				'retry_after' => null,
			)
		);

		$product    = WC_Helper_Product::create_simple_product();
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID' => $product->get_id(),
				'comment_type'    => 'review',
				'comment_content' => 'Great!',
				'comment_author'  => 'Tester',
			)
		);

		$notification = new NewReviewNotification( $comment_id );

		$this->sut->process( $notification );

		$this->assertNotEmpty( get_comment_meta( $comment_id, NotificationProcessor::SENT_META_KEY, true ) );
		$this->assertFalse( $notification->has_meta( NotificationProcessor::CLAIMED_META_KEY ) );
	}

	/**
	 * @testdox Should handle safety net callback by processing with is_retry true.
	 */
	public function test_handle_safety_net_processes_notification(): void {
		$this->dispatcher->expects( $this->once() )->method( 'dispatch' )->willReturn(
			array(
				'success'     => true,
				'retry_after' => null,
			)
		);

		$this->sut->handle_safety_net( 'store_order', $this->order_id );

		$order = wc_get_order( $this->order_id );

		$this->assertNotEmpty( $order->get_meta( NotificationProcessor::SENT_META_KEY ) );
	}

	/**
	 * @testdox Should schedule retry via handler on dispatch failure.
	 */
	public function test_process_schedules_retry_on_failure(): void {
		$this->dispatcher->method( 'dispatch' )->willReturn(
			array(
				'success'     => false,
				'retry_after' => 120,
			)
		);

		$this->retry_handler->expects( $this->once() )
			->method( 'schedule' )
			->with(
				$this->isInstanceOf( NewOrderNotification::class ),
				$this->equalTo( 120 ),
				$this->equalTo( 0 )
			);

		$notification = new NewOrderNotification( $this->order_id );
		$this->sut->process( $notification );
	}

	/**
	 * @testdox Should pass attempt number through to retry handler on failure.
	 */
	public function test_process_passes_attempt_to_retry_handler(): void {
		$this->dispatcher->method( 'dispatch' )->willReturn(
			array(
				'success'     => false,
				'retry_after' => null,
			)
		);

		$this->retry_handler->expects( $this->once() )
			->method( 'schedule' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( 3 )
			);

		$notification = new NewOrderNotification( $this->order_id );
		$this->sut->process( $notification, true, 3 );
	}

	/**
	 * @testdox Should cancel safety net after successful dispatch.
	 */
	public function test_process_cancels_safety_net_on_success(): void {
		$this->dispatcher->method( 'dispatch' )->willReturn(
			array(
				'success'     => true,
				'retry_after' => null,
			)
		);

		$notification = new NewOrderNotification( $this->order_id );

		// Seed the precondition through the REAL schedule path so the test setup
		// and production use the same arg shape; if the schedule shape ever drifts
		// from the cancel shape again, this assertion fails in CI.
		$this->schedule_safety_net( $notification );
		$this->assertTrue(
			$this->is_safety_net_pending( $notification ),
			'Safety net should be pending before processing.'
		);

		$this->sut->process( $notification );

		$this->assertFalse(
			$this->is_safety_net_pending( $notification ),
			'Safety net should be cancelled after successful send.'
		);
	}

	/**
	 * @testdox Should cancel safety net after failed dispatch with retry scheduled.
	 */
	public function test_process_cancels_safety_net_on_failure(): void {
		$this->dispatcher->method( 'dispatch' )->willReturn(
			array(
				'success'     => false,
				'retry_after' => null,
			)
		);

		$notification = new NewOrderNotification( $this->order_id );

		$this->schedule_safety_net( $notification );
		$this->assertTrue(
			$this->is_safety_net_pending( $notification ),
			'Safety net should be pending before processing.'
		);

		$this->sut->process( $notification );

		$this->assertFalse(
			$this->is_safety_net_pending( $notification ),
			'Safety net should be cancelled when retry is scheduled.'
		);
	}

	/**
	 * Locks the schedule/cancel arg-shape contract for the StockNotification
	 * subclass, whose identity (and therefore safety-net match key) includes
	 * `event_type`. Schedules via the real path, then cancels using a
	 * notification reconstructed from the serialized payload — exactly what the
	 * loopback controller does on the primary success path — to prove the
	 * round-tripped args still match.
	 *
	 * @testdox Should cancel a stock notification's safety net using a reconstructed notification.
	 */
	public function test_process_cancels_safety_net_for_stock_notification(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 0,
			)
		);

		$this->dispatcher->method( 'dispatch' )->willReturn(
			array(
				'success'     => true,
				'retry_after' => null,
			)
		);

		$notification = new StockNotification( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );

		$this->schedule_safety_net( $notification );
		$this->assertTrue(
			$this->is_safety_net_pending( $notification ),
			'Stock safety net should be pending before processing.'
		);

		// Reconstruct from the serialized payload, mirroring the loopback controller.
		$reconstructed = Notification::from_array( $notification->to_array() );
		$this->sut->process( $reconstructed );

		$this->assertFalse(
			$this->is_safety_net_pending( $notification ),
			'Stock safety net should be cancelled after successful send.'
		);
	}

	/**
	 * Schedules a safety-net action through the real
	 * {@see PendingNotificationStore::schedule_safety_net()} so tests exercise
	 * the production schedule shape rather than a hand-built fixture.
	 *
	 * @param Notification $notification The notification to schedule a safety net for.
	 * @return void
	 */
	private function schedule_safety_net( Notification $notification ): void {
		$store = new PendingNotificationStore();
		$store->init( $this->createMock( InternalNotificationDispatcher::class ) );

		$method = new \ReflectionMethod( PendingNotificationStore::class, 'schedule_safety_net' );
		$method->setAccessible( true );
		$method->invoke( $store, $notification );
	}

	/**
	 * Whether a safety-net action is currently scheduled for a notification,
	 * matched on its canonical args.
	 *
	 * @param Notification $notification The notification to check.
	 * @return bool
	 */
	private function is_safety_net_pending( Notification $notification ): bool {
		return as_has_scheduled_action(
			NotificationProcessor::SAFETY_NET_HOOK,
			$notification->get_safety_net_args(),
			NotificationProcessor::ACTION_SCHEDULER_GROUP
		);
	}

	/**
	 * @testdox Should not schedule retry on successful dispatch.
	 */
	public function test_process_does_not_retry_on_success(): void {
		$this->dispatcher->method( 'dispatch' )->willReturn(
			array(
				'success'     => true,
				'retry_after' => null,
			)
		);

		$this->retry_handler->expects( $this->never() )->method( 'schedule' );

		$notification = new NewOrderNotification( $this->order_id );
		$this->sut->process( $notification );
	}

	/**
	 * @testdox Should catch and log exception when safety net receives an unknown type.
	 */
	public function test_handle_safety_net_logs_error_for_unknown_type(): void {
		$this->dispatcher->expects( $this->never() )->method( 'dispatch' );

		$this->sut->handle_safety_net( 'unknown_type', 1 );

		$this->assertLogged( 'error', 'Safety net failed:', array( 'source' => PushNotifications::FEATURE_NAME ) );
	}

	/**
	 * @testdox Should skip dispatch when the order total is below the user's min_amount threshold.
	 */
	public function test_process_skips_dispatch_when_order_below_min_amount(): void {
		$order = wc_create_order( array( 'status' => 'processing' ) );
		$order->set_total( '100' );
		$order->save();

		$preferences_service = $this->createMock( NotificationPreferencesService::class );
		$preferences_service->method( 'get_preferences' )->willReturn(
			array(
				'store_order'  => array(
					'enabled'    => true,
					'min_amount' => 500,
				),
				'store_review' => array( 'enabled' => true ),
			)
		);

		$this->dispatcher->expects( $this->never() )->method( 'dispatch' );

		$sut = new NotificationProcessor();
		$sut->init( $this->dispatcher, $this->data_store, $preferences_service, $this->retry_handler );

		$notification = new NewOrderNotification( $order->get_id() );
		$result       = $sut->process( $notification );

		$this->assertTrue( $result );
		$this->assertNotEmpty(
			wc_get_order( $order->get_id() )->get_meta( NotificationProcessor::SENT_META_KEY )
		);
	}

	/**
	 * @testdox Should skip dispatch when the review rating is above the user's max_rating threshold.
	 */
	public function test_process_skips_dispatch_when_review_above_max_rating(): void {
		$product    = WC_Helper_Product::create_simple_product();
		$comment_id = WC_Helper_Product::create_product_review( $product->get_id() );
		update_comment_meta( $comment_id, 'rating', 5 );

		$preferences_service = $this->createMock( NotificationPreferencesService::class );
		$preferences_service->method( 'get_preferences' )->willReturn(
			array(
				'store_order'  => array(
					'enabled'    => true,
					'min_amount' => null,
				),
				'store_review' => array(
					'enabled'    => true,
					'max_rating' => 3,
				),
			)
		);

		$this->dispatcher->expects( $this->never() )->method( 'dispatch' );

		$sut = new NotificationProcessor();
		$sut->init( $this->dispatcher, $this->data_store, $preferences_service, $this->retry_handler );

		$notification = new NewReviewNotification( $comment_id );
		$result       = $sut->process( $notification );

		$this->assertTrue( $result );
		$this->assertNotEmpty( get_comment_meta( $comment_id, NotificationProcessor::SENT_META_KEY, true ) );
	}

	/**
	 * @testdox Should handle safety net callback with a stock notification event_type.
	 */
	public function test_handle_safety_net_with_stock_event_type(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 3,
			)
		);

		$this->dispatcher->expects( $this->once() )->method( 'dispatch' )->willReturn(
			array(
				'success'     => true,
				'retry_after' => null,
			)
		);

		$this->sut->handle_safety_net(
			'store_stock',
			$product->get_id(),
			array( 'event_type' => 'low_stock' )
		);

		$refreshed = wc_get_product( $product->get_id() );
		$this->assertNotEmpty(
			$refreshed->get_meta( NotificationProcessor::SENT_META_KEY . '_low_stock' )
		);
	}

	/**
	 * @testdox Safety net should default the extras array so notification types without subclass-specific state still work.
	 */
	public function test_handle_safety_net_omits_extras_for_simple_types(): void {
		$this->dispatcher->expects( $this->once() )->method( 'dispatch' )->willReturn(
			array(
				'success'     => true,
				'retry_after' => null,
			)
		);

		$this->sut->handle_safety_net( 'store_order', $this->order_id );

		$order = wc_get_order( $this->order_id );
		$this->assertNotEmpty( $order->get_meta( NotificationProcessor::SENT_META_KEY ) );
	}

	/**
	 * @testdox Safety net should ignore type/resource_id keys smuggled into the extras array so the positional params remain authoritative.
	 */
	public function test_handle_safety_net_extras_cannot_override_positional_params(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 3,
			)
		);

		$captured_notification = null;
		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch' )
			->with(
				$this->callback(
					function ( $notification ) use ( &$captured_notification ) {
						$captured_notification = $notification;
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

		// `type` and `resource_id` keys inside the extras array are smuggled values
		// that must be ignored — the positional params should remain authoritative.
		$this->sut->handle_safety_net(
			'store_stock',
			$product->get_id(),
			array(
				'event_type'  => 'low_stock',
				'type'        => 'store_order',
				'resource_id' => 999999,
			)
		);

		$this->assertNotNull( $captured_notification );
		$this->assertSame( 'store_stock', $captured_notification->get_type() );
		$this->assertSame( $product->get_id(), $captured_notification->get_resource_id() );
	}

	/**
	 * @testdox Safety net should propagate the stock quantity captured at trigger time when reconstructing the notification.
	 */
	public function test_handle_safety_net_with_stock_quantity_at_trigger(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				// Product currently shows stock=5 (the dispatcher might re-fetch and see this).
				'stock_quantity' => 5,
			)
		);

		$captured_payload = null;
		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch' )
			->with(
				$this->callback(
					function ( $notification ) use ( &$captured_payload ) {
						$captured_payload = $notification->to_payload();
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

		// Trigger-time stock was 1 (post-decrement), even though current product stock is 5.
		$this->sut->handle_safety_net(
			'store_stock',
			$product->get_id(),
			array(
				'event_type'                => 'low_stock',
				'stock_quantity_at_trigger' => 1,
			)
		);

		$this->assertNotNull( $captured_payload );
		$this->assertSame( '1', $captured_payload['message']['args'][1] );
	}

	/**
	 * @testdox Should skip dispatch for stock notification when the matching sub-flag is disabled.
	 */
	public function test_process_skips_dispatch_when_stock_sub_flag_disabled(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 3,
			)
		);

		$preferences_service = $this->createMock( NotificationPreferencesService::class );
		$preferences_service->method( 'get_preferences' )->willReturn(
			array(
				'store_order'  => array(
					'enabled'    => true,
					'min_amount' => null,
				),
				'store_review' => array(
					'enabled'    => true,
					'max_rating' => null,
				),
				'store_stock'  => array(
					'enabled'      => true,
					'low_stock'    => false,
					'out_of_stock' => true,
					'on_backorder' => false,
				),
			)
		);

		$this->dispatcher->expects( $this->never() )->method( 'dispatch' );

		$sut = new NotificationProcessor();
		$sut->init( $this->dispatcher, $this->data_store, $preferences_service, $this->retry_handler );

		$notification = new StockNotification( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$result       = $sut->process( $notification );

		$this->assertTrue( $result );
	}
}
