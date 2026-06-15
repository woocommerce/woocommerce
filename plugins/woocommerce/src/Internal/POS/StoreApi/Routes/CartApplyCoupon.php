<?php
/**
 * CartApplyCoupon class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\CartApplyCoupon as StoreApiCartApplyCoupon;

/**
 * POS cart/apply-coupon route.
 *
 * Extends the Store API's concrete CartApplyCoupon so coupon validation
 * (usage limits, per-customer limits, product restrictions, etc.) runs
 * unchanged. The POS-specific endpoint-shape changes are applied by
 * {@see Controller} at registration time; the request-time behaviour comes
 * from {@see PosRouteTrait} and the POS policy hooks.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartApplyCoupon extends StoreApiCartApplyCoupon {

	use PosRouteTrait;

	/**
	 * Capability required for any POS request. Override per-route if needed.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';
}
