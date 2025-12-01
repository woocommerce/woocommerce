/**
 * External dependencies
 */
import prepareFormFields from '@woocommerce/base-components/cart-checkout/form/prepare-form-fields';
import {
	ADDRESS_FORM_KEYS,
	COUNTRIES,
	STATES,
} from '@woocommerce/block-settings';
import {
	AddressForm,
	BillingAddress,
	defaultFields,
	ShippingAddress,
} from '@woocommerce/settings';
import {
	isObject,
	isString,
	type CartResponseBillingAddress,
	type CartResponseShippingAddress,
	type AddressFieldsForShippingRates as AddressFieldsForShippingRatesType,
} from '@woocommerce/types';
import { decodeEntities } from '@wordpress/html-entities';

export const addressFieldsForShippingRates: AddressFieldsForShippingRatesType =
	[ 'state', 'country', 'postcode', 'city' ];

/**
 * Compare two addresses and see if they are the same.
 */
export const isSameAddress = < T extends ShippingAddress | BillingAddress >(
	address1: T,
	address2: T
): boolean => {
	return ADDRESS_FORM_KEYS.every( ( field ) => {
		return address1[ field ] === address2[ field ];
	} );
};

/**
 * Type-guard.
 */
const isValidAddressKey = (
	key: keyof AddressForm,
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
		defaultFields,
		address.country
	);

	const newAddress = Object.assign( {}, address );

	addressForm.forEach( ( { key, hidden } ) => {
		if ( hidden === true && isValidAddressKey( key, address ) ) {
			newAddress[ key ] = '';
		}
	} );

	return newAddress;
};

/**
 * Sets fields to an empty string in an address.
 *
 * @param {Object} address The address to empty fields from.
 * @return {Object} The address with all fields values removed.
 */
export const emptyAddressFields = <
	T extends CartResponseBillingAddress | CartResponseShippingAddress
>(
	address: T
): T => {
	const addressForm = prepareFormFields(
		ADDRESS_FORM_KEYS,
		defaultFields,
		address.country
	);
	const newAddress = Object.assign( {}, address );

	addressForm.forEach( ( { key } ) => {
		// Clear address fields except country and state to keep consistency with shortcode Checkout.
		if (
			key !== 'country' &&
			key !== 'state' &&
			isValidAddressKey( key, address )
		) {
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
	const formattedCountry = isString( COUNTRIES[ address.country ] )
		? decodeEntities( COUNTRIES[ address.country ] )
		: '';

	const formattedState =
		isObject( STATES[ address.country ] ) &&
		isString( STATES[ address.country ][ address.state ] )
			? decodeEntities( STATES[ address.country ][ address.state ] )
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
 * Checks if all required shipping address fields are completed.
 * Only validates fields that are defined in addressFieldsForShippingRates.
 *
 * @param {CartResponseShippingAddress} address The shipping address to validate.
 * @return {boolean} True if all required shipping fields are filled, false otherwise.
 */
export const hasAllFieldsForShippingRates = (
	address: CartResponseBillingAddress | CartResponseShippingAddress
): boolean => {
	if ( ! address.country ) {
		return false;
	}

	const addressFormWithLocale = prepareFormFields(
		ADDRESS_FORM_KEYS,
		defaultFields,
		address.country
	);

	const filteredAddressForm = addressFormWithLocale.filter( ( { key } ) =>
		addressFieldsForShippingRates.includes( key )
	);

	return filteredAddressForm.every( ( { key, hidden, required } ) => {
		if ( hidden === true || required === false ) {
			return true;
		}
		return isValidAddressKey( key, address ) && address[ key ] !== '';
	} );
};
