<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

use Automattic\WooCommerce\Enums\TaxBasedOn;

/**
 * Selects the billing address for shipping-based tax when cart products do not need shipping.
 *
 * @since 11.3.0
 */
class NonShippingCartTaxLocation {

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
	 * Register hooks.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param CartPricingContext $cart_pricing_context Tracks whether the cart is being priced.
	 */
	final public function init( CartPricingContext $cart_pricing_context ): void {
		$this->cart_pricing_context = $cart_pricing_context;
		// Should run after the Blocks local pickup filter, which can apply a pickup choice left in the session after the last shippable item is removed.
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'use_billing_address_for_cart_without_shipping' ), 11, 2 );
	}

	/**
	 * Use the customer's billing address for shipping-based tax when no product in a non-empty cart needs shipping.
	 *
	 * @since 11.3.0
	 *
	 * @param mixed $taxable_address The current taxable address.
	 * @param mixed $customer        The customer whose taxable address is being determined.
	 * @return mixed The taxable address.
	 */
	public function use_billing_address_for_cart_without_shipping( $taxable_address, $customer=null ) {
		if ( null === $customer ) {
			return $taxable_address;
		}

		if ( TaxBasedOn::SHIPPING !== get_option( 'woocommerce_tax_based_on' ) ) {
			return $taxable_address;
		}

		if ( ! $customer instanceof \WC_Customer ) {
			return $taxable_address;
		}

		$billing_country = $customer->get_billing_country();
		if ( empty( $billing_country ) ) {
			return $taxable_address;
		}

		if ( ! $this->cart_pricing_context->is_active() ) {
			return $taxable_address;
		}

		$cart = WC()->cart;
		if ( ! $cart instanceof \WC_Cart || $cart->get_customer() !== $customer ) {
			return $taxable_address;
		}

		// needs_shipping filter callbacks may resolve the taxable address again (e.g. to price with tax).
		if ( $this->is_checking_shipping ) {
			return $taxable_address;
		}

		$this->is_checking_shipping = true;
		try {
			$has_only_non_shipping_products = $this->has_only_non_shipping_products( $cart );
		} finally {
			$this->is_checking_shipping = false;
		}

		if ( ! $has_only_non_shipping_products ) {
			return $taxable_address;
		}

		return array(
			$billing_country,
			$customer->get_billing_state(),
			$customer->get_billing_postcode(),
			$customer->get_billing_city(),
		);
	}

	/**
	 * Determine whether the cart is non-empty and none of its products need shipping.
	 *
	 * @since 11.3.0
	 *
	 * @param \WC_Cart $cart The cart to check.
	 * @return bool True when the cart has products and none of them need shipping.
	 */
	private function has_only_non_shipping_products( \WC_Cart $cart ): bool {
		if ( $cart->needs_shipping() ) {
			return false;
		}

		$cart_contents = $cart->get_cart();
		if ( empty( $cart_contents ) ) {
			return false;
		}

		foreach ( $cart_contents as $cart_item ) {
			$product = $cart_item['data'] ?? null;
			if ( ! $product instanceof \WC_Product || $product->needs_shipping() ) {
				return false;
			}
		}

		return true;
	}
}
