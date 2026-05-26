<?php
/**
 * CartAddItem class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

/**
 * POS wrapper for the Store API cart/add-item route.
 *
 * Adds a POS capability check; otherwise delegates fully to the Store API
 * route handler. This is the canonical example of the wrapper-delegation
 * pattern — see {@see AbstractRoute} for the full rationale.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartAddItem extends AbstractRoute {

	/**
	 * Route identifier used to look up the Store API delegate.
	 */
	public const STORE_API_IDENTIFIER = 'cart-add-item';
}
