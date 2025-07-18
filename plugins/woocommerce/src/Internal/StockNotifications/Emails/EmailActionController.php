<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;

/**
 * Class EmailActionController
 *
 * Handles email actions such as verification and unsubscribe.
 *
 * @package Automattic\WooCommerce\Internal\StockNotifications\Emails
 */
class EmailActionController {
    public function __construct() {
        add_action( 'template_redirect', array( $this, 'maybe_process_verification_action_from_request' ) );
        add_action( 'template_redirect', array( $this, 'maybe_process_unsubscribe_action_from_request' ) );
    }

    public function maybe_process_verification_action_from_request(): void {
        $this->maybe_process_verification_action(
            $_GET['notification_id'] ?? null,
            $_GET['email_link_action_key'] ?? null
        );
    }

    // Wrapper for production use
    public function maybe_process_unsubscribe_action_from_request(): void {
        $this->maybe_process_unsubscribe_action(
            $_GET['notification_id'] ?? null,
            $_GET['email_link_action_key'] ?? null
        );
    }

	/**
	 * If the verification key matches, it updates the notification status to active.
	 * TODO: redirect the request, notify the user of successful verification.
     *
     * @param int|null $notification_id The ID of the notification to process.
     * @param string|null $action_key The action key to verify.
	 */
    public function maybe_process_verification_action( $notification_id, $action_key ): void  {
        $notification = $this->get_notification_to_be_processed();

        if ( ! $notification ) {
            return;
        }

		if ( $notification->check_verification_key( $_GET['email_link_action_key'] ) ) {
			$notification->set_status( NotificationStatus::ACTIVE );
			$notification->set_date_confirmed( time() );
			$notification->save();
		}
	}

	/**
	 * If the unsubscribe key matches, it updates the notification status to cancelled.
	 * TODO: redirect the request, notify the user of successful unsubscription.
     *
     * @param int|null $notification_id The ID of the notification to process.
     * @param string|null $action_key The action key to verify.
	 */
    public function maybe_process_unsubscribe_action( $notification_id, $action_key ): void {
        $notification = $this->get_notification_to_be_processed();

        if ( ! $notification ) {
            return;
        }

        if ( $notification->check_unsubscribe_key( $_GET['email_link_action_key'] ) ) {
			$this->notification->set_status( NotificationStatus::CANCELLED );
			$this->notification->save();
		}
	}

    /**
     * Retrieves the notification to be processed based on the provided notification ID and action key.
     *
     * @param int|null $notification_id The ID of the notification to process.
     * @param string|null $action_key The action key to verify.
     * @return Notification|null The notification object if found, otherwise null.
     */
    public function get_notification_to_be_processed( $notification_id, $action_key ): Notification | null {
        if ( ! isset( $notification_id ) || ! isset( $action_key ) ) {
            return null;
        }

        $notification = Factory::get_notification( $_GET['notification_id'] );

        if ( ! $notification ) {
            return null;
        }

        if ( empty( $notification->get_meta( 'email_link_action_key' ) ) ) {
            return null;
        }

        return $notification;
    }
}
