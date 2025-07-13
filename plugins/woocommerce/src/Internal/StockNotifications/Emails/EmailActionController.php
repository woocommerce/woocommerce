<?php


declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;

/**
 * Class EmailActionController
 *
 * Handles email actions such as verification and unsubscribe.
 *
 * @package Automattic\WooCommerce\Internal\StockNotifications\Emails
 */
class EmailActionController {

	public $notification;

	/**
	 * Checks for email actions in the request and processes them.
	 */
	public function __construct() {
		if (
			isset( $_GET['notification_id'] ) &&
			isset( $_GET['email_link_action_key'] )
		) {
			$this->maybe_process_email_action();
		}
	}

	/**
	 * Checks the request for valid notification ID. If found, processes the email action based on
	 * the shape of the action key.
	 */
	private function maybe_process_email_action(): void {
		$this->notification = Factory::get_notification( $_GET['notification_id'] );

		if ( ! $this->notification ) {
			return;
		}

		if ( empty( $this->notification->get_meta( 'email_link_action_key' ) ) ) {
			return;
		}

		if ( str_contains( $this->notification->get_meta('email_link_action_key'), ':' ) ) {
			$this->process_verification_action();
		} else {
			$this->process_unsubscribe_action();
		}
	}

	/**
	 * If the verification key matches, it updates the notification status to active.
	 * TODO: set a notification and redirect the request.
	 */
	private function process_verification_action(): void {
		if ( $this->notification->check_verification_key( $_GET['email_link_action_key'] ) ) {
			$this->notification->set_status( NotificationStatus::ACTIVE );
			$this->notification->set_date_confirmed( time() );
			$this->notification->save();
		}
	}

	/**
	 * If the unsubscribe key matches, it updates the notification status to cancelled.
	 * TODO: set a notification and redirect the request.
	 */
	private function process_unsubscribe_action(): void {
		if ( $this->notification->check_unsubscribe_key( $_GET['email_link_action_key'] ) ) {
			$this->notification->set_status( NotificationStatus::CANCELLED );
			$this->notification->save();
		}
	}
}
