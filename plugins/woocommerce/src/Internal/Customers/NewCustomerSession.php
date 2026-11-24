<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Customers;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;

/**
 * Tracks the customer account that a guest created during the current session.
 *
 * Creating an account at checkout does not log the shopper in. They set a password from the new
 * account email, which also verifies their email address. Until then, the session keeps the new
 * account ID so a failed payment can be retried, and so the shopper can view and pay for their order.
 *
 * @since 11.5.0
 */
class NewCustomerSession {

	/**
	 * Session key that stores the account created during this session, with the ID of the session that created it.
	 */
	private const SESSION_KEY = 'new_customer';

	/**
	 * Order meta that stores the ID of the account the order created at checkout.
	 */
	private const ORDER_META_KEY = '_created_customer_id';

	/**
	 * Email verification service.
	 *
	 * @var EmailVerificationService
	 */
	private EmailVerificationService $email_verification_service;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_new_payment_token', array( $this, 'assign_guest_payment_token' ), 10, 2 );
		add_filter( 'woocommerce_create_account_default_checked', array( $this, 'keep_create_account_checked' ) );
	}

	/**
	 * Initialize the class with its dependencies.
	 *
	 * @internal
	 *
	 * @param EmailVerificationService $email_verification_service Email verification service.
	 */
	final public function init( EmailVerificationService $email_verification_service ): void {
		$this->email_verification_service = $email_verification_service;
	}

	/**
	 * Create a customer account with a generated password, or reuse the account this session already created.
	 *
	 * The new account email sends the shopper a link to set their password. Reuse lets a shopper retry
	 * a failed payment without an "account already exists" error for an account they cannot log in to.
	 * A reused account keeps its username, because WordPress usernames cannot be changed.
	 *
	 * @since 11.5.0
	 *
	 * @param string               $email    Customer email address.
	 * @param array<string, mixed> $args     Extra user data passed to wc_create_new_customer().
	 * @param string               $username Username, or an empty string to generate one.
	 * @return int|\WP_Error Customer ID, or the error from wc_create_new_customer().
	 */
	public function create_customer( string $email, array $args = array(), string $username = '' ) {
		$customer_id = $this->get_reusable_customer_id( $email );

		if ( $customer_id ) {
			return $customer_id;
		}

		$customer_id = wc_create_new_customer( $email, $username, '', $args );

		if ( ! is_wp_error( $customer_id ) ) {
			$this->set_customer_id( $customer_id );
		}

		return $customer_id;
	}

	/**
	 * Whether this session created an account that has not verified its email.
	 *
	 * @since 11.5.0
	 *
	 * @return bool
	 */
	public function has_unverified_customer(): bool {
		return $this->get_unverified_customer_id() > 0;
	}

	/**
	 * Whether the order belongs to the account created in this session, and that account has not verified its email.
	 *
	 * Only the session that created the account can keep it on an order, for example when a failed payment is retried.
	 *
	 * @since 11.5.0
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function is_unverified_new_customer_order( \WC_Order $order ): bool {
		$customer_id = $order->get_customer_id();

		return $customer_id > 0 && $customer_id === $this->get_unverified_customer_id();
	}

	/**
	 * Record on the order that it created its customer's account. The caller saves the order.
	 *
	 * @since 11.5.0
	 *
	 * @param \WC_Order $order       Order object.
	 * @param int       $customer_id ID of the account the order created.
	 */
	public function mark_order_created_customer( \WC_Order $order, int $customer_id ): void {
		if ( $customer_id > 0 && $order->get_customer_id() === $customer_id ) {
			$order->update_meta_data( self::ORDER_META_KEY, (string) $customer_id );
		}
	}

	/**
	 * Whether the order created its account, and that account has not confirmed its email address yet.
	 *
	 * The account has no password until then, so the order received and pay pages treat its order like a guest order.
	 * Unlike {@see self::is_unverified_new_customer_order()}, this works in any session, such as a separate express
	 * checkout session, because the order records the account it created.
	 *
	 * @since 11.5.0
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function is_unconfirmed_new_account_order( \WC_Order $order ): bool {
		if ( $this->is_unverified_new_customer_order( $order ) ) {
			return true;
		}

		$customer_id = $order->get_customer_id();

		return $customer_id > 0
			&& (int) $order->get_meta( self::ORDER_META_KEY ) === $customer_id
			&& ! $this->email_verification_service->is_verified( $customer_id );
	}

	/**
	 * Give a card saved for a logged out shopper to the account they created in this session.
	 *
	 * Some gateways save the card for the current user in a request after checkout, such as the 3D Secure step in
	 * WooPayments. The shopper is not logged in after creating an account, so the card would otherwise belong to nobody.
	 *
	 * @internal
	 *
	 * @param mixed $token_id Payment token ID.
	 * @param mixed $token    Payment token.
	 */
	public function assign_guest_payment_token( $token_id, $token ): void {
		unset( $token_id );

		if ( ! $token instanceof \WC_Payment_Token || 0 !== $token->get_user_id() ) {
			return;
		}

		$customer_id = $this->get_unverified_customer_id();

		if ( ! $customer_id ) {
			return;
		}

		$token->set_user_id( $customer_id );
		if ( null === \WC_Payment_Tokens::get_customer_default_token( $customer_id ) ) {
			$token->set_default( true );
		}
		$token->save();
	}

	/**
	 * Keep "Create an account" ticked after a failed payment reloads the checkout form.
	 *
	 * The form only reloads for a chosen username (see {@see \WC_Checkout::process_customer()}). Without this, the
	 * box would come back empty, and a retry would place a guest order instead of using the new account.
	 *
	 * @internal
	 *
	 * @param mixed $checked Whether the box is ticked by default.
	 * @return mixed
	 */
	public function keep_create_account_checked( $checked ) {
		if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) && $this->has_unverified_customer() ) {
			return true;
		}

		return $checked;
	}

	/**
	 * Get the account created in this session when it can be reused for the given email address.
	 *
	 * @param string $email Customer email address.
	 * @return int Customer ID, or 0 when there is no account to reuse.
	 */
	private function get_reusable_customer_id( string $email ): int {
		$customer_id = $this->get_unverified_customer_id();
		$user        = $customer_id ? get_user_by( 'id', $customer_id ) : false;

		return $user instanceof \WP_User && 0 === strcasecmp( $user->user_email, $email ) ? $customer_id : 0;
	}

	/**
	 * Get the account created in this session while its email is unverified.
	 *
	 * A verified account has a password the shopper chose, so the shopper logs in instead. Logged in users never use
	 * this account: a guest session moves to whoever logs in on the same browser, and must not carry the account over.
	 * A session cloned from a shared checkout link gets a new session ID, so it does not carry the account over either.
	 *
	 * @return int Customer ID, or 0 when there is none.
	 */
	private function get_unverified_customer_id(): int {
		$session = WC()->session;

		if ( is_user_logged_in() || ! $session instanceof \WC_Session ) {
			return 0;
		}

		$stored      = $session->get( self::SESSION_KEY );
		$customer_id = is_array( $stored ) && ( $stored['session'] ?? '' ) === (string) $session->get_customer_id() ? absint( $stored['id'] ?? 0 ) : 0;

		return $customer_id && ! $this->email_verification_service->is_verified( $customer_id ) ? $customer_id : 0;
	}

	/**
	 * Store the ID of the account created in this session.
	 *
	 * @param int $customer_id Customer ID.
	 */
	private function set_customer_id( int $customer_id ): void {
		$session = WC()->session;

		// The order confirmation page can create an account without a session, for example from an emailed link.
		if ( $session instanceof \WC_Session_Handler && ! $session->has_session() ) {
			$session->set_customer_session_cookie( true );
		}

		if ( $session instanceof \WC_Session ) {
			$session->set(
				self::SESSION_KEY,
				array(
					'id'      => $customer_id,
					'session' => (string) $session->get_customer_id(),
				)
			);
		}
	}
}
