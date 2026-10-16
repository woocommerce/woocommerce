<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Checkout;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Forgets the shopper's payment choice when the cart empties, on stores that decide the
 * default payment method through the `woocommerce_default_payment_method` filter.
 *
 * That filter runs in PaymentUtils. Without a callback on it, nothing here does anything.
 *
 * @since 11.3.0
 */
class DefaultPaymentMethod implements RegisterHooksInterface {

	/**
	 * Hook into the cart so a payment choice lasts one purchase.
	 */
	public function register() {
		add_action( 'woocommerce_cart_emptied', array( $this, 'forget_chosen_payment_method' ) );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'forget_chosen_payment_method_when_cart_is_empty' ), 10, 2 );
	}

	/**
	 * Forget the shopper's payment choice once they remove the last item from the cart.
	 *
	 * Removing items one by one doesn't fire woocommerce_cart_emptied.
	 *
	 * @internal
	 *
	 * @param string   $cart_item_key Key of the removed cart item.
	 * @param \WC_Cart $cart          The cart the item was removed from.
	 */
	public function forget_chosen_payment_method_when_cart_is_empty( $cart_item_key, $cart ): void {
		unset( $cart_item_key );

		if ( $cart instanceof \WC_Cart && $cart->is_empty() ) {
			$this->forget_chosen_payment_method();
		}
	}

	/**
	 * Forget the shopper's payment choice once the cart is emptied, so the next purchase starts from the filter.
	 *
	 * The cart is usually emptied after a placed order. Only stores that decide the starting method
	 * through the filter need this; elsewhere the remembered choice keeps working as before.
	 *
	 * @internal
	 */
	public function forget_chosen_payment_method(): void {
		// The cart can be emptied in contexts without a session, such as WP-CLI or tests that null it.
		if ( ! has_filter( 'woocommerce_default_payment_method' ) || ! ( WC()->session instanceof \WC_Session ) ) {
			return;
		}

		WC()->session->set( 'chosen_payment_method', null );
	}
}
