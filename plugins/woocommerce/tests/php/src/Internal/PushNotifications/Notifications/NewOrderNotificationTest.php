<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Notifications;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the NewOrderNotification class.
 */
class NewOrderNotificationTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should return a payload with all required keys for an existing order.
	 */
	public function test_to_payload_contains_required_keys(): void {
		$order        = WC_Helper_Order::create_order();
		$notification = new NewOrderNotification( $order->get_id() );

		$payload = $notification->to_payload();

		$this->assertArrayHasKey( 'type', $payload );
		$this->assertArrayHasKey( 'blog_id', $payload );
		$this->assertArrayHasKey( 'resource_id', $payload );
		$this->assertArrayHasKey( 'title', $payload );
		$this->assertArrayHasKey( 'format', $payload['title'] );
		$this->assertArrayHasKey( 'args', $payload['title'] );
		$this->assertArrayHasKey( 'message', $payload );
		$this->assertArrayHasKey( 'format', $payload['message'] );
		$this->assertArrayHasKey( 'args', $payload['message'] );
		$this->assertArrayHasKey( 'icon', $payload );
		$this->assertArrayHasKey( 'meta', $payload );
		$this->assertArrayHasKey( 'order_id', $payload['meta'] );
	}

	/**
	 * @testdox Should return store_order as the notification type.
	 */
	public function test_type_is_store_order(): void {
		$notification = new NewOrderNotification( 1 );

		$this->assertSame( 'store_order', $notification->get_type() );
	}

	/**
	 * @testdox Should return the order ID as the resource ID.
	 */
	public function test_resource_id_matches_order_id(): void {
		$notification = new NewOrderNotification( 42 );

		$this->assertSame( 42, $notification->get_resource_id() );
	}

	/**
	 * @testdox Should include an emoji in the title args.
	 */
	public function test_to_payload_title_args_contain_emoji(): void {
		$order        = WC_Helper_Order::create_order();
		$notification = new NewOrderNotification( $order->get_id() );

		$payload = $notification->to_payload();

		$this->assertContains( $payload['title']['args'][0], NewOrderNotification::EMOJI_LIST );
	}

	/**
	 * @testdox Should include an order total and blog name in the message args.
	 */
	public function test_to_payload_message_args_contain_order_total_and_blog_name(): void {
		$order        = WC_Helper_Order::create_order();
		$notification = new NewOrderNotification( $order->get_id() );

		$payload = $notification->to_payload();

		$this->assertSame( get_bloginfo( 'name' ), $payload['message']['args'][1] );

		$this->assertSame(
			wp_strip_all_tags( $order->get_formatted_order_total() ),
			$payload['message']['args'][0]
		);
	}

	/**
	 * @testdox Should return null when the order no longer exists.
	 */
	public function test_to_payload_returns_null_for_deleted_order(): void {
		$notification = new NewOrderNotification( 999999 );

		$this->assertNull( $notification->to_payload() );
	}
}
