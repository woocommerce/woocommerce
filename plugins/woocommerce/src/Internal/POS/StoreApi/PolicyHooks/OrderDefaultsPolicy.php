<?php
/**
 * OrderDefaultsPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Enums\OrderItemType;
use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;

/**
 * Aligns Store API order defaults with the realities of an in-store POS sale.
 *
 * The Store API's {@see \Automattic\WooCommerce\StoreApi\Utilities\OrderController::update_order_from_cart}
 * is built around the web-checkout assumption that the authenticated user IS
 * the customer. It therefore hard-codes four things into the draft order
 * before any extension can intervene:
 *
 *   1. `customer_id = get_current_user_id()` — the cashier's WP user_id ends
 *      up attributed to the order. In POS that's wrong on its face: the
 *      cashier is not the customer, and POS doesn't (yet) support choosing
 *      one. Customer attribution must be 0 (anonymous in-store sale).
 *   2. `billing_*` / `shipping_*` are copied from `wc()->customer`, which for
 *      a logged-in admin returns the admin's saved profile address. The
 *      receipt would show the cashier's home address as the customer's.
 *   3. `payment_method = PaymentUtils::get_default_payment_method()` — the
 *      first enabled gateway (typically WooPayments) is stamped on the order
 *      even though no tender has been picked yet. The actual payment method
 *      is decided post-order by the cashier and recorded by a separate REST
 *      call ({@see \WooCommerce_Payments capture flow} for card, the existing
 *      `PUT /wc/v3/orders/{id}` `set_paid` path for cash).
 *   4. `wc()->cart->needs_shipping()` is true for any physical product, so
 *      the cart computes shipping packages and `update_line_items_from_cart`
 *      stamps a `shipping` line item on the order. POS is by definition an
 *      in-person tender — there is no shipping to compute.
 *
 * Because none of those four assignments expose a filter where they happen,
 * this policy works at the two layers it can:
 *
 *   - `woocommerce_cart_needs_shipping` (pre-order): short-circuit shipping
 *     calculation for POS so the cart never produces shipping packages, and
 *     `update_line_items_from_cart` therefore never adds a shipping line.
 *   - `woocommerce_store_api_checkout_order_processed` (post-validation,
 *     pre-payment): zero out customer_id, the address bag and the default
 *     payment_method on the draft order before it is handed to the payment
 *     stage. Running at high priority means extension callbacks on the
 *     same hook have already seen the order as-built and can record their
 *     own state (gift cards, subscriptions, etc.) before the wipe.
 *
 * The defensive `remove_order_items( SHIPPING )` in the same callback is a
 * belt-and-suspenders for the rare case where something added a shipping
 * line out-of-band of the cart calculation.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class OrderDefaultsPolicy implements RegisterHooksInterface {

	/**
	 * Priority at which we run our order-defaults reset. Deliberately late
	 * so that any extension callback attached to the same action at default
	 * priority observes the order as the Store API built it (with admin
	 * attribution etc.) before we overwrite the POS-specific fields.
	 */
	private const RESET_PRIORITY = 999;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_cart_needs_shipping', array( $this, 'no_shipping_for_pos' ) );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'reset_order_defaults_for_pos' ), self::RESET_PRIORITY );
	}

	/**
	 * Short-circuit cart shipping for POS so no shipping packages are
	 * generated and no shipping line is stamped on the resulting order.
	 *
	 * @param bool $needs_shipping Original value from the filter chain.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function no_shipping_for_pos( bool $needs_shipping ): bool {
		if ( Context::is_pos_request() ) {
			return false;
		}

		return $needs_shipping;
	}

	/**
	 * Reset customer attribution, addresses and payment-method defaults on
	 * the order after the Store API has built it from cart, before payment
	 * is taken. Saves the order so the wipe is persisted regardless of
	 * what the payment stage does.
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function reset_order_defaults_for_pos( WC_Order $order ): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}

		// Anonymous in-store sale — the cashier is not the customer.
		$order->set_customer_id( 0 );

		// Cashier picks a tender post-order; the gateway's id is recorded
		// via the existing per-tender REST endpoints, not at order creation.
		$order->set_payment_method( '' );
		$order->set_payment_method_title( '' );

		$order->set_props( $this->blank_address_props() );

		// Defense in depth: if the needs_shipping filter was bypassed or
		// some extension stamped a shipping line during order_processed,
		// drop it. In-person sale = no shipping.
		$order->remove_order_items( OrderItemType::SHIPPING );
		$order->set_shipping_total( '0' );

		$order->save();
	}

	/**
	 * Build a key-value map of every billing_* / shipping_* address prop
	 * set to the empty string. Mirrors the keys that
	 * {@see \Automattic\WooCommerce\StoreApi\Utilities\OrderController::update_addresses_from_cart}
	 * populates, so this wipe is a complete inverse of that copy.
	 *
	 * @return array<string, string>
	 */
	private function blank_address_props(): array {
		$address_fields = array(
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
			'phone',
		);

		$blank = array();
		foreach ( $address_fields as $field ) {
			$blank[ "billing_{$field}" ]  = '';
			$blank[ "shipping_{$field}" ] = '';
		}
		$blank['billing_email'] = '';

		return $blank;
	}
}
