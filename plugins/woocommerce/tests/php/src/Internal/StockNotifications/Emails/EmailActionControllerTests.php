<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailActionController;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\HasherHelper;

/**
 * EmailActionController tests.
 */
class EmailActionControllerTests extends \WC_Unit_Test_Case {
	public function setUp(): void {
		parent::setUp();
		$_GET = [];
	}

	public function tearDown(): void {
		parent::tearDown();
		$_GET = [];
	}

	/**
	 * Test that the controller processes email verification links.
	 */
	public function test_process_verification_action_sets_status_active() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_status(NotificationStatus::PENDING);
		$notification->set_user_email( 'test@example.com' );
		$key = time() . ':' . HasherHelper::wp_fast_hash( 'test' );
		$notification->update_meta_data('email_link_action_key', $key );
		$id = $notification->save();

		$_GET['notification_id'] = $id;
		$_GET['email_link_action_key'] = 'test';

		$controller = new EmailActionController();
		$controller->process_verification_action();
		$notification = Factory::get_notification( $id );
		$this->assertEquals(NotificationStatus::ACTIVE, $notification->get_status());
	}

	/**
	 * Test that the controller processes unsubscribe links.
	 */
	public function test_process_unsubscribe_action_sets_status_cancelled() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_user_email( 'test@example.com' );
		$key = HasherHelper::wp_fast_hash( 'test' );
		$notification->update_meta_data( 'email_link_action_key', $key );
		$id = $notification->save();

		$_GET['notification_id'] = $id;
		$_GET['email_link_action_key'] = 'test';

		$controller = new EmailActionController();
		$controller->process_unsubscribe_action();
		$notification = Factory::get_notification( $id );
		$this->assertEquals( NotificationStatus::CANCELLED, $notification->get_status() );
	}

	/**
	 * Test that the controller does nothing if the action key is not found.
	 */
	public function test_invalid_notification_id_does_nothing() {
		$_GET['notification_id'] = 999;
		$_GET['email_link_action_key'] = 'any_key';

		$controller = new EmailActionController();
		$this->assertFalse( $controller->notification );
	}
}
