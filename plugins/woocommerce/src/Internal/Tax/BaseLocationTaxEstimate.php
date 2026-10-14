<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

use Automattic\WooCommerce\Enums\DefaultCustomerAddress;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Completes an un-addressed shopper's taxable address with the store's postcode and city
 * when the default customer location is the shop base, so a postcode- or city-scoped base
 * tax rate can be estimated before the shopper enters their own address.
 *
 * This only affects the tax lookup: it does not prefill checkout fields, change the stored
 * customer address, or affect shipping-rate selection, since it filters the taxable address
 * alone. Once the shopper enters a postcode or city, the estimate steps aside.
 *
 * @since 11.3.0
 */
class BaseLocationTaxEstimate implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'estimate_base_location_address' ), 10, 2 );
	}

	/**
	 * Fill the store's postcode and city into an un-addressed shopper's taxable address.
	 *
	 * @param array $taxable_address The taxable address: [ country, state, postcode, city ].
	 * @param mixed $customer        The customer object (unused).
	 * @return array The taxable address, with the store postcode/city filled in when applicable.
	 */
	public function estimate_base_location_address( $taxable_address, $customer ) {
		unset( $customer );

		if ( ! is_array( $taxable_address ) || count( $taxable_address ) < 4 ) {
			return $taxable_address;
		}

		if ( ! wc_tax_enabled() ) {
			return $taxable_address;
		}

		if ( DefaultCustomerAddress::BASE !== get_option( 'woocommerce_default_customer_address', DefaultCustomerAddress::BASE ) ) {
			return $taxable_address;
		}

		list( $country, $state, $postcode, $city ) = array_values( $taxable_address );

		// The shopper has started entering their own location, so stop estimating.
		if ( '' !== (string) $postcode || '' !== (string) $city ) {
			return $taxable_address;
		}

		$countries = WC()->countries;
		if ( ! $countries ) {
			return $taxable_address;
		}

		// Only treat the shopper as "at the shop" when the assumed country and state are the store's.
		// A same-state shopper mid-entry is estimated at the store postcode/city by design; it is a
		// pre-address estimate that corrects once they enter their own postcode or city.
		if ( (string) $country !== (string) $countries->get_base_country() || (string) $state !== (string) $countries->get_base_state() ) {
			return $taxable_address;
		}

		// Reindex so the writes land on the postcode/city slots even if a callback returned string keys,
		// while keeping any trailing fields (e.g. a tax provider's street at index 4). Normalize the
		// postcode to the format an entered address uses, so other consumers of the filter see it consistently.
		$taxable_address    = array_values( $taxable_address );
		$taxable_address[2] = wc_format_postcode( $countries->get_base_postcode(), (string) $country );
		$taxable_address[3] = $countries->get_base_city();

		return $taxable_address;
	}
}
