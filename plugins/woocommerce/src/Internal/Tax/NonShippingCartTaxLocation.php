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
		// Runs early so it only replaces the default address, and callbacks from other plugins at the default priority see and can override the result.
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'use_billing_address_for_cart_without_shipping' ), 1, 2 );
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
	public function use_billing_address_for_cart_without_shipping( $taxable_address, $customer = null ) {
		if ( ! $customer instanceof \WC_Customer || ! $this->should_use_billing_address( $customer ) ) {
			return $taxable_address;
		}

		return array(
			$customer->get_billing_country(),
			$customer->get_billing_state(),
			$customer->get_billing_postcode(),
			$customer->get_billing_city(),
		);
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
			return $this->has_only_non_shipping_products( $cart );
		} finally {
			$this->is_checking_shipping = false;
		}
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
