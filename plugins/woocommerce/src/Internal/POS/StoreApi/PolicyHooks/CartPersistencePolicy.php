<?php
/**
 * CartPersistencePolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Disables WooCommerce's persistent-cart feature for POS requests.
 *
 * WC_Cart_Session::persistent_cart_update saves the cart to
 * `_woocommerce_persistent_cart_{blog_id}` in `wp_usermeta`, keyed by the
 * currently logged-in WP user ID. WC_Cart_Session::get_saved_cart then
 * restores it on the next page load when the user is logged in and the
 * session cart is empty. The feature exists for web shoppers who add to
 * cart, leave the site, log in later and want their cart back.
 *
 * For POS that's a disaster: every cashier authenticates as the same
 * store-manager account, so the user-meta cart row is constantly written
 * to AND read from by every transaction. Items added in one transaction
 * leak into the next one — across registers, across devices, across app
 * restarts — because they're attached to the admin's WP user_id, not to
 * any per-transaction identifier. POSSessionHandler can mint all the
 * fresh `t_xxx` session IDs it wants and it won't help, because the cart
 * load path reads from user_meta before the session.
 *
 * Disabling via the `woocommerce_persistent_cart_enabled` filter
 * short-circuits both the save path (`persistent_cart_update`) and the
 * read path (`get_saved_cart`), so:
 *
 *   - POS requests neither pollute the admin's user-meta cart row
 *     nor read from it.
 *   - Non-POS requests (e.g. the admin browsing the storefront in a
 *     browser) keep the persistent-cart behaviour exactly as today.
 *
 * Mirrors the other `PolicyHooks/` classes (single filter callback gated
 * on {@see Context::is_pos_request()}).
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartPersistencePolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_persistent_cart_enabled', array( $this, 'disable_for_pos' ) );
	}

	/**
	 * Return false when the current request is a POS request OR the active
	 * WC session is being handled by {@see POSSessionHandler}; otherwise
	 * pass through the original value.
	 *
	 * The two checks are deliberately redundant for defense in depth:
	 *
	 *   - The URI check ({@see Context::is_pos_request()}) is the primary
	 *     gate. It catches all POS requests up-front, before WC's cart
	 *     bootstrap runs.
	 *   - The session-handler instance check is a belt-and-suspenders
	 *     fallback. If anything ever messes with URI detection (rewrite
	 *     rules, reverse proxies, future refactors) but POSSessionHandler
	 *     is still the active handler, this second check still gates the
	 *     persistent-cart machinery off.
	 *
	 * @param bool $enabled Original value from the filter chain.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function disable_for_pos( bool $enabled ): bool {
		if ( Context::is_pos_request() ) {
			return false;
		}

		if ( function_exists( 'WC' ) && WC()->session instanceof POSSessionHandler ) {
			return false;
		}

		return $enabled;
	}
}
