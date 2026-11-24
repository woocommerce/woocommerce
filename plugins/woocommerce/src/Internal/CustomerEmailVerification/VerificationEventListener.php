<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\Utilities\Users;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Verify only the recipient of a validated, emailed password credential.
 *
 * @since 11.0.0
 */
class VerificationEventListener {

	private const PROOF_META = '_wc_email_verification_reset_proof';

	/**
	 * Verification service.
	 *
	 * @var EmailVerificationService
	 */
	private $service;

	/** Register credential delivery and completion hooks. */
	public function __construct() {
		add_filter( 'woocommerce_email_recipient_customer_reset_password', array( $this, 'on_wc_email' ), PHP_INT_MAX, 3 );
		add_filter( 'woocommerce_email_recipient_customer_new_account', array( $this, 'on_wc_email' ), PHP_INT_MAX, 3 );
		add_filter( 'retrieve_password_notification_email', array( $this, 'on_core_email' ), PHP_INT_MAX, 4 );
		add_action( 'after_password_reset', array( $this, 'on_password_reset' ) );
	}

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 * @param EmailVerificationService $service Verification service.
	 */
	final public function init( EmailVerificationService $service ): void {
		$this->service = $service;
	}

	/**
	 * Bind WooCommerce password emails to their final recipient.
	 *
	 * @param mixed $recipient Filtered recipient.
	 * @param mixed $user Email customer.
	 * @param mixed $email Email instance.
	 * @return mixed Unchanged recipient.
	 */
	public function on_wc_email( $recipient, $user, $email ) {
		if ( $email instanceof \WC_Email_Customer_Reset_Password ) {
			$this->bind_credential( $user, $email->reset_key, $recipient );
			return $recipient;
		}
		if ( ! $email instanceof \WC_Email_Customer_New_Account || ! is_string( $email->set_password_url ) ) {
			return $recipient;
		}
		wp_parse_str( (string) wp_parse_url( $email->set_password_url, PHP_URL_QUERY ), $query );
		$this->bind_credential( $user, $query['key'] ?? null, $recipient );
		return $recipient;
	}

	/**
	 * Bind a WordPress reset notification to its recipient.
	 *
	 * @param mixed $email Notification arguments.
	 * @param mixed $key Reset credential.
	 * @param mixed $login Account login.
	 * @param mixed $user Account object.
	 * @return mixed Unchanged notification.
	 */
	public function on_core_email( $email, $key, $login, $user ) {
		$this->bind_credential( $user, $key, is_array( $email ) ? ( $email['to'] ?? null ) : null );
		return $email;
	}

	/**
	 * Store fingerprints, never the emailed credential or recipient.
	 *
	 * @param mixed $user Account object.
	 * @param mixed $key Reset credential.
	 * @param mixed $recipient Recipient address.
	 */
	private function bind_credential( $user, $key, $recipient ): void {
		if ( ! $user instanceof WP_User || ! is_string( $key ) ) {
			return;
		}
		$validated = check_password_reset_key( $key, $user->user_login );
		if ( ! $validated instanceof WP_User || $validated->ID !== $user->ID ) {
			return;
		}
		if ( ! is_string( $recipient ) || ! is_email( trim( $recipient ) ) ) {
			Users::delete_site_user_meta( $user->ID, self::PROOF_META );
			return;
		}
		Users::update_site_user_meta(
			$user->ID,
			self::PROOF_META,
			array(
				'key_hash'   => hash( 'sha256', $validated->user_activation_key ),
				'email_hash' => $this->email_fingerprint( $recipient ),
			)
		);
	}

	/**
	 * Consume proof only for the credential validated by the reset flow.
	 *
	 * @param mixed $user Account passed to reset_password before its key is cleared.
	 */
	public function on_password_reset( $user ): void {
		// A reset without its credential cannot establish who received the email.
		if ( ! $user instanceof WP_User || '' === $user->user_activation_key ) {
			return;
		}
		$proof   = Users::get_site_user_meta( $user->ID, self::PROOF_META );
		$current = get_user_by( 'id', $user->ID );
		// Require both the current account and a complete saved delivery proof.
		if ( ! $current instanceof WP_User || ! is_array( $proof ) || ! is_string( $proof['key_hash'] ?? null ) || ! is_string( $proof['email_hash'] ?? null ) ) {
			return;
		}
		// A newer or different reset credential cannot reuse this proof.
		if ( ! hash_equals( $proof['key_hash'], hash( 'sha256', $user->user_activation_key ) ) ) {
			return;
		}
		// The account must still use the address that received the credential.
		if ( ! hash_equals( $proof['email_hash'], $this->email_fingerprint( $current->user_email ) ) ) {
			return;
		}
		// Verification is allowed only after the matching proof is consumed.
		if ( ! Users::delete_site_user_meta( $user->ID, self::PROOF_META, $proof ) ) {
			return;
		}
		$this->service->mark_verified( $user->ID, $current->user_email );
	}

	/**
	 * Fingerprint a normalized recipient with the site's secret salt.
	 *
	 * @param string $email Recipient address.
	 * @return string Fingerprint.
	 */
	private function email_fingerprint( string $email ): string {
		return hash_hmac( 'sha256', strtolower( trim( $email ) ), wp_salt( 'auth' ) );
	}
}
