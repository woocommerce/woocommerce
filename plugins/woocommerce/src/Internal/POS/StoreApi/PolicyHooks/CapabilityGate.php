<?php
/**
 * CapabilityGate class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Error;

/**
 * Rejects POS-marked requests whose caller cannot operate POS.
 *
 * The POS marker that {@see Context} keys off only declares *intent* — any
 * client can set the `X-WooCommerce-POS` header or `pos` parameter. This gate is
 * what turns that intent into an authorisation decision: on a request that
 * targets a shared Store API cart/checkout route and carries the marker, it
 * requires the operator capability and short-circuits the REST dispatch with a
 * 401/403 when it is missing. A guest who forges the marker is rejected here,
 * before any other POS policy runs, so the request can never reach checkout and
 * mint a no-payment order.
 *
 * Failing closed up front is what lets the rest of the POS policy layer stay
 * simple: by the time any other hook fires, an unauthorised marked request has
 * already been rejected, so {@see Context::is_pos_request()} can rely on the
 * marker alone — which, unlike the capability, survives the mid-request guest
 * swap performed by {@see CurrentUserSwap}. No verdict has to be latched.
 *
 * Runs on `rest_dispatch_request` at a priority below {@see CurrentUserSwap}
 * (which drops the user to a guest at priority 10), so the capability is still
 * evaluated against the authenticated operator.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CapabilityGate implements RegisterHooksInterface {

	/**
	 * Priority below CurrentUserSwap (10) and CustomerSwap (11) so the capability
	 * is checked while the operator is still the current user, before any swap.
	 */
	private const PRIORITY = 5;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'rest_dispatch_request', array( $this, 'enforce_capability' ), self::PRIORITY, 1 );
	}

	/**
	 * Reject a POS-marked request whose caller lacks the operator capability.
	 *
	 * Returning a {@see WP_Error} short-circuits the dispatch with that error;
	 * returning the incoming value unchanged lets the request proceed.
	 *
	 * @param mixed $dispatch_result Existing dispatch short-circuit value (null = don't short-circuit).
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function enforce_capability( $dispatch_result ) {
		// An earlier hook already resolved (or rejected) the request — leave it be.
		if ( is_wp_error( $dispatch_result ) ) {
			return $dispatch_result;
		}

		// Not a POS-intent request, or the operator is authorised: nothing to do.
		if ( ! Context::is_pos_request() || Context::current_user_can_operate_pos() ) {
			return $dispatch_result;
		}

		return new WP_Error(
			'woocommerce_pos_rest_forbidden',
			__( 'Sorry, you are not allowed to perform point of sale requests.', 'woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}
}
