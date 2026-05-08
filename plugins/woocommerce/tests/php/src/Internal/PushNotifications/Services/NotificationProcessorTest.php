<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\WpcomNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewReviewNotification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
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

		$this->dispatcher = $this->createMock( WpcomNotificationDispatcher::class );
		$this->data_store = $this->createMock( PushTokensDataStore::class );
		$this->order_id   = wc_create_order( array( 'status' => 'processing' ) )->get_id();

		$this->sut = new NotificationProcessor();
		$this->sut->init( $this->dispatcher, $this->data_store );

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
		$sut->init( $this->dispatcher, $data_store );

		$notification = new NewOrderNotification( $this->order_id );
		$result       = $sut->process( $notification );

		$this->assertTrue( $result );

		$order = wc_get_order( $this->order_id );

		$this->assertNotEmpty( $order->get_meta( NotificationProcessor::SENT_META_KEY ) );
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
}
