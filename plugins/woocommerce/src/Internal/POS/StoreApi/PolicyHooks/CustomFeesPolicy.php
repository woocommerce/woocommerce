<?php
/**
 * CustomFeesPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\CustomFeesStore;
use WC_Cart;

/**
 * Re-applies POS custom fees on every cart calculation.
 *
 * Fees added through the POS `cart/add-fee` route are stored in the session by
 * {@see CustomFeesStore}. WooCommerce clears all fees on every calculation and
 * re-fires `woocommerce_cart_calculate_fees`, so this hook re-applies the stored
 * fees there — the same way core re-applies coupons from their stored codes.
 * Restricted to POS requests so it never affects web or agentic carts.
 *
 * The action is installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CustomFeesPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_custom_fees' ) );
	}

	/**
	 * Re-apply the session-stored POS fees to the cart being calculated. No-op
	 * on non-POS requests.
	 *
	 * @param WC_Cart $cart Cart passed by the `woocommerce_cart_calculate_fees` action.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function apply_custom_fees( WC_Cart $cart ): void {
		if ( ! Context::is_pos_request() || ! WC()->session ) {
			return;
		}
		( new CustomFeesStore( WC()->session ) )->apply_to_cart( $cart );
	}
}
