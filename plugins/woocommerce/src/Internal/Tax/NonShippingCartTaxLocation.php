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
	 * Stack of dispatch IDs for Store API cart and checkout requests.
	 *
	 * @var int[]
	 */
	private $dispatch_stack = array();

	/**
	 * Map of dispatch IDs to their cart/checkout context.
	 *
	 * @var array<int, bool>
	 */
	private $dispatch_contexts = array();

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
	 */
	final public function init(): void {
		$this->dispatch_stack    = array();
		$this->dispatch_contexts = array();
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'use_billing_address_for_cart_without_shipping' ), 10, 2 );
		// Register the cart/checkout context only after a route matches, and clear it right
		// after the callback.
		add_filter( 'rest_request_before_callbacks', array( $this, 'handle_rest_request_before_callbacks' ), 10, 3 );
		add_filter( 'rest_request_after_callbacks', array( $this, 'handle_rest_request_after_callbacks' ), 10, 3 );
	}

	/**
	 * Register Store API cart and checkout context after a route matches, before its callback runs.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param mixed            $response Result to send to the client.
	 * @param mixed            $handler  Route handler for the request.
	 * @param \WP_REST_Request $request  Request used to generate the response.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return mixed The response.
	 */
	public function handle_rest_request_before_callbacks( $response, $handler, \WP_REST_Request $request ) {
		$dispatch_id         = spl_object_id( $request );
		$is_cart_or_checkout = 1 === preg_match( '#^/wc/store(?:/v1)?/(?:cart|checkout)(?:/|$)#', $request->get_route() );

		$this->dispatch_contexts[ $dispatch_id ] = $is_cart_or_checkout;
		$this->dispatch_stack[]                  = $dispatch_id;

		return $response;
	}

	/**
	 * Clear the Store API request context after the route callback runs.
	 *
	 * Paired with rest_request_before_callbacks inside WP_REST_Server::respond_to_request(),
	 * so the stack stays balanced for both external and internal (rest_do_request) dispatches.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param mixed            $response Result to send to the client.
	 * @param mixed            $handler  Route handler for the request.
	 * @param \WP_REST_Request $request  Request used to generate the response.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return mixed The response.
	 */
	public function handle_rest_request_after_callbacks( $response, $handler, \WP_REST_Request $request ) {
		$this->clear_dispatch_context( $request );

		return $response;
	}

	/**
	 * Remove a dispatch's cart/checkout context from the stack.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param \WP_REST_Request $request Request whose context should be cleared.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	private function clear_dispatch_context( \WP_REST_Request $request ): void {
		$dispatch_id = spl_object_id( $request );

		// Remove the context for this specific dispatch.
		if ( isset( $this->dispatch_contexts[ $dispatch_id ] ) ) {
			unset( $this->dispatch_contexts[ $dispatch_id ] );
		}

		// Remove the dispatch ID from the stack, preserving the order of outer contexts.
		$stack_key = array_search( $dispatch_id, $this->dispatch_stack, true );
		if ( false !== $stack_key ) {
			array_splice( $this->dispatch_stack, (int) $stack_key, 1 );
		}
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
	public function use_billing_address_for_cart_without_shipping( $taxable_address, $customer ) {
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

		if ( ! empty( $this->dispatch_stack ) ) {
			$current_dispatch_id = end( $this->dispatch_stack );
			if ( empty( $this->dispatch_contexts[ $current_dispatch_id ] ) ) {
				return $taxable_address;
			}
		} elseif ( ! is_cart() && ! is_checkout() ) {
			return $taxable_address;
		}

		if ( $this->is_tax_based_on_local_pickup() ) {
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

	/**
	 * Determine whether local pickup requires taxes to use the store base address.
	 *
	 * @since 11.3.0
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
		if ( ! is_array( $local_pickup_methods ) ) {
			return false;
		}

		$local_pickup_methods = array_filter( $local_pickup_methods, 'is_string' );

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
