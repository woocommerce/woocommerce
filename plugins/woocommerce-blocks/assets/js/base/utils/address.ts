/**
 * External dependencies
 */
import prepareFormFields from '@woocommerce/base-components/cart-checkout/form/prepare-form-fields';
import { isEmail } from '@wordpress/url';
import type {
	CartResponseBillingAddress,
	CartResponseShippingAddress,
} from '@woocommerce/types';
import { ShippingAddress, BillingAddress } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import {
	SHIPPING_COUNTRIES,
	SHIPPING_STATES,
	ADDRESS_FORM_KEYS,
} from '@woocommerce/block-settings';

/**
 * Compare two addresses and see if they are the same.
 */
export const isSameAddress = < T extends ShippingAddress | BillingAddress >(
	address1: T,
	address2: T
): boolean => {
	return ADDRESS_FORM_KEYS.every( ( field: string ) => {
		return address1[ field as keyof T ] === address2[ field as keyof T ];
	} );
};

/**
 * pluckAddress takes a full address object and returns relevant fields for calculating
 * shipping, so we can track when one of them change to update rates.
 *
 * @param {Object} address          An object containing all address information
 * @param {string} address.country  The country.
 * @param {string} address.state    The state.
 * @param {string} address.city     The city.
 * @param {string} address.postcode The postal code.
 *
 * @return {Object} pluckedAddress  An object containing shipping address that are needed to fetch an address.
 */
export const pluckAddress = ( {
	country = '',
	state = '',
	city = '',
	postcode = '',
}: CartResponseBillingAddress | CartResponseShippingAddress ): {
	country: string;
	state: string;
	city: string;
	postcode: string;
} => ( {
	country: country.trim(),
	state: state.trim(),
	city: city.trim(),
	postcode: postcode ? postcode.replace( ' ', '' ).toUpperCase() : '',
} );

/**
 * pluckEmail takes a full address object and returns only the email address, if set and valid. Otherwise returns an empty string.
 *
 * @param {Object} address       An object containing all address information
 * @param {string} address.email The email address.
 * @return {string} The email address.
 */
export const pluckEmail = ( {
	email = '',
}: CartResponseBillingAddress ): string =>
	isEmail( email ) ? email.trim() : '';

/**
 * Type-guard.
 */
const isValidAddressKey = (
	key: string,
	address: CartResponseBillingAddress | CartResponseShippingAddress
): key is keyof typeof address => {
	return key in address;
};

/**
 * Sets fields to an empty string in an address if they are hidden by the settings in countryLocale.
 *
 * @param {Object} address The address to empty fields from.
 * @return {Object} The address with hidden fields values removed.
 */
export const emptyHiddenAddressFields = <
	T extends CartResponseBillingAddress | CartResponseShippingAddress
>(
	address: T
): T => {
	const addressForm = prepareFormFields(
		ADDRESS_FORM_KEYS,
		{},
		address.country
	);
	const newAddress = Object.assign( {}, address ) as T;

	addressForm.forEach( ( { key = '', hidden = false } ) => {
		if ( hidden && isValidAddressKey( key, address ) ) {
			newAddress[ key ] = '';
		}
	} );

	return newAddress;
};

/*
 * Formats a shipping address for display.
 *
 * @param {Object} address The address to format.
 * @return {string | null} The formatted address or null if no address is provided.
 */
export const formatShippingAddress = (
	address: ShippingAddress | BillingAddress
): string | null => {
	// We bail early if we don't have an address.
	if ( Object.values( address ).length === 0 ) {
		return null;
	}
	const formattedCountry =
		typeof SHIPPING_COUNTRIES[ address.country ] === 'string'
			? decodeEntities( SHIPPING_COUNTRIES[ address.country ] )
			: '';

	const formattedState =
		typeof SHIPPING_STATES[ address.country ] === 'object' &&
		typeof SHIPPING_STATES[ address.country ][ address.state ] === 'string'
			? decodeEntities(
					SHIPPING_STATES[ address.country ][ address.state ]
			  )
			: address.state;

	const addressParts = [];

	addressParts.push( address.postcode.toUpperCase() );
	addressParts.push( address.city );
	addressParts.push( formattedState );
	addressParts.push( formattedCountry );

	const formattedLocation = addressParts.filter( Boolean ).join( ', ' );

	if ( ! formattedLocation ) {
		return null;
	}

	return formattedLocation;
};

/**
 * Checks that all required fields in an address are completed based on the settings in countryLocale.
 */
export const isAddressComplete = (
	address: ShippingAddress | BillingAddress
): boolean => {
	if ( ! address.country ) {
		return false;
	}
	const addressForm = prepareFormFields(
		ADDRESS_FORM_KEYS,
		{},
		address.country
	);

	return addressForm.every(
		( { key = '', hidden = false, required = false } ) => {
			if ( hidden || ! required ) {
				return true;
			}
			return isValidAddressKey( key, address ) && address[ key ] !== '';
		}
	);
};
