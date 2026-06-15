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
 * pipeline (and therefore the checkout-time extension hooks downstream) runs
 * unchanged. The POS-specific endpoint-shape changes are applied by
 * {@see Controller} at registration time; the request-time behaviour comes
 * from {@see PosRouteTrait} and the POS policy hooks.
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
}
