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
	 * Email manager.
	 *
	 * @var EmailManager
	 */
	private EmailManager $email_manager;

	/**
	 * Action token for verifying (double opt-in) a pending notification sign-up.
	 *
	 * Must match the `email_link_action` query param produced by the verify
	 * email template.
	 */
	public const ACTION_VERIFY = 'verify';

	/**
	 * Action token for unsubscribing an active notification sign-up.
	 *
	 * Must match the `email_link_action` query param produced by the "back in
	 * stock" and confirmation email templates.
	 */
	public const ACTION_UNSUBSCRIBE = 'unsubscribe';

	/**
	 * EmailActionController constructor.
	 *
	 * Initializes the controller by adding actions to process verification and unsubscribe actions from requests.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_process_email_action' ) );
	}

	/**
	 * Init the service.
	 *
	 * @internal
	 *
	 * @param EmailManager $email_manager The email manager.
	 */
	final public function init( EmailManager $email_manager ): void {
		$this->email_manager = $email_manager;
	}

	/**
	 * This method checks if the request contains indicators to process an action from an email link.
	 */
	public function maybe_process_email_action(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['notification_id'] ) || ! isset( $_GET['email_link_action_key'] ) || ! isset( $_GET['email_link_action'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notification_id = absint( wp_unslash( $_GET['notification_id'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action_key = sanitize_text_field( wp_unslash( $_GET['email_link_action_key'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( wp_unslash( $_GET['email_link_action'] ) );

		$this->validate_and_maybe_process_request( $notification_id, $action_key, $action );
	}

	/**
	 * Checks request parameters and processes the notification based on the action type.
	 *
	 * @param int    $notification_id       The ID of the notification to process.
	 * @param string $email_link_action_key The action key from the email link.
	 * @param string $action                The action to perform: 'verify' or 'unsubscribe'.
	 * @return void
	 */
	public function validate_and_maybe_process_request( int $notification_id, string $email_link_action_key, string $action = '' ): void {
		if ( empty( $email_link_action_key ) || empty( $notification_id ) ) {
			return;
		}

		// An empty $action means the caller omitted the routing argument — a
		// programming error, not a mis-routed email. Return silently so the
		// debug branch in the switch is reserved for genuinely-unknown
		// tokens arriving in the wild.
		if ( '' === $action ) {
			return;
		}

		$notification = $this->get_notification_to_be_processed( $notification_id );

		if ( ! $notification ) {
			return;
		}

		switch ( $action ) {
			case self::ACTION_VERIFY:
				$this->process_verification_action( $notification, $email_link_action_key );
				break;
			case self::ACTION_UNSUBSCRIBE:
				$this->process_unsubscribe_action( $notification, $email_link_action_key );
				break;
			default:
				// Unknown action — silently drop in production, log in debug so
				// mis-routed email links surface during alpha testing rather
				// than no-op'ing invisibly.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					wc_get_logger()->debug(
						sprintf(
							'Unknown email_link_action "%s" for notification %d',
							$action,
							$notification->get_id()
						),
						array( 'source' => 'stock-notifications' )
					);
				}
				break;
		}//end switch
	}

	/**
	 * If the verification key matches, it updates the notification status to active.
	 *
	 * @param Notification $notification The notification to process.
	 * @param string       $action_key The action key to verify.
	 * @return void
	 */
	private function process_verification_action( Notification $notification, string $action_key ): void {
		if ( ! $notification->check_verification_key( $action_key ) ) {
			return;
		}

		// A re-hit of a still-valid verification URL (double-click, email prefetch,
		// link-scanner bot) leaves the row alone and falls through without redirecting.
		if ( ! $this->verify( $notification ) ) {
			return;
		}

		// We need a cookie-based session for notices to work on frontend pages.
		if ( WC()->session instanceof \WC_Session_Handler && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		$product = wc_get_product( $notification->get_product_id() );

		if ( $product instanceof \WC_Product ) {
			/* translators: %s is product name */
			$notice_text = sprintf( esc_html__( 'Successfully verified stock notifications for "%s".', 'woocommerce' ), $product->get_name() );
			wc_add_notice( $notice_text );
		}

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
		exit;
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
			$this->unsubscribe( $notification );

			// We need a cookie-based session for notices to work on frontend pages.
			if ( WC()->session instanceof \WC_Session_Handler && ! WC()->session->has_session() ) {
				WC()->session->set_customer_session_cookie( true );
			}

			$product = wc_get_product( $notification->get_product_id() );

			if ( $product instanceof \WC_Product ) {
				/* translators: %2$s product name, %1$s user email */
				$notice_text = sprintf( esc_html__( 'Successfully unsubscribed %1$s. You will not receive a notification when "%2$s" becomes available.', 'woocommerce' ), $notification->get_user_email(), $product->get_name() );
				wc_add_notice( $notice_text );
			}
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
			exit;
		}
	}

	/**
	 * Activate a notification, if it is still awaiting verification.
	 *
	 * Acts only on a `pending` notification, so a verification link followed after the row
	 * was cancelled, sent or already verified is a no-op. Without that guard a link issued
	 * before a cancellation would resurrect the row and send a "verified" email for a
	 * notification the shopper or the store had since called off.
	 *
	 * @since 11.2.0
	 *
	 * @param Notification $notification The notification to activate.
	 * @return bool True if the notification was activated, false if it was left unchanged.
	 */
	public function verify( Notification $notification ): bool {
		if ( NotificationStatus::PENDING !== $notification->get_status() ) {
			return false;
		}

		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_date_confirmed( time() );
		$notification->save();

		/**
		 * Action: woocommerce_customer_stock_notifications_verified
		 *
		 * Fires after a stock-notification signup has been verified via the
		 * double opt-in email link. Mirrors `woocommerce_customer_stock_notifications_signup`.
		 *
		 * @since 10.9.0
		 *
		 * @param Notification $notification The notification.
		 */
		do_action( 'woocommerce_customer_stock_notifications_verified', $notification );

		$this->email_manager->send_verified_email( $notification );

		return true;
	}

	/**
	 * Cancel a notification, if it is still eligible to be cancelled.
	 *
	 * Acts only on a `pending` or `active` notification, so calling this again on an
	 * already-cancelled or already-sent row is a no-op.
	 *
	 * @since 11.2.0
	 *
	 * @param Notification $notification The notification to cancel.
	 * @return bool True if the notification was cancelled, false if it was left unchanged.
	 */
	public function unsubscribe( Notification $notification ): bool {
		if ( ! in_array( $notification->get_status(), array( NotificationStatus::PENDING, NotificationStatus::ACTIVE ), true ) ) {
			return false;
		}

		$notification->set_status( NotificationStatus::CANCELLED );
		$notification->set_cancellation_source( NotificationCancellationSource::USER );
		$notification->set_date_cancelled( time() );
		$notification->save();

		/**
		 * Action: woocommerce_customer_stock_notifications_cancelled
		 *
		 * Fires after a shopper cancels a stock notification from an email link. Mirrors
		 * `woocommerce_customer_stock_notifications_signup`. Does not fire for a row that
		 * was already cancelled, or for a cancellation the store made on the shopper's
		 * behalf.
		 *
		 * @since 11.2.0
		 *
		 * @param Notification $notification The notification.
		 */
		do_action( 'woocommerce_customer_stock_notifications_cancelled', $notification );

		return true;
	}

	/**
	 * Retrieves the notification to be processed based on the provided notification ID.
	 *
	 * @param int $notification_id The ID of the notification to process.
	 * @return Notification|null The notification object if found, null otherwise.
	 */
	private function get_notification_to_be_processed( int $notification_id ): ?Notification {
		$notification = Factory::get_notification( (int) $notification_id );

		return $notification instanceof Notification ? $notification : null;
	}
}
