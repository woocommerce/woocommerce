<?php


declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Factory;

class EmailActionController {

	public $notification;

	/**
	 * Constructor to set up hooks for managing data retention tasks.
	 */
	public function __construct() {
		if (
			isset( $_GET['notification_id'] ) &&
			isset( $_GET['key'] )
		) {
			$this->maybe_process_email_action();
		}
	}

	private function maybe_process_email_action() {
		$this->notification = Factory::get_notification( $_GET['notification_id'] );

		if ( ! $this->notification ) {
			return;
		}

		if ( ! $this->notification->is_valid_key( $_GET['key'] ) ) {
			return;
		}

		if ( empty( $this->notification->get_meta( 'email_action_key' ) ) ) {
			return;
		}

		if ( str_contains( $this->notification->get_meta('email_action_key'), ':' ) ) {
			$this->process_verification_action();
		} elseif ( 'unsubscribe' === $_GET['action'] ) {
			$this->process_unsubscribe_action();
		}
	}

	private function process_verification_action() {

	}

	private function process_unsubscribe_action() {
		if ( ! $this->notification ) {
			return;
		}

		$this->notification->delete();
		wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
		exit;
	}
}
