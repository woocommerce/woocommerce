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
 * useless and dangerous here. Disabling the `woocommerce_persistent_cart_enabled`
 * filter short-circuits both the save and read paths.
 *
 * Registration is gated on {@see Context::is_pos_request()}, so the filter is
 * installed only when the current request is POS.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartPersistencePolicy implements RegisterHooksInterface {

	/**
	 * Register hooks. No-op on non-POS requests.
	 */
	public function register(): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}
		add_filter( 'woocommerce_persistent_cart_enabled', '__return_false' );
	}
}
