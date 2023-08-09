/**
 * External dependencies
 */
import { removeAllNotices, debounce, pick } from '@woocommerce/base-utils';
import {
	CartBillingAddress,
	CartShippingAddress,
	BillingAddressShippingAddress,
} from '@woocommerce/types';
import { select, dispatch } from '@wordpress/data';
import isShallowEqual from '@wordpress/is-shallow-equal';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import { processErrorResponse } from '../utils';
import { getDirtyKeys, validateDirtyProps, BaseAddressKey } from './utils';

// This is used to track and cache the local state of push changes.
const localState = {
	// True when the customer data has been initialized.
	customerDataIsInitialized: false,
	// True when a push is currently happening to avoid simultaneous pushes.
	doingPush: false,
	// Local cache of the last pushed customerData used for comparisons.
	customerData: {
		billingAddress: {} as CartBillingAddress,
		shippingAddress: {} as CartShippingAddress,
	},
	// Tracks which props have changed so the correct data gets pushed to the server.
	dirtyProps: {
		billingAddress: [] as BaseAddressKey[],
		shippingAddress: [] as BaseAddressKey[],
	},
};

/**
 * Initializes the customer data cache on the first run.
 */
const initialize = () => {
	localState.customerData = select( STORE_KEY ).getCustomerData();
	localState.customerDataIsInitialized = true;
};

/**
 * Checks customer data against new customer data to get a list of dirty props.
 */
const updateDirtyProps = () => {
	// Returns all current customer data from the store.
	const newCustomerData = select( STORE_KEY ).getCustomerData();

	localState.dirtyProps.billingAddress = [
		...localState.dirtyProps.billingAddress,
		...getDirtyKeys(
			localState.customerData.billingAddress,
			newCustomerData.billingAddress
		),
	];

	localState.dirtyProps.shippingAddress = [
		...localState.dirtyProps.shippingAddress,
		...getDirtyKeys(
			localState.customerData.shippingAddress,
			newCustomerData.shippingAddress
		),
	];

	// Update local cache of customer data so the next time this runs, it can compare against the latest data.
	localState.customerData = newCustomerData;
};

/**
 * Function to dispatch an update to the server.
 */
const updateCustomerData = (): void => {
	if ( localState.doingPush ) {
		return;
	}

	// Prevent multiple pushes from happening at the same time.
	localState.doingPush = true;

	// Get updated list of dirty props by comparing customer data.
	updateDirtyProps();

	// Do we need to push anything?
	const needsPush =
		localState.dirtyProps.billingAddress.length > 0 ||
		localState.dirtyProps.shippingAddress.length > 0;

	if ( ! needsPush ) {
		localState.doingPush = false;
		return;
	}

	// Check props are valid, or abort.
	if ( ! validateDirtyProps( localState.dirtyProps ) ) {
		localState.doingPush = false;
		return;
	}

	// Find valid data from the list of dirtyProps and prepare to push to the server.
	const customerDataToUpdate = {} as Partial< BillingAddressShippingAddress >;

	if ( localState.dirtyProps.billingAddress.length ) {
		customerDataToUpdate.billing_address = pick(
			localState.customerData.billingAddress,
			localState.dirtyProps.billingAddress
		);
	}

	if ( localState.dirtyProps.shippingAddress.length ) {
		customerDataToUpdate.shipping_address = pick(
			localState.customerData.shippingAddress,
			localState.dirtyProps.shippingAddress
		);
	}

	dispatch( STORE_KEY )
		.updateCustomerData( customerDataToUpdate )
		.then( () => {
			localState.dirtyProps.billingAddress = [];
			localState.dirtyProps.shippingAddress = [];
			localState.doingPush = false;
			removeAllNotices();
		} )
		.catch( ( response ) => {
			localState.doingPush = false;
			processErrorResponse( response );
		} );
};

/**
 * Function to dispatch an update to the server. This is debounced.
 */
const debouncedUpdateCustomerData = debounce( () => {
	if ( localState.doingPush ) {
		debouncedUpdateCustomerData();
		return;
	}
	updateCustomerData();
}, 1500 );

/**
 * After cart has fully initialized, pushes changes to the server when data in the store is changed. Updates to the
 * server are debounced to prevent excessive requests.
 *
 * Any update to the store triggers this, so we do a shallow compare on the important data to know if we really need to
 * schedule a push.
 */
export const pushChanges = ( debounced = true ): void => {
	if ( ! select( STORE_KEY ).hasFinishedResolution( 'getCartData' ) ) {
		return;
	}

	if ( ! localState.customerDataIsInitialized ) {
		initialize();
		return;
	}

	if (
		isShallowEqual(
			localState.customerData,
			select( STORE_KEY ).getCustomerData()
		)
	) {
		return;
	}

	if ( debounced ) {
		debouncedUpdateCustomerData();
	} else {
		updateCustomerData();
	}
};

// Cancel the debounced updateCustomerData function and trigger it immediately.
export const flushChanges = (): void => {
	debouncedUpdateCustomerData.flush();
};
