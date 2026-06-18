<?php
/**
 * CheckoutAddressPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_REST_Request;

/**
 * Lets POS submit a checkout with no customer address.
 *
 * The common in-store sale is a cash purchase of physical goods where the
 * cashier has no customer address to capture, so POS needs to clear two
 * separate address requirements the shared `wc/store/v1/checkout` route imposes
 * on web shoppers:
 *
 * - The schema marks `billing_address` (and `shipping_address`) as required
 *   request parameters, so an address-less POST is rejected with
 *   `rest_missing_callback_param` before any business logic runs. The
 *   inheritance spike relaxed this per-route in its POS Controller; with no POS
 *   route to edit, we instead inject empty address objects into POS checkout
 *   requests on `rest_pre_dispatch`, before REST validates required params —
 *   leaving the public schema (and web behaviour) untouched.
 * - The per-field rules (postcode, phone, …) are then skipped by returning false
 *   from the `woocommerce_store_api_validate_addresses` filter (added in
 *   {@see \Automattic\WooCommerce\StoreApi\Utilities\OrderController::validate_addresses}).
 *
 * A cart-aware per-product-type requirements model (downloadables, gift cards,
 * shipped goods) is a planned follow-up.
 *
 * Both hooks are installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CheckoutAddressPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_store_api_validate_addresses', array( $this, 'maybe_skip_address_validation' ) );
		add_filter( 'rest_pre_dispatch', array( $this, 'maybe_default_address_params' ), 10, 3 );
	}

	/**
	 * Skip address validation on POS requests, leaving web behaviour untouched.
	 *
	 * @param bool $validate Whether to validate addresses.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_skip_address_validation( $validate ) {
		return Context::is_pos_request() ? false : $validate;
	}

	/**
	 * Default the required address params to empty objects on a POS checkout
	 * request, so an address-less POST clears REST's required-param check. Runs
	 * before route dispatch; a no-op for web requests and non-checkout routes.
	 *
	 * @param mixed           $result  Existing pre-dispatch short-circuit value.
	 * @param \WP_REST_Server $server  Server instance (unused).
	 * @param WP_REST_Request $request Request being dispatched.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_default_address_params( $result, $server, $request ) {
		unset( $server );

		if ( null !== $result || ! $request instanceof WP_REST_Request || ! Context::is_pos_request() ) {
			return $result;
		}

		$route = (string) $request->get_route();
		if ( '/checkout' !== substr( $route, -strlen( '/checkout' ) ) ) {
			return $result;
		}

		foreach ( array( 'billing_address', 'shipping_address' ) as $address_key ) {
			if ( null === $request->get_param( $address_key ) ) {
				$request->set_param( $address_key, array() );
			}
		}

		return $result;
	}
}
