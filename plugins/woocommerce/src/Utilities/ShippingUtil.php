<?php
/**
 * ShippingUtil class file.
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Utilities;

/**
 * The ShippingUtil class provides utilities for working with shipping and shipping packages.
 */
class ShippingUtil {

	/**
	 * Get the selected shipping rates from the packages.
	 *
	 * @param array $packages The packages to get the selected shipping rates from.
	 * @return \WC_Shipping_Rate[] The selected shipping rates.
	 */
	public static function get_selected_shipping_rates_from_packages( $packages ) {
		return array_filter(
			array_map(
				function ( $package_id, $package ) {
					$selected_rate_id = wc_get_chosen_shipping_method_for_package( $package_id, $package );
					$selected_rate    = false !== $selected_rate_id && isset( $package['rates'][ $selected_rate_id ] ) ? $package['rates'][ $selected_rate_id ] : null;

					return $selected_rate instanceof \WC_Shipping_Rate ? $selected_rate : null;
				},
				array_keys( $packages ),
				array_values( $packages )
			)
		);
	}
}
