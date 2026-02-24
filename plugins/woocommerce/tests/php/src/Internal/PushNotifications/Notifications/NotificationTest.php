<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Notifications;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
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
		$notification = $this->create_notification( 'store_order', 42 );

		$this->assertSame( 'store_order', $notification->get_type() );
	}

	/**
	 * @testdox Should store and return the resource ID.
	 */
	public function test_get_resource_id(): void {
		$notification = $this->create_notification( 'store_order', 42 );

		$this->assertSame( 42, $notification->get_resource_id() );
	}

	/**
	 * @testdox Should return an identifier combining blog ID, type, and resource ID.
	 */
	public function test_get_identifier(): void {
		$notification = $this->create_notification( 'store_order', 42 );

		$this->assertSame( get_current_blog_id() . '_store_order_42', $notification->get_identifier() );
	}

	/**
	 * @testdox Should return different identifiers for different resource IDs.
	 */
	public function test_get_identifier_differs_by_resource_id(): void {
		$order  = $this->create_notification( 'store_order', 42 );
		$review = $this->create_notification( 'store_order', 43 );

		$this->assertNotSame( $order->get_identifier(), $review->get_identifier() );
	}

	/**
	 * @testdox Should return different identifiers for different types with the same resource ID.
	 */
	public function test_get_identifier_differs_by_type(): void {
		$order  = $this->create_notification( 'store_order', 42 );
		$review = $this->create_notification( 'store_review', 42 );

		$this->assertNotSame( $order->get_identifier(), $review->get_identifier() );
	}

	/**
	 * @testdox Should return notification data as an array.
	 */
	public function test_to_array(): void {
		$notification = $this->create_notification( 'store_review', 99 );

		$result = $notification->to_array();

		$this->assertArrayHasKey( 'type', $result );
		$this->assertSame( 'store_review', $result['type'] );
		$this->assertArrayHasKey( 'resource_id', $result );
		$this->assertSame( 99, $result['resource_id'] );
	}

	/**
	 * @testdox Should throw when type is empty.
	 */
	public function test_throws_for_empty_type(): void {
		$this->expectException( InvalidArgumentException::class );

		$this->create_notification( '', 1 );
	}

	/**
	 * @testdox Should throw when resource_id is zero.
	 */
	public function test_throws_for_zero_resource_id(): void {
		$this->expectException( InvalidArgumentException::class );

		$this->create_notification( 'store_order', 0 );
	}

	/**
	 * @testdox Should throw when resource_id is negative.
	 */
	public function test_throws_for_negative_resource_id(): void {
		$this->expectException( InvalidArgumentException::class );

		$this->create_notification( 'store_order', -1 );
	}

	/**
	 * Creates a concrete Notification instance for testing.
	 *
	 * @param string $type        The notification type.
	 * @param int    $resource_id The resource ID.
	 * @return Notification
	 */
	private function create_notification( string $type, int $resource_id ): Notification {
		return new class( $type, $resource_id ) extends Notification {
			/**
			 * Returns a test payload.
			 *
			 * @return array
			 */
			public function to_payload(): array {
				return array( 'test' => true );
			}
		};
	}
}
