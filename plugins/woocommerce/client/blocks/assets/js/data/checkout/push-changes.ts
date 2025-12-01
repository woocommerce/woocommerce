/**
 * External dependencies
 */
import { debounce } from '@woocommerce/base-utils';
import { select, dispatch } from '@wordpress/data';
import type { OrderFormValues } from '@woocommerce/settings';
import { ApiErrorResponse } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { STORE_KEY as CHECKOUT_STORE_KEY } from './constants';
import { STORE_KEY as PAYMENT_STORE_KEY } from '../payment/constants';
import { processErrorResponse } from '../utils';
import { CheckoutPutData } from './types';
import {
	clearFieldErrorNotices,
	hasValidationError,
	validateAdditionalFields,
} from './utils';

// This is used to track and cache the local state of push changes.
const localState = {
	// True when the checkout data has been initialized.
	isInitialized: false,
	// True when a push is currently happening to avoid simultaneous pushes.
	doingPush: false,
	// Local cache of the last pushed checkoutData used for comparisons.
	checkoutData: {
		orderNotes: '',
		additionalFields: {} as OrderFormValues,
		activePaymentMethod: '',
	},
	hasSession: false,
};

const isCheckoutBlock = getSetting< boolean >( 'isCheckoutBlock', false );

/**
 * Initializes the checkout & payment data cache on the first run.
 */
const initialize = () => {
	const store = select( CHECKOUT_STORE_KEY );
	const paymentStore = select( PAYMENT_STORE_KEY );
	localState.checkoutData = {
		orderNotes: store.getOrderNotes(),
		additionalFields: store.getAdditionalFields(),
		activePaymentMethod: paymentStore.getActivePaymentMethod(),
	};
	localState.hasSession = document.cookie.includes( 'woocommerce_cart_hash' );
	localState.isInitialized = true;
};

/**
 * Function to dispatch an update to the server.
 */
const updateCheckoutData = (): void => {
	// If we don't have any session, exit early.
	if ( ! localState.hasSession ) {
		return;
	}

	if ( localState.doingPush ) {
		return;
	}

	// Prevent multiple pushes from happening at the same time.
	localState.doingPush = true;

	// Don't push changes if the page contains a cart block, but no checkout block.
	if ( ! isCheckoutBlock ) {
		localState.doingPush = false;
		return;
	}

	// Don't push changes if an express payment method is clicked
	if ( select( PAYMENT_STORE_KEY ).isExpressPaymentStarted() ) {
		localState.doingPush = false;
		return;
	}

	// Get current checkout data from the store
	const checkoutStore = select( CHECKOUT_STORE_KEY );
	const paymentStore = select( PAYMENT_STORE_KEY );
	const newCheckoutData = {
		orderNotes: checkoutStore.getOrderNotes(),
		additionalFields: checkoutStore.getAdditionalFields(),
		activePaymentMethod: paymentStore.getActivePaymentMethod(),
	};

	// Don't push changes if the active payment method is empty
	if ( newCheckoutData.activePaymentMethod === '' ) {
		localState.doingPush = false;
		return;
	}
	// Figure out which additional fields have changed and only send those to the server
	const changedFields = Object.keys( newCheckoutData.additionalFields )
		.filter( ( key ) => {
			// Fields with errors should be ignored
			if ( hasValidationError( key as keyof OrderFormValues ) ) {
				return false;
			}

			// Fields that are not present in the original checkout data and have an empty value should be ignored (happens when a field is hidden on mount).
			if (
				! ( key in localState.checkoutData.additionalFields ) &&
				newCheckoutData.additionalFields[
					key as keyof OrderFormValues
				] === ''
			) {
				return false;
			}

			// Fields that have not changed should be ignored
			if (
				localState.checkoutData.additionalFields[
					key as keyof OrderFormValues
				] ===
				newCheckoutData.additionalFields[ key as keyof OrderFormValues ]
			) {
				return false;
			}
			return true;
		} )
		.reduce( ( acc: OrderFormValues, key ) => {
			acc[ key as keyof OrderFormValues ] =
				newCheckoutData.additionalFields[
					key as keyof OrderFormValues
				];
			return acc;
		}, {} );

	const requestData: CheckoutPutData = {};

	if ( Object.keys( changedFields ).length > 0 ) {
		requestData.additional_fields = changedFields;
	}

	// Validate additional fields before proceeding
	if ( ! validateAdditionalFields( changedFields ) ) {
		localState.doingPush = false;
		localState.checkoutData = newCheckoutData;
		return;
	}

	if ( newCheckoutData.orderNotes !== localState.checkoutData.orderNotes ) {
		requestData.order_notes = newCheckoutData.orderNotes;
	}

	if (
		newCheckoutData.activePaymentMethod !==
		localState.checkoutData.activePaymentMethod
	) {
		requestData.payment_method = newCheckoutData.activePaymentMethod;
	}

	// If nothing's changed, skip update
	if ( Object.keys( requestData ).length === 0 ) {
		localState.doingPush = false;
		return;
	}

	// Update local cache
	localState.checkoutData = newCheckoutData;

	dispatch( CHECKOUT_STORE_KEY )
		.updateDraftOrder( requestData )
		.then( () => {
			clearFieldErrorNotices( requestData );
			localState.doingPush = false;
		} )
		.catch( ( response: ApiErrorResponse ) => {
			localState.doingPush = false;
			processErrorResponse( response );
		} );

	localState.doingPush = false;
};

/**
 * Function to dispatch an update to the server. This is debounced.
 */
const debouncedUpdateCheckoutData = debounce( () => {
	if ( localState.doingPush ) {
		return;
	}
	updateCheckoutData();
}, 1500 );

/**
 * After checkout has fully initialized, pushes changes to the server when data in the store is changed. Updates to the
 * server are debounced to prevent excessive requests.
 */
export const pushChanges = ( debounced = true ): void => {
	if ( ! localState.isInitialized ) {
		initialize();
		return;
	}

	if ( debounced ) {
		debouncedUpdateCheckoutData();
	} else {
		updateCheckoutData();
	}
};

// Cancel the debounced updateCheckoutData function and trigger it immediately.
export const flushChanges = (): void => {
	debouncedUpdateCheckoutData.flush();
};

// Cancel the debounced updateCheckoutData function without trigger it.
export const clearChanges = (): void => {
	debouncedUpdateCheckoutData.clear();
};
