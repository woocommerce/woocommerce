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
 * The default WC_Session_Handler keys carts by the authenticated WP user and
 * resolves the session from a browser cookie. Both are wrong for POS: cashiers
 * share a store-manager account (user-id keying would collide their carts), and
 * headless clients with persistent cookie jars would re-load a prior cart on
 * every request, even across app restarts. This handler treats every POS
 * request as a brand-new guest — fresh customer_id, cookie never read.
 *
 * Continuity across the requests in a single POS transaction comes from the
 * `cart_token` URL parameter (see the routes' `has_cart_token` override), which
 * engages the Store API's existing header-based session swap. Cookies play no
 * role in the POS request lifecycle.
 *
 * Only swapped in when {@see Context::is_pos_request()} is true, so web checkout
 * is unaffected.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class POSSessionHandler extends WC_Session_Handler {

	/**
	 * Always generate a guest-style customer ID, ignoring the authenticated user.
	 *
	 * Prevents the cart from being keyed by the cashier's user ID and
	 * therefore prevents collisions across concurrent POS transactions
	 * that authenticate as the same account.
	 *
	 * @return string
	 */
	public function generate_customer_id() {
		return wc_rand_hash( 't_', 30 );
	}

	/**
	 * Ignore the WC session cookie entirely.
	 *
	 * Otherwise the parent would read a persisted `wp_woocommerce_session_…`
	 * cookie, re-load the prior cart, and migrate it onto the cashier's WP user
	 * ID — leaking items across transactions and app restarts. Continuity comes
	 * from the `cart_token` URL parameter instead.
	 *
	 * @return void
	 */
	public function init_session_cookie() {
		$this->_customer_id = $this->generate_customer_id();
		$this->_data        = $this->get_session_data();
	}
}
