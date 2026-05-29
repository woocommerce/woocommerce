<?php
/**
 * CartAddItem class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\CartAddItem as StoreApiCartAddItem;

/**
 * POS cart/add-item route.
 *
 * Extends the Store API's concrete CartAddItem so the full add-to-cart
 * pipeline (and therefore the checkout-time extension hooks downstream)
 * runs unchanged. POS-specific overrides come from {@see PosRouteTrait}.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartAddItem extends StoreApiCartAddItem {

	use PosRouteTrait;

	/**
	 * Capability required for any POS request. Override per-route if needed.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * Endpoint arguments.
	 *
	 * @return array
	 */
	public function get_args() {
		return $this->apply_pos_endpoint_overrides(
			parent::get_args(),
			__( 'Cart session token returned by a prior POS Store API response. Pass it back on subsequent requests to keep the cart scoped to the same transaction.', 'woocommerce' )
		);
	}
}
