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
	 * @testdox Should return an identifier combining blog ID, type, and resource ID.
	 */
	public function test_get_identifier(): void {
		$notification = new StubOrderNotification( 42 );

		$this->assertSame( get_current_blog_id() . '_store_order_42', $notification->get_identifier() );
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
	 * @testdox Should throw when resource_id is $resource_id.
	 * @testWith [0]
	 *           [-1]
	 *
	 * @param int $resource_id The invalid resource ID.
	 */
	public function test_throws_for_non_positive_resource_id( int $resource_id ): void {
		$this->expectException( InvalidArgumentException::class );

		new StubOrderNotification( $resource_id );
	}

	/**
	 * @testdox from_array should create correct notification for $type type.
	 * @testWith ["store_order", "Automattic\\WooCommerce\\Internal\\PushNotifications\\Notifications\\NewOrderNotification"]
	 *           ["store_review", "Automattic\\WooCommerce\\Internal\\PushNotifications\\Notifications\\NewReviewNotification"]
	 *
	 * @param string $type           The notification type.
	 * @param string $expected_class The expected class name.
	 */
	public function test_from_array_creates_notification( string $type, string $expected_class ): void {
		$notification = Notification::from_array(
			array(
				'type'        => $type,
				'resource_id' => 42,
			)
		);

		$this->assertInstanceOf( $expected_class, $notification );
		$this->assertSame( 42, $notification->get_resource_id() );
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
