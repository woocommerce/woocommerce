<?php
/**
 * CartAddFee class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\CartAddFee as StoreApiCartAddFee;

/**
 * POS cart/add-fee route.
 *
 * Extends the Store API's CartAddFee — which ships there but is not registered
 * under the public `wc/store/v1` namespace — so POS exposes ad-hoc custom fees
 * only under `wc/internal/pos/v1`. The POS-specific endpoint-shape changes are
 * applied by {@see Controller} at registration; request-time behaviour comes
 * from {@see PosRouteTrait} and the POS policy hooks (including the re-apply of
 * stored fees).
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartAddFee extends StoreApiCartAddFee {

	use PosRouteTrait;

	/**
	 * Capability required for any POS request. Override per-route if needed.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';
}
