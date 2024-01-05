/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { camelCaseKeys } from '@woocommerce/base-utils';
import { isEmail } from '@wordpress/url';
import {
	CartBillingAddress,
	CartShippingAddress,
	Cart,
	CartResponse,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { STORE_KEY as VALIDATION_STORE_KEY } from '../validation/constants';

export const mapCartResponseToCart = ( responseCart: CartResponse ): Cart => {
	return camelCaseKeys( responseCart ) as unknown as Cart;
};

export const shippingAddressHasValidationErrors = () => {
	const validationStore = select( VALIDATION_STORE_KEY );
	// Check if the shipping address form has validation errors - if not then we know the full required
	// address has been pushed to the server.
	const stateValidationErrors =
		validationStore.getValidationError( 'shipping_state' );
	const address1ValidationErrors =
		validationStore.getValidationError( 'shipping_address_1' );
	const countryValidationErrors =
		validationStore.getValidationError( 'shipping_country' );
	const postcodeValidationErrors =
		validationStore.getValidationError( 'shipping_postcode' );
	const cityValidationErrors =
		validationStore.getValidationError( 'shipping_city' );
	return [
		cityValidationErrors,
		stateValidationErrors,
		address1ValidationErrors,
		countryValidationErrors,
		postcodeValidationErrors,
	].some( ( entry ) => typeof entry !== 'undefined' );
};

export type BaseAddressKey =
	| keyof CartBillingAddress
	| keyof CartShippingAddress;

/**
 * Normalizes address values before push.
 */
export const normalizeAddressProp = (
	key: BaseAddressKey,
	value?: string | undefined
) => {
	// Skip normalizing for any non string field
	if ( typeof value !== 'string' ) {
		return value;
	}
	if ( key === 'email' ) {
		return isEmail( value ) ? value.trim() : '';
	}
	if ( key === 'postcode' ) {
		return value.replace( ' ', '' ).toUpperCase();
	}
	return value.trim();
};

/**
 * Compares two address objects and returns an array of keys that have changed.
 */
export const getDirtyKeys = <
	T extends CartBillingAddress & CartShippingAddress
>(
	// An object containing all previous address information
	previousAddress: Partial< T >,
	// An object containing all address information.
	address: Partial< T >
): BaseAddressKey[] => {
	const previousAddressKeys = Object.keys(
		previousAddress
	) as BaseAddressKey[];

	return previousAddressKeys.filter( ( key: BaseAddressKey ) => {
		return (
			normalizeAddressProp( key, previousAddress[ key ] ) !==
			normalizeAddressProp( key, address[ key ] )
		);
	} );
};

/**
 * Validates dirty props before push.
 */
export const validateDirtyProps = ( dirtyProps: {
	billingAddress: BaseAddressKey[];
	shippingAddress: BaseAddressKey[];
} ): boolean => {
	const validationStore = select( VALIDATION_STORE_KEY );

	const invalidProps = [
		...dirtyProps.billingAddress.filter( ( key ) => {
			return (
				validationStore.getValidationError( 'billing_' + key ) !==
				undefined
			);
		} ),
		...dirtyProps.shippingAddress.filter( ( key ) => {
			return (
				validationStore.getValidationError( 'shipping_' + key ) !==
				undefined
			);
		} ),
	].filter( Boolean );

	return invalidProps.length === 0;
};
