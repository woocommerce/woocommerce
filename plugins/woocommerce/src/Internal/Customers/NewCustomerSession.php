<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Customers;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;

/**
 * Tracks the customer account that a guest created during the current session.
 *
 * Creating an account at checkout does not log the shopper in. They set a password from the new
 * account email, which also verifies their email address. Until then, the session and the order
 * keep a link to the new account, so a failed payment can be retried and the shopper can view and
 * pay for their order. The links end once the account is claimed: verified, logged in to, or given a new password.
 *
 * @since 11.5.0
 */
class NewCustomerSession {

	/**
	 * Session key that stores the account created during this session, with the ID of the session that created it
	 * and the account's password fingerprint.
	 */
	private const SESSION_KEY = 'new_customer';

	/**
	 * Order meta that stores the password fingerprint of the account the order created at checkout.
	 */
	private const ORDER_FINGERPRINT_META_KEY = '_created_customer_fingerprint';

	/**
	 * User meta set on accounts this class creates. Any login removes it, which claims the account.
	 */
	private const UNCLAIMED_META_KEY = '_woocommerce_unclaimed_account';

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
		add_action( 'set_auth_cookie', array( $this, 'record_login' ), 10, 4 );
		add_action( 'woocommerce_customer_email_verified', array( $this, 'record_verification' ) );
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'remove_fingerprint_from_response' ) );
		add_filter( 'woocommerce_rest_prepare_shop_subscription_object', array( $this, 'remove_fingerprint_from_response' ) );
		add_filter( 'woocommerce_rest_api_v4_orders_item_response', array( $this, 'remove_fingerprint_from_response' ) );
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
	 * @param string               $email        Customer email address.
	 * @param array<string, mixed> $args         Extra user data passed to wc_create_new_customer().
	 * @param string               $username     Username, or an empty string to generate one.
	 * @param bool                 $link_session Whether this session keeps the account for checkout retries. Not needed after payment.
	 * @return int|\WP_Error Customer ID, or the error from wc_create_new_customer().
	 */
	public function create_customer( string $email, array $args = array(), string $username = '', bool $link_session = true ) {
		$customer_id = $this->get_reusable_customer_id( $email );

		if ( $customer_id ) {
			return $customer_id;
		}

		// Mark the account first thing, so a plugin that logs the new customer in while the account is created claims it.
		add_action( 'user_register', array( $this, 'mark_unclaimed' ), PHP_INT_MIN );
		$customer_id = wc_create_new_customer( $email, $username, '', $args );
		remove_action( 'user_register', array( $this, 'mark_unclaimed' ), PHP_INT_MIN );

		if ( $link_session && ! is_wp_error( $customer_id ) ) {
			$this->remember_customer( $customer_id );
		}

		return $customer_id;
	}

	/**
	 * Whether this session created an account that is still unclaimed.
	 *
	 * @since 11.5.0
	 *
	 * @return bool
	 */
	public function has_unclaimed_customer(): bool {
		return $this->get_unclaimed_customer_id() > 0;
	}

	/**
	 * Whether the order belongs to the account created in this session, and that account is still unclaimed.
	 *
	 * Only the session that created the account can keep it on an order, for example when a failed payment is retried.
	 *
	 * @since 11.5.0
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function is_session_customer_order( \WC_Order $order ): bool {
		$customer_id = $order->get_customer_id();

		return $customer_id > 0 && $customer_id === $this->get_unclaimed_customer_id();
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
			// A new order gets its key when it is first saved. Set it now, because the fingerprint uses it.
			if ( '' === $order->get_order_key() ) {
				$order->set_order_key( wc_generate_order_key() );
			}

			$order->update_meta_data( self::ORDER_FINGERPRINT_META_KEY, $this->get_password_fingerprint( $customer_id, $order->get_order_key() ) );
		}
	}

	/**
	 * Whether the order created its account, and that account is still unclaimed.
	 *
	 * The shopper has no password for the account yet, so a logged out visitor gets the guest order rules for it. This
	 * works in any session, such as a separate express checkout session, because the order records the account's
	 * password fingerprint, which no other account shares. Nobody can be logged in to an unclaimed account. The
	 * fingerprint includes the order key, so a copy of it on another order, such as a subscription renewal, does not count.
	 *
	 * @since 11.5.0
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function is_unclaimed_customer_order( \WC_Order $order ): bool {
		$customer_id = $order->get_customer_id();

		return ! is_user_logged_in()
			&& $customer_id > 0
			&& $this->is_unclaimed( $customer_id, (string) $order->get_meta( self::ORDER_FINGERPRINT_META_KEY ), $order->get_order_key() );
	}

	/**
	 * Remove the account fingerprint from order and subscription REST responses and webhook payloads. It is for
	 * WooCommerce only. Subscriptions copy order meta, so they can hold a copy of it.
	 *
	 * @internal
	 *
	 * @param mixed $response Order response.
	 * @return mixed
	 */
	public function remove_fingerprint_from_response( $response ) {
		if ( ! $response instanceof \WP_REST_Response ) {
			return $response;
		}

		$data = $response->get_data();

		if ( ! isset( $data['meta_data'] ) || ! is_array( $data['meta_data'] ) ) {
			return $response;
		}

		$data['meta_data'] = array_values(
			array_filter(
				$data['meta_data'],
				static function ( $meta ): bool {
					$key = $meta instanceof \WC_Meta_Data ? $meta->key : ( $meta['key'] ?? '' );

					return self::ORDER_FINGERPRINT_META_KEY !== $key;
				}
			)
		);
		$response->set_data( $data );

		return $response;
	}

	/**
	 * Give a card saved for a logged out shopper to the account they created in this session.
	 *
	 * Some gateways save the card for the current user, for example in a 3D Secure step after checkout. The shopper is
	 * not logged in after creating an account, so the card would otherwise belong to nobody.
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

		$customer_id = $this->get_unclaimed_customer_id();

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
	 * Keep "Create an account" ticked while this session has an unclaimed account, so a retry reuses it instead of
	 * placing a guest order.
	 *
	 * @internal
	 *
	 * @param mixed $checked Whether the box is ticked by default.
	 * @return mixed
	 */
	public function keep_create_account_checked( $checked ) {
		return $this->has_unclaimed_customer() ? true : $checked;
	}

	/**
	 * Mark a new account as unclaimed.
	 *
	 * @internal
	 *
	 * @param mixed $user_id User ID.
	 */
	public function mark_unclaimed( $user_id ): void {
		update_user_meta( absint( $user_id ), self::UNCLAIMED_META_KEY, '1' );
	}

	/**
	 * Claim a new account when someone logs in to it, so the links to it end.
	 *
	 * This covers logins that do not use the password, such as social login or single sign-on. A login does not verify
	 * the email address.
	 *
	 * @internal
	 *
	 * @param mixed $auth_cookie Authentication cookie value.
	 * @param mixed $expire      Login grace period expiry.
	 * @param mixed $expiration  Cookie expiry.
	 * @param mixed $user_id     User ID.
	 */
	public function record_login( $auth_cookie, $expire, $expiration, $user_id ): void {
		unset( $auth_cookie, $expire, $expiration );
		$user_id = absint( $user_id );

		if ( $user_id && get_user_meta( $user_id, self::UNCLAIMED_META_KEY, true ) ) {
			delete_user_meta( $user_id, self::UNCLAIMED_META_KEY );
		}
	}

	/**
	 * Claim a new account when its email address is verified, so clearing the verification later does not reopen the links.
	 *
	 * @internal
	 *
	 * @param mixed $user_id User ID.
	 */
	public function record_verification( $user_id ): void {
		$user_id = absint( $user_id );

		if ( $user_id && get_user_meta( $user_id, self::UNCLAIMED_META_KEY, true ) ) {
			delete_user_meta( $user_id, self::UNCLAIMED_META_KEY );
		}
	}

	/**
	 * Get the account created in this session when it can be reused for the given email address.
	 *
	 * @param string $email Customer email address.
	 * @return int Customer ID, or 0 when there is no account to reuse.
	 */
	private function get_reusable_customer_id( string $email ): int {
		$customer_id = $this->get_unclaimed_customer_id();
		$user        = $customer_id ? get_user_by( 'id', $customer_id ) : false;

		return $user instanceof \WP_User && 0 === strcasecmp( $user->user_email, $email ) ? $customer_id : 0;
	}

	/**
	 * Get the account created in this session while it is unclaimed.
	 *
	 * Logged in users never use this account: a guest session moves to whoever logs in on the same browser, and must
	 * not carry the account over. A session cloned from a shared checkout link gets a new session ID, so it does not
	 * carry the account over either.
	 *
	 * @return int Customer ID, or 0 when there is none.
	 */
	private function get_unclaimed_customer_id(): int {
		$session = WC()->session;

		if ( is_user_logged_in() || ! $session instanceof \WC_Session ) {
			return 0;
		}

		$stored = $session->get( self::SESSION_KEY );

		if ( ! is_array( $stored ) || ( $stored['session'] ?? '' ) !== (string) $session->get_customer_id() ) {
			return 0;
		}

		$customer_id = absint( $stored['id'] ?? 0 );

		return $customer_id && $this->is_unclaimed( $customer_id, (string) ( $stored['fingerprint'] ?? '' ) ) ? $customer_id : 0;
	}

	/**
	 * Store the ID of the account created in this session.
	 *
	 * @param int $customer_id Customer ID.
	 */
	private function remember_customer( int $customer_id ): void {
		$session = WC()->session;

		if ( $session instanceof \WC_Session ) {
			$session->set(
				self::SESSION_KEY,
				array(
					'id'          => $customer_id,
					'session'     => (string) $session->get_customer_id(),
					'fingerprint' => $this->get_password_fingerprint( $customer_id ),
				)
			);
		}
	}

	/**
	 * Whether nobody has claimed the account since the link to it was stored.
	 *
	 * Verifying the email claims the account. So does logging in to it, or any password change, even one that does
	 * not verify the email, such as an admin setting the password.
	 *
	 * @param int    $customer_id Customer ID.
	 * @param string $fingerprint Password fingerprint stored with the link.
	 * @param string $order_key   Key of the order that stored the link, or an empty string for the session link.
	 * @return bool
	 */
	private function is_unclaimed( int $customer_id, string $fingerprint, string $order_key = '' ): bool {
		return '' !== $fingerprint
			&& hash_equals( $this->get_password_fingerprint( $customer_id, $order_key ), $fingerprint )
			&& get_user_meta( $customer_id, self::UNCLAIMED_META_KEY, true )
			&& ! $this->email_verification_service->is_verified( $customer_id );
	}

	/**
	 * Get a keyed hash of the account's stored password hash. It changes whenever the password changes.
	 *
	 * @param int    $customer_id Customer ID.
	 * @param string $order_key   Key of the order the fingerprint is for, or an empty string for the session link.
	 * @return string Fingerprint, or an empty string when the account does not exist.
	 */
	private function get_password_fingerprint( int $customer_id, string $order_key = '' ): string {
		$user = get_user_by( 'id', $customer_id );

		return $user instanceof \WP_User ? wp_hash( $user->user_pass . '|' . $order_key ) : '';
	}
}
