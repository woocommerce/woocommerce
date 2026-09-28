<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

/**
 * Decides whether local pickup replaces the customer's address for tax.
 *
 * When it applies, core taxes at the store base address and Blocks refines that to the pickup location's address.
 * This must match the local pickup check in WC_Customer::get_taxable_address().
 *
 * @see \WC_Customer::get_taxable_address()
 * @see \WC_Abstract_Order::get_tax_location()
 *
 * @since 11.3.0
 */
class LocalPickupTaxRule {

	/**
	 * Determine whether local pickup replaces the customer's address for tax, given the chosen shipping methods.
	 *
	 * @since 11.3.0
	 *
	 * @param string[] $shipping_method_ids Shipping method IDs without instance IDs, e.g. 'local_pickup'.
	 * @return bool True when one of the methods is local pickup and base tax for local pickup is enabled.
	 */
	public static function applies_to_shipping_methods( array $shipping_method_ids ): bool {
		/**
		 * Filters whether tax is based on the store address for local pickup.
		 *
		 * @since 6.8.0
		 *
		 * @param bool $apply_base_tax Whether to apply store-address tax for local pickup.
		 */
		if ( true !== apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) ) {
			return false;
		}

		/**
		 * Filters local pickup shipping methods.
		 *
		 * @since 6.8.0
		 *
		 * @param string[] $local_pickup_methods Local pickup shipping method IDs.
		 */
		$local_pickup_methods = apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) );
		if ( ! is_array( $local_pickup_methods ) ) {
			return false;
		}

		return count( array_intersect( $shipping_method_ids, array_filter( $local_pickup_methods, 'is_string' ) ) ) > 0;
	}
}
