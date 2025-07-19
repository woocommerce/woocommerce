<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailActionController;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\HasherHelper;

/**
 * EmailActionControllerTests tests.
 */
class EmailActionControllerTests extends \WC_Unit_Test_Case {

    /**
     * Test that verification action is sets notification status to active.
     */
    public function test_process_verification_action_sets_status_active() {
        $notification = new Notification();
        $notification->set_product_id( 1 );
        $notification->set_status( NotificationStatus::PENDING );
        $notification->set_user_email( 'test@example.com' );
        $key = time() . ':' . HasherHelper::wp_fast_hash( 'test' );
        $notification->update_meta_data('email_link_action_key', $key );
        $id = $notification->save();

        $controller = new EmailActionController();
        $controller->maybe_process_verification_action($id, 'test');
        $notification = Factory::get_notification( $id );
        $this->assertEquals(NotificationStatus::ACTIVE, $notification->get_status());
    }

    /**
     * Test that unsubscribe action sets notification status to cancelled, and sets cancellation source to user.
     */
    public function test_process_unsubscribe_action_sets_status_cancelled() {
        $notification = new Notification();
        $notification->set_product_id( 1 );
        $notification->set_status( NotificationStatus::ACTIVE );
        $notification->set_user_email( 'test@example.com' );
        $key = HasherHelper::wp_fast_hash( 'test' );
        $notification->update_meta_data( 'email_link_action_key', $key );
        $id = $notification->save();

        $controller = new EmailActionController();
        $controller->maybe_process_unsubscribe_action($id, 'test');
        $notification = Factory::get_notification( $id );
        $this->assertEquals( NotificationStatus::CANCELLED, $notification->get_status() );
        $this->assertEquals( NotificationCancellationSource::USER, $notification->get_cancellation_source() );
    }
}
