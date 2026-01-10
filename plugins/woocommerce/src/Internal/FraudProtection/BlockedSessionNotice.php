<?php
/**
 * BlockedSessionNotice class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Handles blocked session messaging for fraud protection.
 *
 * This class provides:
 * - Hook into shortcode checkout to display blocked notice
 * - Message generation for both HTML (shortcode) and plaintext (Store API) contexts
 *
 * Note: Store API (block checkout) and payment gateway filtering are handled
 * directly in WC Core classes (Checkout.php and WC_Payment_Gateways).
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class BlockedSessionNotice implements RegisterHooksInterface {

	/**
	 * Session clearance manager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private SessionClearanceManager $session_manager;

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param SessionClearanceManager $session_manager The session clearance manager instance.
	 */
	final public function init( SessionClearanceManager $session_manager ): void {
		$this->session_manager = $session_manager;
	}

	/**
	 * Register hooks for displaying blocked notice.
	 *
	 * This method should only be called when fraud protection is enabled.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_before_checkout_form', array( $this, 'display_blocked_notice' ), 1, 0 );
	}

	/**
	 * Display blocked notice on shortcode checkout page.
	 *
	 * Shows a user-friendly message explaining that the request cannot be
	 * processed online and provides contact information for support.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function display_blocked_notice(): void {
		if ( ! $this->session_manager->is_session_blocked() ) {
			return;
		}

		wc_print_notice( $this->get_message_html(), 'error' );
	}

	/**
	 * Get the blocked session message as HTML.
	 *
	 * Includes a mailto link for the support email. Used by shortcode checkout.
	 *
	 * @return string HTML message with mailto link.
	 */
	public function get_message_html(): string {
		$email = WC()->mailer()->get_from_address();

		return sprintf(
			/* translators: %1$s: mailto link, %2$s: email address */
			__( 'We are unable to process this request online. Please <a href="%1$s">contact support (%2$s)</a> to complete your purchase.', 'woocommerce' ),
			esc_url( 'mailto:' . $email ),
			esc_html( $email )
		);
	}

	/**
	 * Get the blocked session message as plaintext.
	 *
	 * Used by Store API responses where HTML is not supported.
	 *
	 * @return string Plaintext message with email address.
	 */
	public function get_message_plaintext(): string {
		$email = WC()->mailer()->get_from_address();

		return sprintf(
			/* translators: %s: support email address */
			__( 'We are unable to process this request online. Please contact support (%s) to complete your purchase.', 'woocommerce' ),
			$email
		);
	}
}
