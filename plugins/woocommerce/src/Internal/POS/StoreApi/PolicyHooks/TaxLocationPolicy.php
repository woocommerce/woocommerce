<?php
/**
 * TaxLocationPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Customer;

/**
 * Sets the tax location for POS transactions.
 *
 * In-person retail tax is location-based: the jurisdiction is where the sale
 * happens, not the customer's address, so tax always resolves to the store's
 * location. Since {@see CustomerSwap} leaves the customer address blank, the
 * tax engine would otherwise match no rate; hooking
 * `woocommerce_customer_taxable_address` lets the address stay empty on the
 * order while tax still computes correctly. The merchant's
 * `woocommerce_tax_based_on` setting is intentionally bypassed for POS.
 *
 * The wrapping `woocommerce_pos_tax_location` filter lets future POS work plug
 * in structured per-register address fields without changing this class.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class TaxLocationPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks. No-op on non-POS requests.
	 */
	public function register(): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'override_taxable_address' ), 10, 2 );
	}

	/**
	 * Return store base address as the taxable address.
	 *
	 * @param array       $taxable_address Original [country, state, postcode, city] from WC_Customer::get_taxable_address.
	 * @param WC_Customer $customer        Customer the taxable address was computed for.
	 * @return array
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function override_taxable_address( $taxable_address, $customer ): array {
		unset( $taxable_address );

		$store_base = array(
			(string) WC()->countries->get_base_country(),
			(string) WC()->countries->get_base_state(),
			(string) WC()->countries->get_base_postcode(),
			(string) WC()->countries->get_base_city(),
		);

		/**
		 * Filters the tax location used for POS transactions.
		 *
		 * Defaults to the store's base address (Settings → General → Store
		 * Address). When the POS Physical address setting grows structured
		 * fields, follow-up work can hook this filter to return them.
		 *
		 * @since 10.9.0
		 *
		 * @param array{0:string,1:string,2:string,3:string} $tax_location [country, state, postcode, city].
		 * @param WC_Customer                                $customer      Customer object whose taxable address was originally requested.
		 */
		return (array) apply_filters( 'woocommerce_pos_tax_location', $store_base, $customer );
	}
}
