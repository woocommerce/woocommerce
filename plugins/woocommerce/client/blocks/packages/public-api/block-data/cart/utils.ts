/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { camelCaseKeys, debounce } from '@woocommerce/base-utils';
import { isEmail } from '@wordpress/url';
import {
	CartBillingAddress,
	CartShippingAddress,
	Cart,
	CartResponse,
} from '@woocommerce/types';
import { CurriedSelectorsOf } from '@wordpress/data/build-types/types';

/**
 * Internal dependencies
 */
import type { ValidationStoreDescriptor } from '../validation';
import { STORE_KEY as VALIDATION_STORE_KEY } from '../validation/constants';
import { CART_STORE_KEY } from './index';
import type { CartStoreDescriptor } from './index';

export const mapCartResponseToCart = ( responseCart: CartResponse ): Cart => {
	return camelCaseKeys( responseCart ) as unknown as Cart;
};

export const shippingAddressHasValidationErrors = () => {
	const validationStore = select(
		VALIDATION_STORE_KEY
	) as CurriedSelectorsOf< ValidationStoreDescriptor >;
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
	T extends CartBillingAddress & CartShippingAddress,
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
 *
 * If the country field is dirty for an address, the dependent state and
 * postcode fields are expected to be in a transitional state (cleared by the
 * country-change handler in form.tsx). The country change itself is the
 * meaningful update and should reach the server immediately so the order
 * summary recalculates taxes. The dependent fields stay validatable for
 * non-country changes, so an invalid postcode typed by the user still
 * blocks the push.
 */
export const validateDirtyProps = ( dirtyProps: {
	billingAddress: BaseAddressKey[];
	shippingAddress: BaseAddressKey[];
} ): boolean => {
	const validationStore = select(
		VALIDATION_STORE_KEY
	) as CurriedSelectorsOf< ValidationStoreDescriptor >;
	const customerData = (
		select( CART_STORE_KEY ) as CurriedSelectorsOf< CartStoreDescriptor >
	 ).getCustomerData();

	const invalidFieldsForAddress = (
		prefix: 'billing_' | 'shipping_',
		keys: BaseAddressKey[],
		address: Partial< CartBillingAddress & CartShippingAddress >
	) => {
		const invalidKeys = keys.filter( ( key ) => {
			return (
				validationStore.getValidationError( prefix + key ) !== undefined
			);
		} );

		if ( invalidKeys.length === 0 ) {
			return [];
		}

		// A country change clears state and postcode to empty strings. Allow
		// the country update to go through when those dependents are still
		// empty (the country-change handler reset them) but keep blocking
		// when the customer has typed a non-empty invalid value into one of
		// the dependents. See #67344.
		if (
			keys.includes( 'country' ) &&
			invalidKeys.every( ( key ) => {
				if ( key !== 'state' && key !== 'postcode' ) {
					return false;
				}
				return ( address[ key ] ?? '' ) === '';
			} )
		) {
			return [];
		}

		return invalidKeys;
	};

	const invalidProps = [
		...invalidFieldsForAddress(
			'billing_',
			dirtyProps.billingAddress,
			customerData.billingAddress
		),
		...invalidFieldsForAddress(
			'shipping_',
			dirtyProps.shippingAddress,
			customerData.shippingAddress
		),
	].filter( Boolean );

	return invalidProps.length === 0;
};

/**
 * Gets the localStorage flag to indicate whether the customer data is dirty.
 */
export const getIsCustomerDataDirty = () => {
	return (
		window.localStorage.getItem(
			'WOOCOMMERCE_CHECKOUT_IS_CUSTOMER_DATA_DIRTY'
		) === 'true'
	);
};

/**
 * Sets a flag in localStorage to indicate whether the customer data has been modified.
 */
export const setIsCustomerDataDirty = debounce(
	( isCustomerDataDirty: boolean ) => {
		window.localStorage.setItem(
			'WOOCOMMERCE_CHECKOUT_IS_CUSTOMER_DATA_DIRTY',
			isCustomerDataDirty ? 'true' : 'false'
		);
	},
	300
);

/**
 * Sets whether it should trigger the event to sync with the Interactivity API
 * store. It's used to prevent emiting the `wc-blocks_store_sync_required`
 * event and causing an infinite loop.
 */
let triggerStoreSyncEvent = true;
export const setTriggerStoreSyncEvent = ( value: boolean ) => {
	triggerStoreSyncEvent = value;
};
export const getTriggerStoreSyncEvent = () => triggerStoreSyncEvent;
