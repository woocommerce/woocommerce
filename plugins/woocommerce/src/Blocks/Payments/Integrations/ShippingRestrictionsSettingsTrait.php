<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

/**
 * Reads the shipping method restriction settings shared by the offline payment
 * gateways (COD, BACS and Cheque) for use in the block checkout.
 *
 * @internal
 * @since 11.2.0
 */
trait ShippingRestrictionsSettingsTrait {

	/**
	 * Return enable_for_virtual option.
	 *
	 * @return boolean True if the store allows this payment method for orders containing only virtual products.
	 */
	private function get_enable_for_virtual() {
		return filter_var( $this->get_setting( 'enable_for_virtual', true ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Return enable_for_methods option.
	 *
	 * @return array Array of shipping methods (string ids) that allow this payment method. (If empty, all support it.)
	 */
	private function get_enable_for_methods() {
		$enable_for_methods = $this->get_setting( 'enable_for_methods', [] );
		if ( ! is_array( $enable_for_methods ) ) {
			return [];
		}
		return $enable_for_methods;
	}
}
