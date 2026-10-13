<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Checkout;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Tells the checkout whether a payment method should be preselected for the shopper.
 *
 * Some countries require the shopper to actively choose a payment method, so merchants
 * can turn preselection off. The option is on by default, matching the historic behavior.
 *
 * @since 11.3.0
 */
class PaymentMethodPreselection implements RegisterHooksInterface {

	/**
	 * Name of the option that stores the merchant's choice ('yes' or 'no').
	 */
	public const OPTION_NAME = 'woocommerce_checkout_preselect_payment_method';

	/**
	 * Whether the checkout should preselect a payment method.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return wc_string_to_bool( get_option( self::OPTION_NAME, 'yes' ) );
	}

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
	 * Forget the shopper's payment choice once the cart is emptied, so the next purchase starts from nothing.
	 *
	 * The cart is emptied after every placed order. Only stores that don't preselect a payment method
	 * need this; elsewhere the remembered choice keeps working as before.
	 *
	 * @internal
	 */
	public function forget_chosen_payment_method(): void {
		// The cart can be emptied in contexts without a session, such as WP-CLI or tests that null it.
		if ( self::is_enabled() || ! ( WC()->session instanceof \WC_Session ) ) {
			return;
		}

		WC()->session->set( 'chosen_payment_method', null );
	}
}
