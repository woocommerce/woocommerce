/**
 * External dependencies
 */
import {
	debounce,
	isAddressFieldForShippingRates,
} from '@woocommerce/base-utils';
import { STATES } from '@woocommerce/block-settings';
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
	// True when changes were flushed while a push was running, so they are sent when it ends.
	flushWhenPushEnds: false,
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
 * Returns true when the country's state field is a list to pick from. Countries without one use a text input.
 */
const hasStateList = ( country: string ): boolean =>
	Object.keys( STATES[ country ] ?? {} ).length > 0;

const isShippingRatesUpdateQueued = () =>
	!! select( cartStore ).getCartMeta().isShippingRatesUpdateQueued;

/**
 * Updates the queued shipping rates flag, skipping the dispatch when it is unchanged.
 */
const setShippingRatesUpdateQueued = ( isQueued: boolean ) => {
	if ( isShippingRatesUpdateQueued() !== isQueued ) {
		dispatch( cartStore ).__internalSetShippingRatesUpdateQueued(
			isQueued
		);
	}
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
		setShippingRatesUpdateQueued( false );
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
		setShippingRatesUpdateQueued( false );
		return;
	}

	const haveAddressFieldsForShippingRatesChanged =
		localState.dirtyProps.shippingAddress.some(
			isAddressFieldForShippingRates
		);

	const endPush = () => {
		localState.doingPush = false;

		if ( localState.flushWhenPushEnds ) {
			localState.flushWhenPushEnds = false;
			updateCustomerData();
		}
	};

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
			endPush();
		} )
		.catch( ( response ) => {
			processErrorResponse( response );
			endPush();
		} );

	// Cleared once the request has started, so the shipping rates loading state takes over without a gap.
	setShippingRatesUpdateQueued( false );
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
 * Sends the changes without waiting for the debounce. Pushes do not overlap, so while one is running
 * the changes are sent when it ends; the scheduled run is kept as a fallback and finds nothing left to send.
 */
export const flushChanges = (): void => {
	if ( localState.doingPush ) {
		localState.flushWhenPushEnds = true;
		return;
	}

	debouncedUpdateCustomerData.clear();
	updateCustomerData();
};

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

	if (
		! isShippingRatesUpdateQueued() &&
		getDirtyKeys(
			localState.customerData.shippingAddress,
			customerData.shippingAddress
		).some( isAddressFieldForShippingRates )
	) {
		setShippingRatesUpdateQueued( true );
	}

	if ( ! debounced ) {
		updateCustomerData();
		return;
	}

	debouncedUpdateCustomerData();

	// Picking a country, or a state from a list, is a deliberate choice rather than typing, and it
	// invalidates the shipping rates already on screen. Push it straight away so the rates reload
	// without waiting for the debounce. A state typed into a text input waits like any other field.
	const pickedFieldChanged = (
		[ 'billingAddress', 'shippingAddress' ] as const
	 ).some( ( addressType ) => {
		const address = customerData[ addressType ];
		const previousAddress = localState.customerData[ addressType ];

		return (
			address.country !== previousAddress.country ||
			( address.state !== previousAddress.state &&
				hasStateList( address.country ) )
		);
	} );

	if ( pickedFieldChanged ) {
		flushChanges();
	}
};
