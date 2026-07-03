/**
 * External dependencies
 */
import {
	ALLOWED_COUNTRIES,
	SHIPPING_COUNTRIES,
} from '@woocommerce/block-settings';
import type { AddressFormType } from '@woocommerce/settings';

/**
 * Resolves the country to use for address autocomplete requests, constrained
 * to the countries the store actually allows for the given address type.
 *
 * The address's own country value can be stale, geolocated, or otherwise
 * unrelated to the store's "sell to"/"ship to" settings, so it isn't safe to
 * forward as-is to the autocomplete provider.
 *
 * @param country     Country code currently on the address.
 * @param addressType Type of address ('billing' or 'shipping').
 * @return The country to search/select with, or an empty string when there's
 *         no unambiguous allowed country to use yet.
 */
export function getAutocompleteCountry(
	country: string,
	addressType: AddressFormType
): string {
	const allowedCountries =
		addressType === 'shipping' ? SHIPPING_COUNTRIES : ALLOWED_COUNTRIES;
	const allowedCodes = Object.keys( allowedCountries );

	if ( allowedCodes.includes( country ) ) {
		return country;
	}

	// Store locked to a single country: use it, regardless of whatever
	// (stale/mismatched) country is currently on the address.
	if ( allowedCodes.length === 1 ) {
		return allowedCodes[ 0 ];
	}

	// Ambiguous: more than one allowed country and none currently selected
	// matches. Callers should treat this as "no usable country yet".
	return '';
}
