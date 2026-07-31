<?php
/**
 * HydrationUtil class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Utilities;

/**
 * Neutral home for the decision of whether server-rendered WooCommerce output
 * should be hydrated with per-user data.
 *
 * Lives in the Utilities layer (rather than the Blocks layer) so both core
 * code and the Blocks rendering layer can consult the same decision and the
 * same filter without core depending on Blocks.
 */
class HydrationUtil {

	/**
	 * Whether WooCommerce should hydrate server-rendered output with per-user data.
	 *
	 * The default is request-aware: hydrate when the response is personalized
	 * anyway (logged-in user, or a session with a non-empty cart), and emit
	 * neutral output otherwise. This matches the current behavior of the
	 * Interactivity API blocks, so out of the box nothing changes; the
	 * `woocommerce_should_hydrate` filter is the opt-in surface for cache
	 * integrations (CDNs, page caches, hosts) that know the caching policy
	 * applied to the request.
	 *
	 * Returns false when the output should remain cacheable. When false,
	 * callers should emit neutral, anonymous output and load per-user data on
	 * the client. Third-party blocks hydrating per-user data can route through
	 * this method and the `woocommerce_should_hydrate` filter to stay
	 * cache-aware.
	 *
	 * @since 11.1.0
	 *
	 * @param string $store_namespace Optional. Block or IAPI store namespace making the decision.
	 * @return bool
	 */
	public static function should_hydrate( string $store_namespace = '' ): bool {
		// Logged-in requests and requests carrying a non-empty cart are
		// personalized: a cache layer following the documented policy bypasses
		// them (e.g. on the cart/session cookie), so the HTML reaches the
		// shopper directly from the origin and benefits from full hydration.
		$cart    = WC()->cart;
		$default = is_user_logged_in() || ( $cart instanceof \WC_Cart && ! $cart->is_empty() );

		/**
		 * Filters whether server-rendered WooCommerce output should hydrate with per-user data.
		 *
		 * Return false to keep the output neutral so the response stays safe to
		 * store in a shared cache; per-user data is then loaded on the client.
		 * This is a per-block hint honored by blocks that know how to recover
		 * their data client-side (via the Store API), not a promise that the
		 * whole page is cacheable. Cache integrations that serve shared
		 * anonymous HTML regardless of cookies should return false for the
		 * requests they intend to store.
		 *
		 * The `$store_namespace` argument identifies the block or Interactivity
		 * API store consulting the filter (e.g. `woocommerce/mini-cart`),
		 * allowing per-block decisions.
		 *
		 * @since 11.1.0
		 *
		 * @param bool   $default         Default value, true when the request is personalized (logged-in user or non-empty cart).
		 * @param string $store_namespace Block or IAPI store namespace making the decision.
		 */
		return (bool) apply_filters( 'woocommerce_should_hydrate', $default, $store_namespace );
	}
}
