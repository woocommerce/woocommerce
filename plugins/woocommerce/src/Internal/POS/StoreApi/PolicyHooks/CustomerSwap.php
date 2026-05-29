<?php
/**
 * CustomerSwap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Customer;

/**
 * Swaps {@see WC()->customer} to a fresh guest object for POS requests.
 *
 * For web checkout, {@see WC::initialize_cart} does
 * `$this->customer = new WC_Customer( get_current_user_id(), true )` — the
 * authenticated user IS the shopper, so loading their saved profile address
 * into the cart/checkout pipeline is correct. For POS that assumption
 * breaks: the authenticated user is the cashier, not the customer, so every
 * downstream consumer of {@see WC()->customer} that reads
 * `get_billing_*` / `get_shipping_*` (the Store API cart/checkout schemas,
 * `OrderController::update_addresses_from_cart`, the
 * `woocommerce_order_get_tax_location` callback in
 * `OrderController::update_order_from_cart`) ends up with the cashier's
 * home address rather than empty.
 *
 * The conceptually correct fix is to make {@see WC()->customer} an actual
 * guest for the duration of a POS request — i.e. construct it with
 * `user_id = 0`. That returns a customer object whose `get_billing_*` /
 * `get_shipping_*` methods read from session (empty for a fresh POS
 * session) instead of from user_meta. Every consumer then sees empty
 * addresses naturally, with no post-hoc wipes to coordinate.
 *
 * Hooked on `rest_pre_dispatch` (per-REST-request, before the route handler
 * runs). {@see WC::initialize_cart} only constructs `WC()->customer` when
 * it is null, so setting it pre-emptively here causes the cart bootstrap
 * to leave our guest customer in place. The defensive re-set in
 * {@see WC()->initialize_cart} would otherwise reset it back to the
 * cashier's customer object.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CustomerSwap implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'rest_pre_dispatch', array( $this, 'swap_customer_for_pos' ), 10, 3 );
	}

	/**
	 * For POS REST requests, replace {@see WC()->customer} with a fresh
	 * guest customer (user_id = 0). For all other REST requests, return
	 * the existing pre-dispatch result unchanged.
	 *
	 * The filter signature requires returning the first argument; this
	 * callback only side-effects into WC()->customer.
	 *
	 * @param mixed                                $result  Pre-dispatch result. Not modified.
	 * @param \WP_REST_Server|null                 $server  REST server instance (unused).
	 * @param \WP_REST_Request<array<string,mixed>> $request Inbound request.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function swap_customer_for_pos( $result, $server, $request ) {
		// Signature contract: $server is required but unused.
		unset( $server );

		if ( ! $this->is_pos_route( $request ) ) {
			return $result;
		}

		// `new WC_Customer( 0, true )` constructs a session-backed guest
		// customer. `get_billing_*` / `get_shipping_*` then read from the
		// (empty, freshly minted by POSSessionHandler) session instead of
		// from the cashier's user_meta.
		//
		// The session data store's `set_defaults` then populates store-based
		// defaults (billing_country, billing_state, shipping_country,
		// shipping_state) and — if the request is authenticated — fills
		// `billing_email` from `wp_get_current_user()->user_email`. For POS
		// that last fallback is exactly the leak we are guarding against
		// (the cashier's WP email would otherwise become the customer's),
		// and the store-location defaults equally have no business being on
		// an in-store sale where the cashier never entered any address.
		// Explicitly clear them so the customer object truly is blank.
		if ( function_exists( 'WC' ) ) {
			$customer = new WC_Customer( 0, true );
			$customer->set_billing_email( '' );
			$customer->set_billing_country( '' );
			$customer->set_billing_state( '' );
			$customer->set_shipping_country( '' );
			$customer->set_shipping_state( '' );

			WC()->customer = $customer;
		}

		return $result;
	}

	/**
	 * Determine whether the inbound REST request targets the POS namespace.
	 *
	 * Uses {@see \WP_REST_Request::get_route()} (authoritative for the
	 * dispatched route) and falls back to {@see Context::is_pos_request()}
	 * which derives the same thing from REQUEST_URI; the fallback covers
	 * test contexts and overrides.
	 *
	 * @param \WP_REST_Request<array<string,mixed>>|null $request Inbound REST request, if any.
	 * @return bool
	 */
	private function is_pos_route( $request ): bool {
		if ( $request instanceof \WP_REST_Request ) {
			$route = (string) $request->get_route();
			if ( 0 === strpos( $route, '/wc/pos/' ) ) {
				return true;
			}
		}

		return Context::is_pos_request();
	}
}
