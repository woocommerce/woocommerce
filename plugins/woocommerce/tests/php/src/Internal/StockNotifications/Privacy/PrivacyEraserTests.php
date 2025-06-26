<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Privacy;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Privacy\PrivacyEraser;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;

/**
 * PrivacyEraser tests.
 */
class PrivacyEraserTests extends \WC_Unit_Test_Case {
	/**
	 * Test that privacy eraser makes notification data anonymous.
	 */
	public function test_privacy_eraser_makes_data_anonymous() {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( 1 );
		$notification_id = $notification->save();

		$response = PrivacyEraser::erase_notification_data( 'jon@doe.com' );
		$this->assertTrue( $response['items_removed'] );
		$this->assertEquals( $response['messages'][0], 'Removed back-in-stock notification for product id: 1' );

		$anonymous_notification = new Notification( $notification_id );

		$this->assertEquals( $anonymous_notification->get_user_email(), wp_privacy_anonymize_data( 'email', '' ) );
		$this->assertEquals( NotificationStatus::CANCELLED, $anonymous_notification->get_status() );
	}
}
