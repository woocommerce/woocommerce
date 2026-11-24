<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\Customers\NewCustomerSession;

/**
 * Decides who can see an order on the order received page.
 *
 * The order received shortcode and the order confirmation blocks share these rules. Every visitor needs the order key.
 * A logged in owner then sees the order. Other orders that belong to a customer need a login. A guest sees a guest
 * order for a short time after checkout, and after that unlocks it by entering the billing email address. A new account
 * has no password until its owner confirms the email address, so its order is treated like a guest order until then.
 *
 * @since 11.5.0
 */
class OrderReceivedAccess {

	/**
	 * Context passed to the email verification filters.
	 */
	private const CONTEXT = 'order-received';

	/**
	 * Tracks the account created at checkout.
	 *
	 * @var NewCustomerSession
	 */
	private NewCustomerSession $new_customer_session;

	/**
	 * Initialize the class with its dependencies.
	 *
	 * @internal
	 *
	 * @param NewCustomerSession $new_customer_session Tracks the account created at checkout.
	 */
	final public function init( NewCustomerSession $new_customer_session ): void {
		$this->new_customer_session = $new_customer_session;
	}

	/**
	 * Whether the current visitor can see the order details without entering an email address.
	 *
	 * @since 11.5.0
	 *
	 * @param \WC_Order $order     Order object.
	 * @param string    $order_key Order key from the request.
	 * @return bool
	 */
	public function current_user_can_view_order( \WC_Order $order, string $order_key ): bool {
		if ( ! $this->key_matches( $order, $order_key ) ) {
			return false;
		}

		$customer_id = $order->get_customer_id();

		if ( $customer_id && get_current_user_id() === $customer_id ) {
			return true;
		}

		if ( $this->needs_login( $order ) ) {
			return ! $this->verify_known_shoppers();
		}

		// An order without a billing email can never be unlocked, so there is nothing to ask for.
		if ( '' === (string) $order->get_billing_email() || $this->is_session_order( $order ) || $this->is_within_grace_period( $order ) ) {
			return true;
		}

		/**
		 * Provides an opportunity to override the (potential) requirement for shoppers to verify their email address
		 * before we show information such as the order summary, or order payment page.
		 *
		 * @since 7.9.0
		 *
		 * @param bool      $email_verification_required If email verification is required.
		 * @param \WC_Order $order                       The relevant order.
		 * @param string    $context                     The context under which we are performing this check.
		 */
		return ! apply_filters( 'woocommerce_order_email_verification_required', true, $order, self::CONTEXT );
	}

	/**
	 * Whether a guest can unlock the order details by entering the order's billing email address.
	 *
	 * Guests can unlock guest orders while guest checkout is on. They can also unlock the order of a new account that has
	 * no password yet, because stores without guest checkout create these accounts too. Other customer orders need a login.
	 *
	 * @since 11.5.0
	 *
	 * @param \WC_Order $order     Order object.
	 * @param string    $order_key Order key from the request.
	 * @return bool
	 */
	public function guest_can_unlock_order( \WC_Order $order, string $order_key ): bool {
		if ( ! $this->key_matches( $order, $order_key ) || '' === (string) $order->get_billing_email() ) {
			return false;
		}

		if ( $order->get_customer_id() ) {
			return ! $this->needs_login( $order );
		}

		return 'yes' === get_option( 'woocommerce_enable_guest_checkout' );
	}

	/**
	 * Whether an email address that a guest entered matches the order's billing email address.
	 *
	 * The caller checks the form nonce first.
	 *
	 * @since 11.5.0
	 *
	 * @param \WC_Order $order Order object.
	 * @param string    $email Email address the guest entered.
	 * @return bool
	 */
	public function email_unlocks_order( \WC_Order $order, string $email ): bool {
		$billing_email = (string) $order->get_billing_email();

		return '' !== $billing_email && '' !== $email && 0 === strcasecmp( $email, $billing_email );
	}

	/**
	 * Whether the order was created within the grace period, when guests see it without entering an email address.
	 *
	 * @since 11.5.0
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function is_within_grace_period( \WC_Order $order ): bool {
		/**
		 * Controls the grace period within which we do not require any sort of email verification step before rendering
		 * the 'order received' or 'order pay' pages.
		 *
		 * To eliminate the grace period, set to zero (or to a negative value).
		 *
		 * @since 8.0.0
		 *
		 * @param int       $grace_period Time in seconds after an order is placed before email verification may be required.
		 * @param \WC_Order $order        The order for which this grace period is being assessed.
		 * @param string    $context      Indicates the context in which we might verify the email address. Typically 'order-pay' or 'order-received'.
		 */
		$grace_period = (int) apply_filters( 'woocommerce_order_email_verification_grace_period', 10 * MINUTE_IN_SECONDS, $order, self::CONTEXT );
		$date_created = $order->get_date_created();

		return $date_created instanceof \WC_DateTime && time() - $date_created->getTimestamp() <= $grace_period;
	}

	/**
	 * Whether the order key from the request matches the order. An empty key never matches.
	 *
	 * @param \WC_Order $order     Order object.
	 * @param string    $order_key Order key from the request.
	 * @return bool
	 */
	private function key_matches( \WC_Order $order, string $order_key ): bool {
		return '' !== $order_key && $order->key_is_valid( $order_key );
	}

	/**
	 * Whether the order belongs to a customer account that a visitor has to log in to.
	 *
	 * A new account has no password until its owner confirms the email address, so its order does not need a login.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	private function needs_login( \WC_Order $order ): bool {
		return $order->get_customer_id() > 0 && ! $this->new_customer_session->is_unconfirmed_new_account_order( $order );
	}

	/**
	 * Whether customers must log in to see their orders.
	 *
	 * @return bool
	 */
	private function verify_known_shoppers(): bool {
		/**
		 * Indicates if known (non-guest) shoppers need to be logged in before we let
		 * them access the order received page.
		 *
		 * @since 8.4.0
		 *
		 * @param bool $verify_known_shoppers If verification is required.
		 */
		return (bool) apply_filters( 'woocommerce_order_received_verify_known_shoppers', true );
	}

	/**
	 * Whether the current session still holds the order as its checkout draft.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	private function is_session_order( \WC_Order $order ): bool {
		$session  = WC()->session;
		$draft_id = $session instanceof \WC_Session ? (int) $session->get( 'store_api_draft_order' ) : 0;

		return $draft_id > 0 && $order->get_id() === $draft_id;
	}
}
