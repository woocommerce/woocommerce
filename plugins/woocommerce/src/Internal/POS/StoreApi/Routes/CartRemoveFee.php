<?php
/**
 * CartRemoveFee class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\CartRemoveFee as StoreApiCartRemoveFee;

/**
 * POS cart/remove-fee route.
 *
 * Extends the Store API's CartRemoveFee (shipped there but not registered under
 * the public `wc/store/v1` namespace) so POS exposes fee removal only under
 * `wc/internal/pos/v1`. The POS-specific endpoint-shape changes are applied by
 * {@see Controller} at registration; request-time behaviour comes from
 * {@see PosRouteTrait}.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartRemoveFee extends StoreApiCartRemoveFee {

	use PosRouteTrait;

	/**
	 * Capability required for any POS request. Override per-route if needed.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';
}
