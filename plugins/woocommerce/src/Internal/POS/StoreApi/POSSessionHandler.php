<?php
/**
 * POSSessionHandler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi;

use WC_Session_Handler;

/**
 * Session handler used during POS Store API requests.
 *
 * The default WC_Session_Handler keys carts by the authenticated WP user ID
 * whenever one is present. For POS that is the wrong scope: multiple cashiers,
 * registers, and concurrent in-progress sales typically authenticate as the
 * same store-manager account, and would collide on a single shared cart row.
 *
 * This handler treats every POS request as a guest from the session's
 * perspective. The single load-bearing override is {@see generate_customer_id}
 * — the parent's "if no cookie, generate a new customer ID" path is used
 * unchanged, and because mobile clients do not send the WC session cookie,
 * the parent's "migrate guest session onto authenticated user" branch is
 * never entered. The cart is therefore scoped strictly by whatever
 * transaction-scoped token the mobile client supplies (today: ?session=
 * query parameter; longer term: a Cart-Token header — see DECISIONS.md).
 *
 * Only swapped in when {@see Context::is_pos_request()} is true, so the
 * default cart/session behaviour for web checkout is unaffected.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class POSSessionHandler extends WC_Session_Handler {

	/**
	 * Always generate a guest-style customer ID, ignoring the authenticated user.
	 *
	 * This is the single load-bearing override: it prevents the cart from
	 * being keyed by the cashier's user ID and therefore prevents collisions
	 * across concurrent POS transactions that authenticate as the same
	 * account.
	 *
	 * @return string
	 */
	public function generate_customer_id() {
		return wc_rand_hash( 't_', 30 );
	}
}
