<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Notification;

use Automattic\WooCommerce\Internal\PushNotifications\Notification\Notification;
use WC_Unit_Test_Case;

/**
 * Tests for the Notification class.
 */
class NotificationTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should store and return the notification type.
	 */
	public function test_get_type(): void {
		$notification = $this->create_notification( 'store_order', 42, 1 );

		$this->assertSame( 'store_order', $notification->get_type() );
	}

	/**
	 * @testdox Should store and return the resource ID.
	 */
	public function test_get_resource_id(): void {
		$notification = $this->create_notification( 'store_order', 42, 1 );

		$this->assertSame( 42, $notification->get_resource_id() );
	}

	/**
	 * @testdox Should store and return the blog ID.
	 */
	public function test_get_blog_id(): void {
		$notification = $this->create_notification( 'store_order', 42, 5 );

		$this->assertSame( 5, $notification->get_blog_id() );
	}

	/**
	 * @testdox Should return an identifier combining type and resource ID.
	 */
	public function test_get_identifier(): void {
		$notification = $this->create_notification( 'store_order', 42, 1 );

		$this->assertSame( 'store_order_42', $notification->get_identifier() );
	}

	/**
	 * @testdox Should return different identifiers for different types with the same resource ID.
	 */
	public function test_get_identifier_differs_by_type(): void {
		$order  = $this->create_notification( 'store_order', 42, 1 );
		$review = $this->create_notification( 'store_review', 42, 1 );

		$this->assertNotSame( $order->get_identifier(), $review->get_identifier() );
	}

	/**
	 * @testdox Should return notification data as an array.
	 */
	public function test_to_array(): void {
		$notification = $this->create_notification( 'store_review', 99, 3 );

		$result = $notification->to_array();

		$this->assertArrayHasKey( 'type', $result );
		$this->assertSame( 'store_review', $result['type'] );
		$this->assertArrayHasKey( 'resource_id', $result );
		$this->assertSame( 99, $result['resource_id'] );
		$this->assertArrayHasKey( 'blog_id', $result );
		$this->assertSame( 3, $result['blog_id'] );
	}

	/**
	 * Creates a concrete Notification instance for testing.
	 *
	 * @param string $type        The notification type.
	 * @param int    $resource_id The resource ID.
	 * @param int    $blog_id     The blog ID.
	 * @return Notification
	 */
	private function create_notification( string $type, int $resource_id, int $blog_id ): Notification {
		return new class( $type, $resource_id, $blog_id ) extends Notification {
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
