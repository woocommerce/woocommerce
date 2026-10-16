<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

use Automattic\WooCommerce\Enums\TaxBasedOn;

/**
 * Decides when shipping-based tax should use the billing address because nothing in the cart ships.
 *
 * @internal
 * @since 11.3.0
 */
class CartWithoutShipping {

	/**
	 * Tracks whether the cart is being priced, as opposed to catalog prices.
	 *
	 * @var CartPricingContext
	 */
	private $cart_pricing_context;

	/**
	 * Whether the cart shipping check is running, so re-entrant calls from needs_shipping filter callbacks are skipped.
	 *
	 * @var bool
	 */
	private $is_checking_shipping = false;

	/**
	 * Set up the cart pricing context.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param CartPricingContext $cart_pricing_context Tracks whether the cart is being priced.
	 */
	final public function init( CartPricingContext $cart_pricing_context ): void {
		$this->cart_pricing_context = $cart_pricing_context;
	}

	/**
	 * Determine whether the customer's billing address should be used for tax because tax is based on shipping and no product in their cart needs shipping.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Customer $customer The customer whose taxable address is being determined.
	 * @return bool True when the billing address should be used.
	 */
	public function should_use_billing_address( \WC_Customer $customer ): bool {
		if ( TaxBasedOn::SHIPPING !== get_option( 'woocommerce_tax_based_on' ) ) {
			return false;
		}

		if ( empty( $customer->get_billing_country() ) ) {
			return false;
		}

		if ( ! $this->cart_pricing_context->is_active() ) {
			return false;
		}

		$cart = WC()->cart;
		if ( ! $cart instanceof \WC_Cart || $cart->get_customer() !== $customer ) {
			return false;
		}

		// needs_shipping filter callbacks may resolve the taxable address again (e.g. to price with tax).
		if ( $this->is_checking_shipping ) {
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
			 * Return false to keep using the shipping address. The filter only fires when the cart qualifies,
			 * so it cannot turn the fallback on for other carts. It may fire multiple times per request.
			 *
			 * @since 11.3.0
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
}
