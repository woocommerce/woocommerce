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
			// A deleted sign-up gets the same generic notice as a bad key, so the
			// response does not reveal whether the notification ever existed.
			if ( self::ACTION_VERIFY === $action || self::ACTION_UNSUBSCRIBE === $action ) {
				$this->redirect_with_notice( $action, $this->get_invalid_link_notice_text( $action ), 'error' );
			}
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
	 * Verifies a pending notification when the verification key matches.
	 *
	 * Expired, superseded or otherwise invalid keys, as well as notifications
	 * that are no longer pending, redirect with a notice instead of failing silently.
	 *
	 * @param Notification $notification The notification to process.
	 * @param string       $action_key The action key to verify.
	 * @return void
	 */
	private function process_verification_action( Notification $notification, string $action_key ): void {
		// A mismatched key gets the generic notice regardless of status, so the
		// response does not reveal the sign-up's state to anyone guessing keys.
		if ( ! $notification->verification_key_matches( $action_key ) ) {
			$this->redirect_with_notice( self::ACTION_VERIFY, $this->get_invalid_link_notice_text( self::ACTION_VERIFY ), 'error' );
		}

		// Guard against re-hits of a verification URL (double-click, email prefetch,
		// link-scanner bots). Without this, each hit would re-dispatch the verified email.
		// Checked before expiry so a verified sign-up reports as verified even once its link is stale.
		$status = $notification->get_status();
		if ( NotificationStatus::PENDING !== $status ) {
			$notice_text = NotificationStatus::CANCELLED === $status
				? esc_html__( 'This sign-up has been cancelled, so it can no longer be verified.', 'woocommerce' )
				: esc_html__( 'This sign-up has already been verified.', 'woocommerce' );
			$this->redirect_with_notice( self::ACTION_VERIFY, $notice_text, 'notice' );
		}

		if ( $notification->is_verification_key_expired() ) {
			$this->redirect_with_notice( self::ACTION_VERIFY, esc_html__( 'This verification link has expired. Sign up again to request a new one.', 'woocommerce' ), 'error' );
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

		$product = wc_get_product( $notification->get_product_id() );

		/* translators: %s is product name */
		$notice_text = sprintf( esc_html__( 'Successfully verified stock notifications for "%s".', 'woocommerce' ), $product->get_name() );
		$this->redirect_with_notice( self::ACTION_VERIFY, $notice_text );
	}

	/**
	 * If the unsubscribe key matches, it cancels the notification, unless it was already sent or cancelled.
	 *
	 * An invalid key redirects with a notice instead of failing silently.
	 *
	 * @param Notification $notification The Notification to process.
	 * @param string       $action_key The action key to verify.
	 * @return void
	 */
	private function process_unsubscribe_action( Notification $notification, string $action_key ): void {
		if ( ! $notification->check_unsubscribe_key( $action_key ) ) {
			$this->redirect_with_notice( self::ACTION_UNSUBSCRIBE, $this->get_invalid_link_notice_text( self::ACTION_UNSUBSCRIBE ), 'error' );
		}

		$status = $notification->get_status();

		if ( NotificationStatus::SENT === $status ) {
			$this->redirect_with_notice( self::ACTION_UNSUBSCRIBE, esc_html__( 'This notification has already been sent, so there is nothing to unsubscribe from.', 'woocommerce' ), 'notice' );
		}

		if ( NotificationStatus::ACTIVE !== $status && NotificationStatus::PENDING !== $status ) {
			$this->redirect_with_notice( self::ACTION_UNSUBSCRIBE, esc_html__( 'You are already unsubscribed from this notification.', 'woocommerce' ), 'notice' );
		}

		$notification->set_status( NotificationStatus::CANCELLED );
		$notification->set_cancellation_source( NotificationCancellationSource::USER );
		$notification->set_date_cancelled( time() );
		$notification->save();

		$product = wc_get_product( $notification->get_product_id() );

		/* translators: %2$s product name, %1$s user email */
		$notice_text = sprintf( esc_html__( 'Successfully unsubscribed %1$s. You will not receive a notification when "%2$s" becomes available.', 'woocommerce' ), $notification->get_user_email(), $product->get_name() );
		$this->redirect_with_notice( self::ACTION_UNSUBSCRIBE, $notice_text );
	}

	/**
	 * Returns the generic notice shown when an email link cannot be matched to a sign-up.
	 *
	 * @since 11.3.0
	 *
	 * @param string $action The email action: 'verify' or 'unsubscribe'.
	 * @return string
	 */
	private function get_invalid_link_notice_text( string $action ): string {
		return self::ACTION_VERIFY === $action
			? esc_html__( 'This verification link is no longer valid. Please use the link in the most recent verification email.', 'woocommerce' )
			: esc_html__( 'This unsubscribe link is no longer valid.', 'woocommerce' );
	}

	/**
	 * Adds a frontend notice and redirects to the destination page of the given email action.
	 *
	 * @since 11.3.0
	 *
	 * @param string $action      The email action: 'verify' or 'unsubscribe'.
	 * @param string $notice_text The notice text to show after the redirect.
	 * @param string $notice_type The notice type: 'success', 'notice' or 'error'.
	 * @return never
	 */
	private function redirect_with_notice( string $action, string $notice_text, string $notice_type = 'success' ): void {
		// We need a cookie-based session for notices to work on frontend pages.
		if ( WC()->session instanceof \WC_Session_Handler && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		wc_add_notice( $notice_text, $notice_type );

		$url = wc_get_page_permalink( 'shop' );

		if ( self::ACTION_VERIFY === $action ) {
			/**
			 * `woocommerce_customer_stock_notification_verified_redirect_url` filter.
			 *
			 * @since 10.2.0
			 *
			 * @param  string  $url
			 * @return string
			 */
			$url = apply_filters( 'woocommerce_customer_stock_notification_verified_redirect_url', $url );
		} else {
			/**
			 * `woocommerce_customer_stock_notification_unsubscribe_redirect_url` filter.
			 *
			 * @since 10.2.0
			 *
			 * @param  string  $url
			 * @return string
			 */
			$url = apply_filters( 'woocommerce_customer_stock_notification_unsubscribe_redirect_url', $url );
		}

		wp_safe_redirect( $url );
		exit;
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
