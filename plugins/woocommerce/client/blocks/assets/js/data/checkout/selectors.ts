/**
 * External dependencies
 */
import { createSelector, select } from '@wordpress/data';
import { hasCollectableRate } from '@woocommerce/base-utils';
import { isString, objectHasProp } from '@woocommerce/types';
import { getSetting, type AddressFormType } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { STATUS } from './constants';
import { CheckoutState } from './default-state';
import { STORE_KEY as cartStoreKey } from '../cart/constants';

/**
 * Returns the core subset of checkout state. Pairs with the `getCheckoutData`
 * resolver, which fetches the full checkout payload from the Store API when
 * the page is not server-hydrated.
 */
export const getCheckoutData = createSelector(
	( state: CheckoutState ) => {
		return {
			orderId: state.orderId,
			customerId: state.customerId,
			orderNotes: state.orderNotes,
			additionalFields: state.additionalFields,
		};
	},
	( state: CheckoutState ) => [
		state.orderId,
		state.customerId,
		state.orderNotes,
		state.additionalFields,
	]
);

/**
 * Whether the checkout payload was server-hydrated.
 *
 * Returns `false` when the checkout data is fetched on the client instead.
 * Extensions (payment gateways, custom fields) can read this to defer
 * rendering per-user UI until the client-side data has loaded.
 */
export const isCheckoutDataHydrated = (): boolean => {
	return (
		Object.keys(
			getSetting< Record< string, unknown > >( 'checkoutData', {} )
		).length > 0
	);
};

export const getCustomerId = ( state: CheckoutState ) => {
	return state.customerId;
};

export const getCustomerPassword = ( state: CheckoutState ) => {
	return state.customerPassword;
};

export const getOrderId = ( state: CheckoutState ) => {
	return state.orderId;
};

export const getOrderNotes = ( state: CheckoutState ) => {
	return state.orderNotes;
};

export const getRedirectUrl = ( state: CheckoutState ) => {
	return state.redirectUrl;
};

export const getUseShippingAsBilling = ( state: CheckoutState ) => {
	return state.useShippingAsBilling;
};

export const getEditingBillingAddress = ( state: CheckoutState ) => {
	return state.editingBillingAddress;
};

export const getEditingShippingAddress = ( state: CheckoutState ) => {
	return state.editingShippingAddress;
};

export const getExtensionData = ( state: CheckoutState ) => {
	return state.extensionData;
};

export const getShouldCreateAccount = ( state: CheckoutState ) => {
	return state.shouldCreateAccount;
};

export const getAdditionalFields = ( state: CheckoutState ) => {
	return state.additionalFields;
};

export const getCheckoutStatus = ( state: CheckoutState ) => {
	return state.status;
};

export const hasError = ( state: CheckoutState ) => {
	return state.hasError;
};

export const hasOrder = ( state: CheckoutState ) => {
	return !! state.orderId;
};

export const isComplete = ( state: CheckoutState ) => {
	return state.status === STATUS.COMPLETE;
};

export const isIdle = ( state: CheckoutState ) => {
	return state.status === STATUS.IDLE;
};

export const isBeforeProcessing = ( state: CheckoutState ) => {
	return state.status === STATUS.BEFORE_PROCESSING;
};

export const isAfterProcessing = ( state: CheckoutState ) => {
	return state.status === STATUS.AFTER_PROCESSING;
};

export const isProcessing = ( state: CheckoutState ) => {
	return state.status === STATUS.PROCESSING;
};

export const isCalculating = ( state: CheckoutState ) => {
	return state.calculatingCount > 0;
};

export const prefersCollection = ( state: CheckoutState ) => {
	if ( typeof state.prefersCollection === 'undefined' ) {
		const shippingRates = select( cartStoreKey ).getShippingRates();
		if ( ! shippingRates || ! shippingRates.length ) {
			return false;
		}
		const selectedRate = shippingRates[ 0 ].shipping_rates.find(
			( rate ) => rate.selected
		);

		if (
			objectHasProp( selectedRate, 'method_id' ) &&
			isString( selectedRate.method_id )
		) {
			return hasCollectableRate( selectedRate?.method_id );
		}
	}
	return state.prefersCollection;
};

/**
 * Get registered address autocomplete providers.
 *
 * @param state
 */
export const getRegisteredAutocompleteProviders = ( state: CheckoutState ) => {
	return state.addressAutocompleteProviders;
};

/**
 * Get active address autocomplete provider.
 *
 * @param state
 * @param type
 */
export const getActiveAutocompleteProvider = (
	state: CheckoutState,
	type: AddressFormType
) => {
	return state.activeAddressAutocompleteProvider?.[ type ];
};
