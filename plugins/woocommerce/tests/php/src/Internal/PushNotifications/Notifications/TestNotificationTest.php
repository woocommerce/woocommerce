<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Notifications;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\TestNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * Tests for the TestNotification class.
 */
class TestNotificationTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should keep the target token when rebuilt from its array form.
	 */
	public function test_round_trip_keeps_the_target_token(): void {
		$rebuilt = Notification::from_array( ( new TestNotification( 42, 7 ) )->to_array() );

		$this->assertInstanceOf( TestNotification::class, $rebuilt );
		$this->assertSame( 42, $rebuilt->get_resource_id() );
		$this->assertSame( 7, $rebuilt->get_target_token_id() );
	}

	/**
	 * @testdox Should refuse to rebuild a test notification with no token, so it is never sent to every device.
	 */
	public function test_from_array_throws_without_a_token_id(): void {
		$this->expectException( InvalidArgumentException::class );

		Notification::from_array(
			array(
				'type'        => TestNotification::TYPE,
				'resource_id' => 42,
			)
		);
	}

	/**
	 * @testdox Should reject a test ID above the signed 32-bit range.
	 */
	public function test_constructor_rejects_an_out_of_range_test_id(): void {
		$this->expectException( InvalidArgumentException::class );

		new TestNotification( TestNotification::MAX_TEST_ID + 1, 7 );
	}

	/**
	 * @testdox Should build a payload with no alert content that carries the test ID.
	 */
	public function test_payload_has_no_alert_content(): void {
		$payload = ( new TestNotification( 42, 7 ) )->to_payload();

		$this->assertSame( 'store_test', $payload['type'] );
		$this->assertArrayNotHasKey( 'title', $payload );
		$this->assertArrayNotHasKey( 'message', $payload );
		$this->assertSame( 42, $payload['meta']['test_id'] );
	}

	/**
	 * @testdox Should send to the user even when their stored preference says disabled.
	 */
	public function test_should_send_to_user_ignores_preferences(): void {
		$this->assertTrue( ( new TestNotification( 42, 7 ) )->should_send_to_user( array( 'enabled' => false ) ) );
	}

	/**
	 * @testdox Should keep delivery state per test ID, and report a test ID as used once claimed.
	 */
	public function test_delivery_state_is_kept_per_test_id(): void {
		$notification = new TestNotification( 42, 7 );

		$this->assertFalse( $notification->is_used() );

		$notification->write_meta( NotificationProcessor::CLAIMED_META_KEY, 1700000000 );

		$this->assertTrue( $notification->is_used() );
		$this->assertSame( '1700000000', $notification->read_meta( NotificationProcessor::CLAIMED_META_KEY ) );
		$this->assertFalse( ( new TestNotification( 43, 7 ) )->is_used() );

		$notification->delete_meta( NotificationProcessor::CLAIMED_META_KEY );

		$this->assertFalse( $notification->is_used() );
	}
}
