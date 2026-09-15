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
	 * Stack of Store API cart and checkout request contexts.
	 *
	 * @var bool[]
	 */
	private $store_api_cart_or_checkout_request_contexts = array();

	/**
	 * Register hooks.
	 *
	 * @since 11.2.0
	 * @internal
	 */
	final public function init(): void {
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'use_billing_address_for_cart_without_shipping' ), 10, 2 );
		add_filter( 'rest_pre_dispatch', array( $this, 'handle_rest_pre_dispatch' ), 10, 3 );
		add_filter( 'rest_post_dispatch', array( $this, 'handle_rest_post_dispatch' ) );
	}

	/**
	 * Identify Store API cart and checkout requests before their callbacks run.
	 *
	 * @since 11.2.0
	 * @internal
	 *
	 * @param mixed            $result  Response to replace the requested version with. Can be anything a normal endpoint can return, or null to not hijack the request.
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @return mixed The response to dispatch.
	 */
	public function handle_rest_pre_dispatch( $result, \WP_REST_Server $server, \WP_REST_Request $request ) {
		$this->store_api_cart_or_checkout_request_contexts[] = 1 === preg_match( '#^/wc/store(?:/v1)?/(?:cart|checkout)(?:/|$)#', $request->get_route() );

		return $result;
	}

	/**
	 * Clear the Store API request state after its response is dispatched.
	 *
	 * @since 11.2.0
	 * @internal
	 *
	 * @param mixed $response Response generated for the request.
	 * @return mixed The dispatched response.
	 */
	public function handle_rest_post_dispatch( $response ) {
		array_pop( $this->store_api_cart_or_checkout_request_contexts );

		return $response;
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

		$billing_country = $customer->get_billing_country();
		if ( empty( $billing_country ) ) {
			return $taxable_address;
		}

		if ( ! is_cart() && ! is_checkout() && ! end( $this->store_api_cart_or_checkout_request_contexts ) ) {
			return $taxable_address;
		}

		if ( $this->is_tax_based_on_local_pickup() ) {
			return $taxable_address;
		}

		$cart = WC()->cart;
		if ( ! $cart instanceof \WC_Cart || ! $customer instanceof \WC_Customer || $cart->get_customer() !== $customer ) {
			return $taxable_address;
		}

		$cart_contents = $cart->get_cart();

		if ( empty( $cart_contents ) ) {
			return $taxable_address;
		}

		foreach ( $cart_contents as $cart_item ) {
			$product = $cart_item['data'] ?? null;
			if ( ! $product instanceof \WC_Product || $product->needs_shipping() ) {
				return $taxable_address;
			}
		}

		return array(
			$billing_country,
			$customer->get_billing_state(),
			$customer->get_billing_postcode(),
			$customer->get_billing_city(),
		);
	}

	/**
	 * Determine whether local pickup requires taxes to use the store base address.
	 *
	 * @since 11.2.0
	 *
	 * @return bool True when local pickup taxes are based on the store address.
	 */
	private function is_tax_based_on_local_pickup(): bool {
		/**
		 * Filters local pickup shipping methods.
		 *
		 * @since 6.8.0
		 *
		 * @param string[] $local_pickup_methods Local pickup shipping method IDs.
		 */
		$local_pickup_methods = apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) );

		/**
		 * Filters whether tax is based on the store address for local pickup.
		 *
		 * @since 6.8.0
		 *
		 * @param bool $apply_base_tax Whether to apply store-address tax for local pickup.
		 */
		$apply_base_tax = true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true );

		return $apply_base_tax &&
			count( array_intersect( wc_get_chosen_shipping_method_ids(), $local_pickup_methods ) ) > 0;
	}
}
