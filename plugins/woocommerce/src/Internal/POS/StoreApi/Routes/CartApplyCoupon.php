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
 * unchanged. POS-specific overrides come from {@see PosRouteTrait}.
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

	/**
	 * Endpoint arguments.
	 *
	 * @return array
	 */
	public function get_args() {
		return $this->apply_pos_endpoint_overrides(
			parent::get_args(),
			__( 'Cart session token returned by a prior POS Store API response. Pass it back here to apply a coupon to the cart you previously built.', 'woocommerce' )
		);
	}
}
