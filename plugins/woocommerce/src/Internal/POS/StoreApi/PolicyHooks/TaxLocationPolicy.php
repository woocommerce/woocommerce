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
 * In-person retail tax is location-based — the jurisdiction is determined by
 * where the transaction physically happens, not by the customer's address.
 * For POS that means tax always resolves to the store's location, regardless
 * of whether a customer is attached to the order and regardless of any
 * address fields on the order.
 *
 * The Store API's checkout pipeline computes tax through
 * {@see \WC_Customer::get_taxable_address()} (called both directly by
 * `WC_Tax` and by the `woocommerce_order_get_tax_location` callback that
 * `OrderController::update_order_from_cart` installs). With {@see CustomerSwap}
 * replacing `WC()->customer` with a blank guest for POS, that taxable
 * address would otherwise be empty and the tax engine would match no rate
 * (zero tax). Hooking `woocommerce_customer_taxable_address` for POS
 * overrides this at the source: the customer's address can stay genuinely
 * empty on the order while tax computation still produces the right rate.
 *
 * The merchant's `woocommerce_tax_based_on` setting (base/billing/shipping)
 * is intentionally bypassed for POS — for in-person sales the answer is
 * always "where the register is," not the configured online-checkout
 * default. Rate tables themselves continue to govern which rate applies.
 *
 * The wrapping `woocommerce_pos_tax_location` filter exists so that future
 * POS work which adds structured per-register address fields (the existing
 * "Settings → Point of Sale → Physical address" setting is currently a
 * free-text textarea that cannot be parsed into country/state/postcode/city
 * reliably) can plug those values in without changing this class. See
 * DECISIONS.md for context.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class TaxLocationPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'override_taxable_address_for_pos' ), 10, 2 );
	}

	/**
	 * Return store base address as the taxable address for POS requests.
	 * Pass through unchanged for non-POS requests.
	 *
	 * @param array       $taxable_address Original [country, state, postcode, city] from WC_Customer::get_taxable_address.
	 * @param WC_Customer $customer        Customer the taxable address was computed for.
	 * @return array
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function override_taxable_address_for_pos( $taxable_address, $customer ): array {
		if ( ! Context::is_pos_request() ) {
			return (array) $taxable_address;
		}

		$store_base = array(
			(string) WC()->countries->get_base_country(),
			(string) WC()->countries->get_base_state(),
			(string) WC()->countries->get_base_postcode(),
			(string) WC()->countries->get_base_city(),
		);

		/**
		 * Filters the tax location used for POS transactions.
		 *
		 * Defaults to the store's base address from
		 * Settings → General → Store Address. The POS-specific Physical
		 * address setting (Settings → Point of Sale → Physical address) is
		 * currently a free-text textarea and can't safely be parsed into
		 * the structured country/state/postcode/city tuple required for
		 * tax lookup; when that setting grows structured fields, the
		 * follow-up work can hook this filter to return them.
		 *
		 * @since 10.9.0
		 *
		 * @param array{0:string,1:string,2:string,3:string} $tax_location [country, state, postcode, city].
		 * @param WC_Customer                                $customer      Customer object whose taxable address was originally requested.
		 */
		return (array) apply_filters( 'woocommerce_pos_tax_location', $store_base, $customer );
	}
}
