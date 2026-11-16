<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

use Automattic\WooCommerce\Enums\TaxBasedOn;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Decides when shipping-based tax should use the billing address because nothing in the cart ships.
 *
 * Every guard falls back to the shipping address, so an interrupted cart load or a re-entrant lookup gets the pre-fallback result.
 *
 * @internal
 * @since 11.4.0
 */
class CartWithoutShipping implements RegisterHooksInterface {

	/**
	 * Whether the cart shipping check is running, so re-entrant calls from needs_shipping filter callbacks are skipped.
	 *
	 * @var bool
	 */
	private $is_checking_shipping = false;

	/**
	 * Whether this class is loading the cart because nothing else loaded it yet, so re-entrant calls from cart contents filter callbacks are skipped.
	 *
	 * @var bool
	 */
	private $is_loading_cart = false;

	/**
	 * Whether WC_Cart_Session is loading the cart from the session, so the cart holds only some of its items.
	 *
	 * Tracked with hooks rather than did_action() counters, because code that reloads the cart may reset those counters.
	 * If a load is interrupted by an exception, the flag stays set until the next load finishes.
	 *
	 * @var bool
	 */
	private $is_cart_loading_from_session = false;

	/**
	 * Register the hooks that track loading the cart from the session.
	 *
	 * @internal
	 */
	public function register(): void {
		add_action( 'woocommerce_load_cart_from_session', array( $this, 'handle_cart_load_started' ), PHP_INT_MIN );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'handle_cart_load_finished' ), PHP_INT_MIN );
	}

	/**
	 * Mark the cart as loading from the session.
	 *
	 * @internal
	 */
	public function handle_cart_load_started(): void {
		$this->is_cart_loading_from_session = true;
	}

	/**
	 * Mark the cart as fully loaded from the session.
	 *
	 * @internal
	 */
	public function handle_cart_load_finished(): void {
		$this->is_cart_loading_from_session = false;
	}

	/**
	 * Determine whether the customer's billing address should be used for tax because tax is based on shipping and no product in their cart needs shipping.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Customer $customer The customer whose taxable address is being determined.
	 * @return bool True when the billing address should be used.
	 */
	public function should_use_billing_address( \WC_Customer $customer ): bool {
		if ( ! wc_tax_enabled() || TaxBasedOn::SHIPPING !== get_option( 'woocommerce_tax_based_on', TaxBasedOn::SHIPPING ) ) {
			return false;
		}

		if ( empty( $customer->get_billing_country() ) ) {
			return false;
		}

		// The cart exists from init, but reading it before wp_loaded is flagged as doing it wrong.
		if ( ! did_action( 'wp_loaded' ) ) {
			return false;
		}

		$cart = WC()->cart;
		if ( ! $cart instanceof \WC_Cart || $cart->get_customer() !== $customer ) {
			return false;
		}

		// A cart still being loaded from the session holds only some of its items.
		if ( $this->is_cart_loading_from_session ) {
			return false;
		}

		// needs_shipping filter callbacks may resolve the taxable address again (e.g. to price with tax).
		if ( $this->is_checking_shipping ) {
			return false;
		}

		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && ! $this->load_cart( $cart ) ) {
			return false;
		}

		$this->is_checking_shipping = true;
		try {
			if ( $cart->is_empty() || $cart->needs_shipping() ) {
				return false;
			}

			/**
			 * Filters whether shipping-based tax uses the billing address for a non-empty cart with nothing to ship.
			 *
			 * Return anything other than true to keep using the shipping address. The filter only fires when the cart qualifies,
			 * so it cannot turn the fallback on for other carts. It may fire multiple times per request.
			 * Callbacks that resolve the taxable address while it runs get the shipping address.
			 *
			 * @since 11.4.0
			 *
			 * @param bool         $use_billing Always true when fired.
			 * @param \WC_Customer $customer    Customer whose taxable address is being resolved.
			 * @param \WC_Cart     $cart        The customer's cart.
			 */
			return true === apply_filters( 'woocommerce_apply_billing_tax_for_cart_without_shipping', true, $customer, $cart );
		} finally {
			$this->is_checking_shipping = false;
		}
	}

	/**
	 * Load the cart from the session before the shipping check starts, so totals calculated while loading also use this rule.
	 *
	 * @param \WC_Cart $cart The customer's cart.
	 * @return bool False when called again while the cart is loading.
	 */
	private function load_cart( \WC_Cart $cart ): bool {
		if ( $this->is_loading_cart ) {
			return false;
		}

		$this->is_loading_cart = true;
		try {
			$cart->get_cart();
		} finally {
			$this->is_loading_cart = false;
		}

		return true;
	}
}
