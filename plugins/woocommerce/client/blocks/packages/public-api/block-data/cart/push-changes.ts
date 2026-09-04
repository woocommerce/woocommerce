/**
 * External dependencies
 */
import {
	debounce,
	addressFieldsForShippingRates,
} from '@woocommerce/base-utils';
import { CartBillingAddress, CartShippingAddress } from '@woocommerce/types';
import { select, dispatch } from '@wordpress/data';
import isShallowEqual from '@wordpress/is-shallow-equal';

/**
 * Internal dependencies
 */
import { store as cartStore } from './index';
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
	localState.customerData = select( cartStore ).getCustomerData();
	localState.customerDataIsInitialized = true;
};

/**
 * Checks customer data against new customer data to get a list of dirty props.
 */
const updateDirtyProps = () => {
	// Returns all current customer data from the store.
	const newCustomerData = select( cartStore ).getCustomerData();

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

	const dirtyShippingAddress = localState.dirtyProps.shippingAddress;
	const dirtyBillingAddress = localState.dirtyProps.billingAddress;

	const customerShippingAddress = localState.customerData.shippingAddress;
	const customerBillingAddress = localState.customerData.billingAddress;

	// Check if country is changing without state
	const shippingCountryChanged = dirtyShippingAddress.includes( 'country' );
	const billingCountryChanged = dirtyBillingAddress.includes( 'country' );
	const shippingStateChanged = dirtyShippingAddress.includes( 'state' );
	const billingStateChanged = dirtyBillingAddress.includes( 'state' );
	const shippingPostcodeChanged = dirtyShippingAddress.includes( 'postcode' );
	const billingPostcodeChanged = dirtyBillingAddress.includes( 'postcode' );

	if ( shippingCountryChanged && ! shippingPostcodeChanged ) {
		dirtyShippingAddress.push( 'postcode' );
		customerShippingAddress.postcode = '';
	}

	if ( billingCountryChanged && ! billingPostcodeChanged ) {
		dirtyBillingAddress.push( 'postcode' );
		customerBillingAddress.postcode = '';
	}

	if ( shippingCountryChanged && ! shippingStateChanged ) {
		dirtyShippingAddress.push( 'state' );
		customerShippingAddress.state = '';
	}

	if ( billingCountryChanged && ! billingStateChanged ) {
		dirtyBillingAddress.push( 'state' );
		customerBillingAddress.state = '';
	}
};

/**
 * Fields a country change resets, since their values only make sense for the previous country.
 */
const countryDependentFields = [ 'state', 'postcode' ] as const;

/**
 * Returns the dirty props that need to pass validation before the address can be pushed.
 *
 * When the country changes, the state and postcode are reset and are therefore invalid until
 * the customer fills them in again. Waiting for them would leave the server calculating
 * shipping for the previous country, so they are skipped while they are still empty.
 */
const getDirtyPropsToValidate = (
	dirtyProps: BaseAddressKey[],
	address: CartBillingAddress | CartShippingAddress
): BaseAddressKey[] => {
	if ( ! dirtyProps.includes( 'country' ) ) {
		return dirtyProps;
	}

	const emptiedByCountryChange = countryDependentFields.filter(
		( field ) => ! address[ field ]
	) as BaseAddressKey[];

	return dirtyProps.filter(
		( key ) => ! emptiedByCountryChange.includes( key )
	);
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

	const isBillingAddressDirty =
		localState.dirtyProps.billingAddress.length > 0;
	const isShippingAddressDirty =
		localState.dirtyProps.shippingAddress.length > 0;

	// Do we need to push anything?
	const needsPush = isBillingAddressDirty || isShippingAddressDirty;

	if ( ! needsPush ) {
		localState.doingPush = false;
		return;
	}

	// Check props are valid, or abort.
	if (
		! validateDirtyProps( {
			billingAddress: getDirtyPropsToValidate(
				localState.dirtyProps.billingAddress,
				localState.customerData.billingAddress
			),
			shippingAddress: getDirtyPropsToValidate(
				localState.dirtyProps.shippingAddress,
				localState.customerData.shippingAddress
			),
		} )
	) {
		localState.doingPush = false;
		return;
	}

	const haveAddressFieldsForShippingRatesChanged =
		localState.dirtyProps.shippingAddress.some( ( field ) =>
			addressFieldsForShippingRates.includes( field as string )
		);

	dispatch( cartStore )
		.updateCustomerData(
			{
				...( isBillingAddressDirty && {
					billing_address: localState.customerData.billingAddress,
				} ),
				...( isShippingAddressDirty && {
					shipping_address: localState.customerData.shippingAddress,
				} ),
			},
			true,
			haveAddressFieldsForShippingRatesChanged
		)
		.then( () => {
			localState.dirtyProps.billingAddress = [];
			localState.dirtyProps.shippingAddress = [];
			localState.doingPush = false;
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
	if ( ! select( cartStore ).hasFinishedResolution( 'getCartData' ) ) {
		return;
	}

	if ( ! localState.customerDataIsInitialized ) {
		initialize();
		return;
	}

	const customerData = select( cartStore ).getCustomerData();

	if ( isShallowEqual( localState.customerData, customerData ) ) {
		return;
	}

	if ( ! debounced ) {
		updateCustomerData();
		return;
	}

	debouncedUpdateCustomerData();

	// Picking a country is a deliberate choice rather than typing, and it invalidates the
	// shipping rates already on screen. Push it straight away so the rates reload instead of
	// showing the previous country's options for the length of the debounce.
	const countryChanged =
		customerData.billingAddress.country !==
			localState.customerData.billingAddress.country ||
		customerData.shippingAddress.country !==
			localState.customerData.shippingAddress.country;

	if ( countryChanged ) {
		debouncedUpdateCustomerData.flush();
	}
};

// Cancel the debounced updateCustomerData function and trigger it immediately.
export const flushChanges = (): void => {
	debouncedUpdateCustomerData.flush();
};
