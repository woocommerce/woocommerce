<?php
/**
 * CartPersistencePolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Disables WooCommerce's persistent-cart feature for POS requests.
 *
 * Persistent cart saves/restores the cart keyed by the logged-in WP user. In
 * POS every cashier authenticates as the same store-manager account, so carts
 * would leak between transactions, registers and devices — the feature is both
 * useless and dangerous here. Returning false from the
 * `woocommerce_persistent_cart_enabled` filter short-circuits both the save and
 * read paths.
 *
 * The filter is installed for every request and the POS check runs in the
 * callback, because POS detection is capability-based and isn't resolvable when
 * hooks are registered (in the WooCommerce constructor, before REST auth). See
 * {@see Context} for why detection is deferred to call time.
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
		add_filter( 'woocommerce_persistent_cart_enabled', array( $this, 'maybe_disable_persistence' ) );
	}

	/**
	 * Disable persistent cart on POS requests, leaving web behaviour untouched.
	 *
	 * @param bool $enabled Whether persistent cart is enabled.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_disable_persistence( $enabled ) {
		return Context::is_pos_request() ? false : $enabled;
	}
}
