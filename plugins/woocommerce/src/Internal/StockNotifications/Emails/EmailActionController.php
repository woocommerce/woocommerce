<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
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
	/**
	 * EmailActionController constructor.
	 *
	 * Initializes the controller by adding actions to process verification and unsubscribe actions from requests.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_process_email_action' ) );
	}

	/**
	 * This method checks if the request contains indicators to process an action from an email link.
	 */
	public function maybe_process_email_action(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['notification_id'] ) || ! isset( $_GET['email_link_action_key'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notification_id = absint( wp_unslash( $_GET['notification_id'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action_key = sanitize_text_field( wp_unslash( $_GET['email_link_action_key'] ) );

		$this->validate_and_maybe_process_request( $notification_id, $action_key );
	}

	/**
	 * Checks request parameters and processes the notification based on the action key.
	 *
	 * @param int    $notification_id The ID of the notification to process.
	 * @param string $email_link_action_key The action key from the email link.
	 * @return void
	 */
	public function validate_and_maybe_process_request( int $notification_id, string $email_link_action_key ): void {
		if ( empty( $email_link_action_key ) || empty( $notification_id ) ) {
			return;
		}

		$notification = $this->get_notification_to_be_processed( $notification_id );

		if ( ! $notification ) {
			return;
		}

		$action_key = $notification->get_meta( 'email_link_action_key' );
		if ( strpos( $action_key, ':' ) !== false ) {
			$this->process_verification_action( $notification, $email_link_action_key );
		} else {
			$this->process_unsubscribe_action( $notification, $email_link_action_key );
		}
	}

	/**
	 * If the verification key matches, it updates the notification status to active.
	 *
	 * @param Notification $notification The notification to process.
	 * @param string       $action_key The action key to verify.
	 * @return void
	 */
	private function process_verification_action( Notification $notification, string $action_key ): void {
		if ( $notification->check_verification_key( $action_key ) ) {
			$notification->set_status( NotificationStatus::ACTIVE );
			$notification->set_date_confirmed( time() );
			$notification->save();

			// We need session for notices to work.
			if ( ! WC()->session->has_session() ) {
				// Generate a random customer ID.
				WC()->session->set_customer_session_cookie( true );
			}

			$product = wc_get_product( $notification->get_product_id() );

			/* translators: %s is product name */
			$notice_text = sprintf( esc_html__( 'Successfully verified stock notifications for "%s".', 'woocommerce' ), $product->get_name() );
			wc_add_notice( $notice_text );
			/**
			 * `woocommerce_customer_stock_notification_verified_redirect_url` filter.
			 *
			 * @since 10.2.0
			 *
			 * @param  string  $url
			 * @return string
			 */
			$url = apply_filters( 'woocommerce_customer_stock_notification_verified_redirect_url', get_permalink( wc_get_page_id( 'shop' ) ) );
			wp_safe_redirect( $url );
		}
	}

	/**
	 * If the unsubscribe key matches, it updates the notification status to cancelled.
	 *
	 * @param Notification $notification The Notification to process.
	 * @param string       $action_key The action key to verify.
	 * @return void
	 */
	private function process_unsubscribe_action( Notification $notification, string $action_key ): void {
		if ( $notification->check_unsubscribe_key( $action_key ) ) {
			$notification->set_status( NotificationStatus::CANCELLED );
			$notification->set_cancellation_source( NotificationCancellationSource::USER );
			$notification->set_date_cancelled( time() );
			$notification->save();

			// We need session for notices to work.
			if ( ! WC()->session->has_session() ) {
				// Generate a random customer ID.
				WC()->session->set_customer_session_cookie( true );
			}

			$product = wc_get_product( $notification->get_product_id() );

			/* translators: %2$s product name, %1$s user email */
			$notice_text = sprintf( esc_html__( 'Successfully unsubscribed %1$s. You will not receive a notification when "%2$s" becomes available.', 'woocommerce' ), $notification->get_user_email(), $product->get_name() );
			wc_add_notice( $notice_text );
			/**
			 * `woocommerce_customer_stock_notification_unsubscribe_redirect_url` filter.
			 *
			 * @since 10.2.0
			 *
			 * @param  string  $url
			 * @return string
			 */
			$url = apply_filters( 'woocommerce_customer_stock_notification_unsubscribe_redirect_url', get_permalink( wc_get_page_id( 'shop' ) ) );
			wp_safe_redirect( $url );
		}
	}

	/**
	 * Retrieves the notification to be processed based on the provided notification ID and action key.
	 *
	 * @param int $notification_id The ID of the notification to process.
	 * @return Notification|false The notification object if found and has an action key, null otherwise.
	 */
	private function get_notification_to_be_processed( int $notification_id ): ?Notification {
		$notification = Factory::get_notification( (int) $notification_id );

		if ( ! $notification ) {
			return false;
		}

		if ( empty( $notification->get_meta( 'email_link_action_key' ) ) ) {
			return false;
		}

		return $notification;
	}
}
