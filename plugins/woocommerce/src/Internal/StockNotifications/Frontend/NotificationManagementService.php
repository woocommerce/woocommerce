<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;

/**
 * Notification management service.
 */
class NotificationManagementService {

	/**
	 * Query argument carrying the notification id for a resend-verification request.
	 */
	public const RESEND_QUERY_ARG = 'wc_bis_resend_notification';

	/**
	 * Nonce action for resend-verification URLs.
	 */
	public const RESEND_NONCE_ACTION = 'wc_bis_resend_verification_email_nonce';

	/**
	 * Meta key tracking the last time a verification email was dispatched.
	 *
	 * Used to rate-limit the frontend resend endpoint.
	 */
	public const LAST_VERIFY_EMAIL_SENT_META = '_last_verify_email_sent_at';

	/**
	 * Minimum seconds between back-to-back resend requests.
	 */
	public const RESEND_RATE_LIMIT_SECONDS = 60;

	/**
	 * Email manager.
	 *
	 * @var EmailManager
	 */
	private EmailManager $email_manager;

	/**
	 * Init the service.
	 *
	 * @internal
	 *
	 * @param EmailManager $email_manager The email manager.
	 */
	final public function init( EmailManager $email_manager ): void {
		$this->email_manager = $email_manager;

		add_action( 'template_redirect', array( $this, 'maybe_process_resend_request' ) );
	}

	/**
	 * Get resend verification email URL.
	 *
	 * @param Notification $notification The notification.
	 * @return string The resend verification email URL.
	 */
	public function get_resend_verification_email_url( Notification $notification ): string {
		$url = add_query_arg(
			array(
				self::RESEND_QUERY_ARG => $notification->get_id(),
			),
			$notification->get_product_permalink()
		);

		return wp_nonce_url( $url, self::RESEND_NONCE_ACTION . '_' . $notification->get_id() );
	}

	/**
	 * Handle the resend-verification request if the current request carries one.
	 */
	public function maybe_process_resend_request(): void {
		// Only run on frontend GET requests — skip admin, POST, CLI, etc. before doing any nonce/DB work.
		if ( is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.Recommended
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( 'GET' !== $method ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ self::RESEND_QUERY_ARG ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notification_id = absint( wp_unslash( $_GET[ self::RESEND_QUERY_ARG ] ) );
		$nonce           = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( empty( $notification_id ) ) {
			return;
		}

		// Scope the nonce per-notification so one valid resend URL cannot be replayed
		// across the notification id query arg to trigger emails for other customers.
		if ( ! wp_verify_nonce( $nonce, self::RESEND_NONCE_ACTION . '_' . $notification_id ) ) {
			return;
		}

		$notification = Factory::get_notification( $notification_id );
		if ( ! $notification instanceof Notification ) {
			return;
		}

		$this->ensure_notice_session();

		$redirect_url = $notification->get_product_permalink();
		if ( empty( $redirect_url ) ) {
			$redirect_url = wc_get_page_permalink( 'shop' );
		}

		if ( NotificationStatus::PENDING !== $notification->get_status() ) {
			wc_add_notice( esc_html__( 'This notification is already verified or cancelled.', 'woocommerce' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$last_sent_at = (int) $notification->get_meta( self::LAST_VERIFY_EMAIL_SENT_META );
		if ( $last_sent_at > 0 && ( time() - $last_sent_at ) < self::RESEND_RATE_LIMIT_SECONDS ) {
			wc_add_notice( esc_html__( 'Please wait a moment before requesting another verification email.', 'woocommerce' ), 'notice' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Persist the rate-limit timestamp before dispatching the email so two near-simultaneous
		// requests can't both pass the rate-limit check and trigger duplicate sends.
		$notification->update_meta_data( self::LAST_VERIFY_EMAIL_SENT_META, (string) time() );
		$notification->save();

		$this->email_manager->send_verify_email( $notification );

		/* translators: %s user email. */
		wc_add_notice( sprintf( esc_html__( 'Verification email sent to %s.', 'woocommerce' ), $notification->get_user_email() ), 'success' );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Ensure there is a cookie-based session so frontend notices survive the redirect.
	 */
	private function ensure_notice_session(): void {
		if ( WC()->session instanceof \WC_Session_Handler && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}
}
