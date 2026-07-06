<?php
/**
 * CartPersistencePolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Disables the persistent cart during POS Store API requests.
 *
 * The persistent cart (WC_Cart_Session) is keyed to the logged-in WP user —
 * in POS terms, the *operator*, not the customer. Left enabled, the
 * operator's saved web cart would merge into every fresh transaction cart,
 * and each transaction would overwrite the operator's saved cart in user
 * meta: cart state leaking between the operator and customers, and between
 * transactions processed under the same account. POS session continuity
 * comes from the Cart-Token instead.
 *
 * Registered unconditionally; the POS context is evaluated lazily per call
 * (see SessionHandlerSwap for why).
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class CartPersistencePolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_persistent_cart_enabled', array( $this, 'maybe_disable_persistent_cart' ) );
	}

	/**
	 * Disable the persistent cart for POS requests, pass through otherwise.
	 *
	 * Untyped parameter on purpose — see SessionHandlerSwap::swap_session_handler().
	 *
	 * @param mixed $enabled Whether the persistent cart is enabled.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_disable_persistent_cart( $enabled ) {
		return Context::is_pos_request() ? false : $enabled;
	}
}
