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
		add_action( 'template_redirect', array( $this, 'maybe_process_verification_action_from_request' ) );
		add_action( 'template_redirect', array( $this, 'maybe_process_unsubscribe_action_from_request' ) );
	}

	/**
	 * This method checks if the request contains a verification action and processes it.
	 */
	public function maybe_process_verification_action_from_request(): void {
		$this->maybe_process_verification_action(
			$_GET['notification_id'] ?? null,
			$_GET['email_link_action_key'] ?? null
		);
	}

	/**
	 * This method checks if the request contains an unsubscribe action and processes it.
	 */
	public function maybe_process_unsubscribe_action_from_request(): void {
		$this->maybe_process_unsubscribe_action(
            $_GET['notification_id'] ?? null,
            $_GET['email_link_action_key'] ?? null
		);
	}

	/**
	 * If the verification key matches, it updates the notification status to active.
	 *
	 * @param string $notification_id The ID of the notification to process.
	 * @param string $action_key The action key to verify.
	 * @return void
	 */
	public function maybe_process_verification_action( string $notification_id, string $action_key ): void {
		if ( ! $action_key ) {
			return;
		}

		$notification = $this->get_notification_to_be_processed( $notification_id );

		if ( ! $notification ) {
			return;
		}

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
			 * `woocommerce_customer_stock_notification_verified_url` filter.
			 *
			 * @since 0.0.0
			 *
			 * @param  string  $url
			 * @return string
			 */
			$url = apply_filters( 'woocommerce_customer_stock_notification_verified_url', get_permalink( wc_get_page_id( 'shop' ) ) );
			wp_safe_redirect( $url );
		}
	}

	/**
	 * If the unsubscribe key matches, it updates the notification status to cancelled.
	 *
	 * @param string $notification_id The ID of the notification to process.
	 * @param string $action_key The action key to verify.
	 * @return void
	 */
	public function maybe_process_unsubscribe_action( string $notification_id, string $action_key ): void {
		if ( ! $action_key ) {
			return;
		}

		$notification = $this->get_notification_to_be_processed( $notification_id );

		if ( ! $notification ) {
			return;
		}

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
			 * `woocommerce_customer_stock_notification_unsubscribe_url` filter.
			 *
			 * @since 0.0.0
			 *
			 * @param  string  $url
			 * @return string
			 */
			$url = apply_filters( 'woocommerce_customer_stock_notification_unsubscribe_url', get_permalink( wc_get_page_id( 'shop' ) ) );
			wp_safe_redirect( $url );
		}
	}

	/**
	 * Retrieves the notification to be processed based on the provided notification ID and action key.
	 *
	 * @param string $notification_id The ID of the notification to process.
	 * @return Notification The notification object if found and has an action key, null otherwise.
	 */
	public function get_notification_to_be_processed( string $notification_id ): Notification|false {
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
