<?php
/**
 * ShippingPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * POS carts never need shipping — the customer walks out with the goods.
 *
 * Keeping `woocommerce_cart_needs_shipping` false in POS context prevents
 * shipping-rate calculation and, downstream, shipping-based checkout
 * validation from applying to in-person sales.
 *
 * Registered unconditionally; the POS context is evaluated lazily per call
 * (see SessionHandlerSwap for why).
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class ShippingPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_cart_needs_shipping', array( $this, 'maybe_disable_shipping' ) );
	}

	/**
	 * Disable shipping for POS requests, pass through otherwise.
	 *
	 * Untyped parameter on purpose — see SessionHandlerSwap::swap_session_handler().
	 *
	 * @param mixed $needs_shipping Whether the cart needs shipping.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_disable_shipping( $needs_shipping ) {
		return Context::is_pos_request() ? false : $needs_shipping;
	}
}
