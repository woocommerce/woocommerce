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
 * whenever one is present, and resolves the session from a browser cookie
 * when one is sent. Both behaviours are wrong for POS:
 *
 *   - Multiple cashiers, registers, and concurrent in-progress sales
 *     typically authenticate as the same store-manager account; user-id
 *     keying would collide them onto a single shared cart row.
 *   - Headless API clients (such as the mobile POS app) frequently have
 *     HTTP stacks with persistent cookie jars, so a previously-issued
 *     `wp_woocommerce_session_…` cookie would re-load the prior cart on
 *     every subsequent request — even across app restarts — and the
 *     parent's `migrate_guest_session_to_user_session` block would then
 *     re-key that cart onto the cashier's WP user ID.
 *
 * This handler addresses both by treating every POS request as a brand-new
 * guest from the session's perspective. Each request starts with a fresh
 * customer_id; the cookie is never read.
 *
 * Session continuity across the requests in a single POS transaction is
 * provided by the `cart_token` URL parameter, handled in the POS routes'
 * `has_cart_token` override — which engages the Store API's existing
 * header-based session swap (`StoreApi\SessionHandler` via
 * `Authentication::maybe_use_store_api_session_handler`-style logic in
 * `AbstractCartRoute::load_cart_session`). Cookies have no role to play
 * in the POS request lifecycle.
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
	 * Without this override the parent would read any previously-issued
	 * `wp_woocommerce_session_…` cookie that the mobile HTTP stack
	 * persisted from an earlier response, re-load the prior cart, and (because
	 * the cashier is logged in but the cookie's customer_id is a guest hash)
	 * migrate that cart onto the cashier's WP user ID via
	 * `migrate_guest_session_to_user_session`. Items would then leak across
	 * transactions and even across app restarts — exactly the
	 * "1,000,000 of product X is still in the cart on the next checkout"
	 * failure mode.
	 *
	 * Cart continuity across the requests in one POS transaction is
	 * provided by the `cart_token` URL parameter, not by the cookie.
	 *
	 * @return void
	 */
	public function init_session_cookie() {
		$this->_customer_id = $this->generate_customer_id();
		$this->_data        = $this->get_session_data();
	}
}
