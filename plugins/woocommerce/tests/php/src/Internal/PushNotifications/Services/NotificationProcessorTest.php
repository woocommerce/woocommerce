<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\WpcomNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewReviewNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the NotificationProcessor class.
 */
class NotificationProcessorTest extends WC_Unit_Test_Case {

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
		$this->order_id            = wc_create_order( array( 'status' => 'processing' ) )->get_id();

		$this->sut = new NotificationProcessor();
		$this->sut->init( $this->dispatcher, $this->data_store, $this->preferences_service );

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
		$sut->init( $this->dispatcher, $data_store, $this->preferences_service );

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
		$sut->init( $this->dispatcher, $this->data_store, $preferences_service );

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
		$sut->init( $this->dispatcher, $data_store, $preferences_service );

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
		$sut->init( $this->dispatcher, $this->data_store, $preferences_service );

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
		$sut->init( $this->dispatcher, $data_store, $preferences_service );

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
		$sut->init( $this->dispatcher, $this->data_store, $preferences_service );

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
		$sut->init( $this->dispatcher, $this->data_store, $preferences_service );

		$notification = new NewOrderNotification( $order->get_id() );
		$result       = $sut->process( $notification );

		$this->assertTrue( $result );
		$this->assertNotEmpty(
			wc_get_order( $order->get_id() )->get_meta( NotificationProcessor::SENT_META_KEY )
		);
	}
}
