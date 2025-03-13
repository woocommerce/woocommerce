/**
 * External dependencies
 */
import { debounce } from '@woocommerce/base-utils';
import { select, dispatch } from '@wordpress/data';
import type { AdditionalValues } from '@woocommerce/settings';
import { ApiErrorResponse } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { STORE_KEY as CHECKOUT_STORE_KEY } from './constants';
import { STORE_KEY as PAYMENT_STORE_KEY } from '../payment/constants';
import { processErrorResponse } from '../utils';
import { CheckoutPutData } from './types';

// This is used to track and cache the local state of push changes.
const localState = {
	// True when the checkout data has been initialized.
	isInitialized: false,
	// True when a push is currently happening to avoid simultaneous pushes.
	doingPush: false,
	// Local cache of the last pushed checkoutData used for comparisons.
	checkoutData: {
		orderNotes: '',
		additionalFields: {} as AdditionalValues,
		activePaymentMethod: '',
	},
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
	localState.isInitialized = true;
};

/**
 * Function to dispatch an update to the server.
 */
const updateCheckoutData = (): void => {
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
			return (
				localState.checkoutData.additionalFields[ key ] !==
				newCheckoutData.additionalFields[ key ]
			);
		} )
		.reduce( ( acc: AdditionalValues, key ) => {
			acc[ key ] = newCheckoutData.additionalFields[ key ];
			return acc;
		}, {} );

	const requestData: CheckoutPutData = {};

	if ( Object.keys( changedFields ).length > 0 ) {
		requestData.additional_fields = changedFields;
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
