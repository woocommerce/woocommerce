<?php
/**
 * CustomFeesPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\CustomFeesStore;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Cart;

/**
 * Re-applies the transaction's stored custom fees on every cart calculation.
 *
 * See {@see CustomFeesStore} for why fees must be re-applied rather than
 * added once.
 *
 * Registered unconditionally; the POS context is evaluated lazily per call
 * (see SessionHandlerSwap for why).
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class CustomFeesPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_custom_fees' ) );
	}

	/**
	 * Apply the stored fees during POS requests.
	 *
	 * @param WC_Cart $cart The cart being calculated.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function apply_custom_fees( $cart ): void {
		if ( ! Context::is_pos_request() || ! $cart instanceof WC_Cart || ! WC()->session ) {
			return;
		}

		( new CustomFeesStore( WC()->session ) )->apply_to_cart( $cart );
	}
}
