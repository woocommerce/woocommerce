<?php
/**
 * CurrentUserSwap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Makes a POS REST request appear anonymous to all business logic that
 * runs after the permission check.
 *
 * POS authenticates with a cashier's WP user account (application password
 * or cookie) so that the route's permission_callback can verify the
 * `manage_woocommerce` capability. The cashier identity is needed to
 * answer "can this caller place a POS order at all?" — but once that
 * gate is open, the cashier is no longer the right answer to "who is the
 * customer?" or to anything else downstream in the checkout pipeline.
 *
 * Web checkout in WooCommerce conflates the two: the authenticated WP
 * user IS the customer, so `get_current_user_id()` returning a non-zero
 * value is what every consumer expects to mean "the shopper." Every
 * extension that hooks into checkout works on the same assumption. For
 * POS this is the root cause of every leak we'd otherwise have to patch
 * one-by-one: cashier attributed as customer, cashier email leaking onto
 * the order, coupons that limit "1 use per user" locking onto the cashier,
 * customer-order-history reports including the cashier's transactions
 * because they "placed" them, third-party extensions with no concept of
 * POS doing the same.
 *
 * The conceptually correct fix is the same property agentic commerce gets
 * for free from its Jetpack-blog-token auth model: no WP user is logged
 * in during the request's business logic. After permission_callback has
 * validated the cashier's capability, we set `wp_set_current_user( 0 )`
 * so that for the remainder of the request — the actual route handler
 * and every hook it fires — `get_current_user_id()` returns 0 and
 * `is_user_logged_in()` returns false. Extensions that read those see
 * an anonymous guest, which is what a POS in-store sale actually is.
 *
 * The swap is request-scoped only. PHP globals do not survive past the
 * request boundary, so concurrent requests from the same cashier (or
 * from any other user) are independently isolated; no cross-request
 * leakage is possible. The swap is intentionally NOT restored — the
 * shutdown phase that runs after the response (autosave hooks etc.)
 * should also see the guest, otherwise we'd re-leak the cashier's
 * identity into the customer object save path.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CurrentUserSwap implements RegisterHooksInterface {

	/**
	 * Register hooks. No-op on non-POS requests.
	 *
	 * `rest_dispatch_request` fires inside {@see \WP_REST_Server::respond_to_request}
	 * after the route's permission_callback has been evaluated and accepted —
	 * the right place to drop the cashier identity without breaking the
	 * capability check that authorised the request in the first place. The
	 * filter is normally used to short-circuit the actual callback; we
	 * return null to keep the pipeline flowing and only side-effect into
	 * the global user.
	 */
	public function register(): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}
		add_filter( 'rest_dispatch_request', array( $this, 'swap_to_guest' ), 10, 2 );
	}

	/**
	 * Swap the global current WP user to guest (0) after the permission
	 * callback has already passed. Returns the dispatch result unchanged to
	 * decline short-circuiting the dispatch.
	 *
	 * @param mixed $dispatch_result Existing dispatch short-circuit value (null = don't short-circuit).
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function swap_to_guest( $dispatch_result ) {
		// Capability check already passed by this point — it's safe to drop
		// the authenticated identity for the remainder of the request.
		wp_set_current_user( 0 );

		return $dispatch_result;
	}
}
