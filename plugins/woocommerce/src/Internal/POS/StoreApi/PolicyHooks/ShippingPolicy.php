<?php
/**
 * ShippingPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Tells the cart there is nothing to ship for POS requests.
 *
 * An in-store sale is in-person: the customer leaves with the goods, so no
 * shipping should be computed even when the cart holds physical products.
 * Returning false from the `woocommerce_cart_needs_shipping` filter
 * short-circuits this at the source — no packages, no shipping cost, no
 * shipping line item.
 *
 * The filter is installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class ShippingPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_cart_needs_shipping', array( $this, 'maybe_disable_shipping' ) );
	}

	/**
	 * Report no shipping needs on POS requests, leaving web behaviour untouched.
	 *
	 * @param bool $needs_shipping Whether the cart needs shipping.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_disable_shipping( $needs_shipping ) {
		return Context::is_pos_request() ? false : $needs_shipping;
	}
}
