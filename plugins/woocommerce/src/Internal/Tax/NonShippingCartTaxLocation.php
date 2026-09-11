<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

use Automattic\WooCommerce\Enums\TaxBasedOn;

/**
 * Selects the billing address for shipping-based tax when cart products do not need shipping.
 *
 * @since 11.2.0
 */
class NonShippingCartTaxLocation {

	/**
	 * Register hooks.
	 *
	 * @since 11.2.0
	 * @internal
	 */
	final public function init(): void {
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'use_billing_address_for_cart_without_shipping' ), 10, 2 );
	}

	/**
	 * Use the customer's billing address for shipping-based tax when no product in a non-empty cart needs shipping.
	 *
	 * @since 11.2.0
	 *
	 * @param mixed $taxable_address The current taxable address.
	 * @param mixed $customer        The customer whose taxable address is being determined.
	 * @return mixed The taxable address.
	 */
	public function use_billing_address_for_cart_without_shipping( $taxable_address, $customer ) {
		if ( TaxBasedOn::SHIPPING !== get_option( 'woocommerce_tax_based_on' ) ) {
			return $taxable_address;
		}

		$cart = WC()->cart;
		if ( ! $cart instanceof \WC_Cart || $cart->is_empty() || ! $customer instanceof \WC_Customer || $cart->get_customer() !== $customer ) {
			return $taxable_address;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'] ?? null;
			if ( ! $product instanceof \WC_Product || $product->needs_shipping() ) {
				return $taxable_address;
			}
		}

		return array(
			$customer->get_billing_country(),
			$customer->get_billing_state(),
			$customer->get_billing_postcode(),
			$customer->get_billing_city(),
		);
	}
}
