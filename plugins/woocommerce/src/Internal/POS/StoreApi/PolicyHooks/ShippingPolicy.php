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
 * Returning false via the `woocommerce_cart_needs_shipping` filter short-circuits
 * this at the source — no packages, no shipping cost, no shipping line item.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class ShippingPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks. No-op on non-POS requests.
	 */
	public function register(): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}
		add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
	}
}
