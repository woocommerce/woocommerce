<?php
/**
 * CurrentUserSwap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Drops the operator's identity for the duration of POS route handling.
 *
 * The authenticated WP user is the *operator* processing the sale, not the
 * purchaser. Anything downstream that keys on the current user — order
 * ownership, customer attribution, user-meta writes — must therefore see a
 * guest, or the sale would be recorded against the operator's account.
 *
 * Hooked on `rest_dispatch_request`, which fires after authentication and
 * the route's permission check have passed (the capability gate has done its
 * job) and immediately before the route callback runs — the operator
 * authorizes the request, the guest owns the transaction. Operator
 * attribution on the order itself is the domain of the separate POS
 * staff/attribution work, not of this identity swap.
 *
 * Registered unconditionally; the POS context is evaluated lazily per call
 * (see SessionHandlerSwap for why).
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class CurrentUserSwap implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'rest_dispatch_request', array( $this, 'maybe_swap_to_guest' ) );
	}

	/**
	 * Switch to the guest user before POS route callbacks run.
	 *
	 * @param mixed $dispatch_result Dispatch result passed through unchanged.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_swap_to_guest( $dispatch_result ) {
		if ( Context::is_pos_request() ) {
			wp_set_current_user( 0 );
		}

		return $dispatch_result;
	}
}
