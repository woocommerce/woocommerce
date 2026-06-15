<?php
/**
 * Checkout class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\Checkout as StoreApiCheckout;

/**
 * POS /checkout route.
 *
 * Extends the Store API's concrete Checkout so the full checkout pipeline
 * (and therefore `woocommerce_store_api_checkout_order_processed` and all
 * extension hooks that depend on it) runs unchanged. The POS-specific
 * endpoint-shape changes — including relaxing the schema-level `required` flag
 * on billing/shipping address — are applied by {@see Controller} at
 * registration time; the request-time behaviour comes from {@see PosRouteTrait}
 * and the POS policy hooks.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class Checkout extends StoreApiCheckout {

	use PosRouteTrait;

	/**
	 * Capability required for any POS request.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';
}
