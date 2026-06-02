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
 * extension hooks that depend on it) runs unchanged. POS-specific overrides
 * come from {@see PosRouteTrait}; the only extra Checkout-specific override
 * is relaxing the schema-level `required` flag on billing/shipping address.
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

	/**
	 * Endpoint arguments.
	 *
	 * @return array
	 */
	public function get_args() {
		$endpoints = $this->apply_pos_endpoint_overrides(
			parent::get_args(),
			__( 'Cart session token returned by a prior POS Store API response. Pass it back here to check out the cart you previously built.', 'woocommerce' )
		);

		// Drop the schema-level required flag on billing/shipping address so
		// POS can submit empty addresses at parse time. The deeper validation
		// pipeline is already relaxed via the POS policy hooks.
		foreach ( $endpoints as $key => &$endpoint ) {
			if ( ! is_int( $key ) || ! is_array( $endpoint ) || ! isset( $endpoint['methods'] ) ) {
				continue;
			}
			foreach ( array( 'billing_address', 'shipping_address' ) as $address_arg ) {
				if ( isset( $endpoint['args'][ $address_arg ] ) && is_array( $endpoint['args'][ $address_arg ] ) ) {
					$endpoint['args'][ $address_arg ]['required'] = false;
				}
			}
		}
		unset( $endpoint );

		return $endpoints;
	}
}
