<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailActionController;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use WC_Helper_Product;

/**
 * EmailActionControllerTests tests.
 */
class EmailActionControllerTests extends \WC_Unit_Test_Case {

	/**
	 * Test that verification action is sets notification status to active.
	 */
	public function test_process_verification_action_sets_status_active() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::PENDING );
		$notification->set_user_email( 'test@example.com' );
		$key = time() . ':' . wp_fast_hash( 'test' );
		$notification->update_meta_data( 'verification_action_key', $key );
		$id = $notification->save();

		$controller = new EmailActionController();
		$controller->validate_and_maybe_process_request( $id, 'test', 'verify' );
		$updated_notification = Factory::get_notification( $id );
		$this->assertEquals( NotificationStatus::ACTIVE, $updated_notification->get_status() );
	}

	/**
	 * Test that unsubscribe action sets notification status to cancelled, and sets cancellation source to user.
	 */
	public function test_process_unsubscribe_action_sets_status_cancelled() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_user_email( 'test@example.com' );
		$key = wp_fast_hash( 'test' );
		$notification->update_meta_data( 'unsubscribe_action_key', $key );
		$id = $notification->save();

		$controller = new EmailActionController();
		$controller->validate_and_maybe_process_request( $id, 'test', 'unsubscribe' );
		$updated_notification = Factory::get_notification( $id );
		$this->assertEquals( NotificationStatus::CANCELLED, $updated_notification->get_status() );
		$this->assertEquals( NotificationCancellationSource::USER, $updated_notification->get_cancellation_source() );
	}

	/**
	 * A verification request with a key that doesn't match the stored one must
	 * leave the notification untouched.
	 */
	public function test_process_verification_action_with_invalid_key_leaves_status_pending() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::PENDING );
		$notification->set_user_email( 'test@example.com' );
		$notification->update_meta_data( 'verification_action_key', time() . ':' . wp_fast_hash( 'real-key' ) );
		$id = $notification->save();

		$controller = new EmailActionController();
		$controller->validate_and_maybe_process_request( $id, 'wrong-key', 'verify' );

		$updated_notification = Factory::get_notification( $id );
		$this->assertEquals( NotificationStatus::PENDING, $updated_notification->get_status() );
	}

	/**
	 * An `unsubscribe` action routed against a notification that only has a
	 * verification key must not cancel the notification.
	 */
	public function test_process_unsubscribe_action_with_only_verification_key_does_not_cancel() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_user_email( 'test@example.com' );
		// Only a verification key is stored — the unsubscribe_action_key meta
		// is deliberately empty to simulate a mis-routed link.
		$notification->update_meta_data( 'verification_action_key', time() . ':' . wp_fast_hash( 'test' ) );
		$id = $notification->save();

		$controller = new EmailActionController();
		$controller->validate_and_maybe_process_request( $id, 'test', 'unsubscribe' );

		$updated_notification = Factory::get_notification( $id );
		$this->assertEquals( NotificationStatus::ACTIVE, $updated_notification->get_status() );
	}

	/**
	 * Calling with a zero/missing notification id must early-return without
	 * error.
	 */
	public function test_process_action_with_missing_notification_id_handles_gracefully() {
		$controller = new EmailActionController();

		// Should not throw or emit a notice — the guard in
		// validate_and_maybe_process_request catches the empty id.
		$controller->validate_and_maybe_process_request( 0, 'any-key', 'verify' );

		$this->assertTrue( true );
	}

	/**
	 * Unknown action tokens must no-op rather than running either the verify
	 * or unsubscribe code paths.
	 */
	public function test_process_action_with_unknown_token_does_not_mutate_notification() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::PENDING );
		$notification->set_user_email( 'test@example.com' );
		$notification->update_meta_data( 'verification_action_key', time() . ':' . wp_fast_hash( 'test' ) );
		$id = $notification->save();

		$controller = new EmailActionController();
		$controller->validate_and_maybe_process_request( $id, 'test', 'bogus-action' );

		$updated_notification = Factory::get_notification( $id );
		$this->assertEquals( NotificationStatus::PENDING, $updated_notification->get_status() );
	}
}
