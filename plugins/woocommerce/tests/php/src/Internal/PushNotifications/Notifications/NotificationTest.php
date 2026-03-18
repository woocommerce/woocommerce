<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Notifications;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewReviewNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Stubs\StubOrderNotification;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Stubs\StubReviewNotification;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * Tests for the Notification class.
 */
class NotificationTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should store and return the notification type.
	 */
	public function test_get_type(): void {
		$notification = new StubOrderNotification( 42 );

		$this->assertSame( 'store_order', $notification->get_type() );
	}

	/**
	 * @testdox Should store and return the resource ID.
	 */
	public function test_get_resource_id(): void {
		$notification = new StubOrderNotification( 42 );

		$this->assertSame( 42, $notification->get_resource_id() );
	}

	/**
	 * @testdox Should return an identifier combining blog ID, type, and resource ID.
	 */
	public function test_get_identifier(): void {
		$notification = new StubOrderNotification( 42 );

		$this->assertSame( get_current_blog_id() . '_store_order_42', $notification->get_identifier() );
	}

	/**
	 * @testdox Should return different identifiers for different resource IDs.
	 */
	public function test_get_identifier_differs_by_resource_id(): void {
		$first  = new StubOrderNotification( 42 );
		$second = new StubOrderNotification( 43 );

		$this->assertNotSame( $first->get_identifier(), $second->get_identifier() );
	}

	/**
	 * @testdox Should return different identifiers for different types with the same resource ID.
	 */
	public function test_get_identifier_differs_by_type(): void {
		$order  = new StubOrderNotification( 42 );
		$review = new StubReviewNotification( 42 );

		$this->assertNotSame( $order->get_identifier(), $review->get_identifier() );
	}

	/**
	 * @testdox Should return notification data as an array.
	 */
	public function test_to_array(): void {
		$notification = new StubReviewNotification( 99 );

		$result = $notification->to_array();

		$this->assertArrayHasKey( 'type', $result );
		$this->assertSame( 'store_review', $result['type'] );
		$this->assertArrayHasKey( 'resource_id', $result );
		$this->assertSame( 99, $result['resource_id'] );
	}

	/**
	 * @testdox Should throw when resource_id is zero.
	 */
	public function test_throws_for_zero_resource_id(): void {
		$this->expectException( InvalidArgumentException::class );

		new StubOrderNotification( 0 );
	}

	/**
	 * @testdox Should throw when resource_id is negative.
	 */
	public function test_throws_for_negative_resource_id(): void {
		$this->expectException( InvalidArgumentException::class );

		new StubOrderNotification( -1 );
	}

	/**
	 * @testdox Should create a NewOrderNotification from array data.
	 */
	public function test_from_array_creates_order_notification(): void {
		$notification = Notification::from_array(
			array(
				'type'        => 'store_order',
				'resource_id' => 42,
			)
		);

		$this->assertInstanceOf( NewOrderNotification::class, $notification );
		$this->assertSame( 42, $notification->get_resource_id() );
	}

	/**
	 * @testdox from_array should create a NewReviewNotification for store_review type.
	 */
	public function test_from_array_creates_review_notification(): void {
		$notification = Notification::from_array(
			array(
				'type'        => 'store_review',
				'resource_id' => 99,
			)
		);

		$this->assertInstanceOf( NewReviewNotification::class, $notification );
		$this->assertSame( 99, $notification->get_resource_id() );
	}

	/**
	 * @testdox from_array should throw for an unknown notification type.
	 */
	public function test_from_array_throws_for_unknown_type(): void {
		$this->expectException( InvalidArgumentException::class );

		Notification::from_array(
			array(
				'type'        => 'unknown_type',
				'resource_id' => 1,
			)
		);
	}

	/**
	 * @testdox Should throw when type is missing from array data.
	 */
	public function test_from_array_throws_for_missing_type(): void {
		$this->expectException( InvalidArgumentException::class );

		Notification::from_array(
			array(
				'resource_id' => 1,
			)
		);
	}
}
