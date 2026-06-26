<?php
/**
 * CurrentUserSwap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Makes a POS REST request appear anonymous to all business logic that runs
 * after the permission check.
 *
 * POS authenticates as a cashier so the permission_callback can verify the
 * `manage_woocommerce` capability, but web checkout then treats that WP user
 * as the customer. That conflation is the root cause of every identity leak
 * (cashier attributed as customer, email on the order, "1 use per user"
 * coupons locking onto the cashier, etc.). Once the capability check has
 * passed we set `wp_set_current_user( 0 )` so the rest of the request — like
 * agentic commerce's guest model — sees an anonymous guest.
 *
 * The swap is request-scoped (PHP globals don't cross the request boundary,
 * so concurrent requests stay isolated) and intentionally not restored, so the
 * post-response shutdown phase also sees the guest.
 *
 * Authorisation has already happened by the time this runs: {@see CapabilityGate}
 * fires earlier on the same `rest_dispatch_request` hook (priority 5) and rejects
 * any marked-but-unauthorised request, so a request that reaches this swap is a
 * genuine, authorised POS request. After the swap the operator capability is
 * gone, which is exactly why detection keys off the request marker rather than
 * the capability — see {@see Context}.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CurrentUserSwap implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 *
	 * `rest_dispatch_request` fires after the permission_callback has passed,
	 * so it's the right place to drop the cashier identity without breaking the
	 * capability check. We don't short-circuit the dispatch — only side-effect
	 * into the global user.
	 */
	public function register(): void {
		add_filter( 'rest_dispatch_request', array( $this, 'swap_to_guest' ), 10, 1 );
	}

	/**
	 * On POS requests, swap the global current WP user to guest (0). Returns the
	 * dispatch result unchanged to decline short-circuiting the dispatch.
	 *
	 * @param mixed $dispatch_result Existing dispatch short-circuit value (null = don't short-circuit).
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function swap_to_guest( $dispatch_result ) {
		// CapabilityGate (priority 5) rejected this request — don't touch the user.
		if ( is_wp_error( $dispatch_result ) ) {
			return $dispatch_result;
		}

		if ( ! Context::is_pos_request() ) {
			return $dispatch_result;
		}

		// The capability was verified by CapabilityGate before this point, so it's
		// safe to drop the authenticated identity for the remainder of the request.
		wp_set_current_user( 0 );

		return $dispatch_result;
	}
}
