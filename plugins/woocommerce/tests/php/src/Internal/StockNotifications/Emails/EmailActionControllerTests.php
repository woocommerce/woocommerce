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
	 * Persist a notification with a single action-key meta entry.
	 *
	 * @param string $status     Initial NotificationStatus value to set on the notification.
	 * @param string $meta_key   Meta key to store the action key under (e.g. 'verification_action_key').
	 * @param string $stored_key Already-formatted key value (caller hashes/timestamps as needed).
	 * @return int Saved notification id.
	 */
	private function arrange_notification( string $status, string $meta_key, string $stored_key ): int {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( $status );
		$notification->set_user_email( 'test@example.com' );
		$notification->update_meta_data( $meta_key, $stored_key );
		return $notification->save();
	}

	/**
	 * Test that verification action is sets notification status to active.
	 */
	public function test_process_verification_action_sets_status_active() {
		$id = $this->arrange_notification(
			NotificationStatus::PENDING,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'test' )
		);

		( new EmailActionController() )->validate_and_maybe_process_request( $id, 'test', 'verify' );

		$this->assertEquals( NotificationStatus::ACTIVE, Factory::get_notification( $id )->get_status() );
	}

	/**
	 * Test that unsubscribe action sets notification status to cancelled, and sets cancellation source to user.
	 */
	public function test_process_unsubscribe_action_sets_status_cancelled() {
		$id = $this->arrange_notification(
			NotificationStatus::ACTIVE,
			'unsubscribe_action_key',
			wp_fast_hash( 'test' )
		);

		( new EmailActionController() )->validate_and_maybe_process_request( $id, 'test', 'unsubscribe' );

		$updated = Factory::get_notification( $id );
		$this->assertEquals( NotificationStatus::CANCELLED, $updated->get_status() );
		$this->assertEquals( NotificationCancellationSource::USER, $updated->get_cancellation_source() );
	}

	/**
	 * A verification request with a key that doesn't match the stored one must
	 * leave the notification untouched.
	 */
	public function test_process_verification_action_with_invalid_key_leaves_status_pending() {
		$id = $this->arrange_notification(
			NotificationStatus::PENDING,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'real-key' )
		);

		( new EmailActionController() )->validate_and_maybe_process_request( $id, 'wrong-key', 'verify' );

		$this->assertEquals( NotificationStatus::PENDING, Factory::get_notification( $id )->get_status() );
	}

	/**
	 * An `unsubscribe` action routed against a notification that only has a
	 * verification key must not cancel the notification.
	 */
	public function test_process_unsubscribe_action_with_only_verification_key_does_not_cancel() {
		// Only a verification key is stored — the unsubscribe_action_key meta
		// is deliberately empty to simulate a mis-routed link.
		$id = $this->arrange_notification(
			NotificationStatus::ACTIVE,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'test' )
		);

		( new EmailActionController() )->validate_and_maybe_process_request( $id, 'test', 'unsubscribe' );

		$this->assertEquals( NotificationStatus::ACTIVE, Factory::get_notification( $id )->get_status() );
	}

	/**
	 * Calling with a zero/missing notification id must early-return without
	 * error.
	 */
	public function test_process_action_with_missing_notification_id_handles_gracefully() {
		// The guard in validate_and_maybe_process_request short-circuits when
		// the id is 0; no side-effect to assert, so suppress PHPUnit's risky
		// warning without a no-op assertion.
		$this->expectNotToPerformAssertions();

		( new EmailActionController() )->validate_and_maybe_process_request( 0, 'any-key', 'verify' );
	}

	/**
	 * Unknown action tokens must no-op rather than running either the verify
	 * or unsubscribe code paths.
	 */
	public function test_process_action_with_unknown_token_does_not_mutate_notification() {
		$id = $this->arrange_notification(
			NotificationStatus::PENDING,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'test' )
		);

		( new EmailActionController() )->validate_and_maybe_process_request( $id, 'test', 'bogus-action' );

		$this->assertEquals( NotificationStatus::PENDING, Factory::get_notification( $id )->get_status() );
	}

	/**
	 * An empty \$action is a caller-side bug (missing argument) that must
	 * short-circuit before the switch; asserted separately from the
	 * unknown-token branch, which takes the `default:` debug-log path.
	 */
	public function test_process_action_with_empty_action_early_returns() {
		$id = $this->arrange_notification(
			NotificationStatus::PENDING,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'test' )
		);

		( new EmailActionController() )->validate_and_maybe_process_request( $id, 'test', '' );

		$this->assertEquals( NotificationStatus::PENDING, Factory::get_notification( $id )->get_status() );
	}
}
