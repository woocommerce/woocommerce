/**
 * External dependencies
 */
import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { Rate } from '@woocommerce/type-defs/shipping';

/**
 * Internal dependencies
 */
import { useSelectShippingRates } from './use-select-shipping-rates';
import { useStoreEvents } from '../use-store-events';

/**
 * Selected rates are derived by looping over the shipping rates.
 *
 * @param {Array} shippingRates Array of shipping rates.
 * @return {string} Selected rate id.
 */
// This will find the selected rate ID in an array of shipping rates.
const deriveSelectedRateId = ( shippingRates: Rate[] ) =>
	shippingRates.find( ( rate ) => rate.selected )?.rate_id;

/**
 * This is a custom hook for tracking selected shipping rates for a package and selecting a rate. State is used so
 * changes are reflected in the UI instantly.
 *
 * @param {string} packageId Package ID to select rates for.
 * @param {Array} shippingRates an array of packages with shipping rates.
 * @return {Object} This hook will return an object with these properties:
 * 		- selectShippingRate: A function that immediately returns the selected rate and dispatches an action generator.
 * 		- selectedShippingRate: The selected rate id.
 *		- isSelectingRate: True when rates are being resolved to the API.
 */
export const useSelectShippingRate = (
	packageId: string,
	shippingRates: Rate[]
): {
	selectShippingRate: ( newShippingRateId: string ) => unknown;
	selectedShippingRate: string | undefined;
	isSelectingRate: boolean;
} => {
	const { dispatchCheckoutEvent } = useStoreEvents();

	// Rates are selected via the shipping data context provider.
	const { selectShippingRate, isSelectingRate } = useSelectShippingRates();

	// Selected rates are stored in state. This allows shipping rates changes to be shown in the UI instantly.
	// Defaults to the currently selected rate_id.
	const [ selectedShippingRate, setSelectedShippingRate ] = useState( () =>
		deriveSelectedRateId( shippingRates )
	);

	// This ref is used to track when changes come in via the props. When the incoming shipping rates change, update our local state if there are changes to selected methods.
	const currentShippingRates = useRef( shippingRates );
	useEffect( () => {
		if ( ! isShallowEqual( currentShippingRates.current, shippingRates ) ) {
			currentShippingRates.current = shippingRates;
			setSelectedShippingRate( deriveSelectedRateId( shippingRates ) );
		}
	}, [ shippingRates ] );

	// Sets a rate for a package in state (so changes are shown right away to consumers of the hook) and in the stores.
	const setPackageRateId = useCallback(
		( newShippingRateId ) => {
			setSelectedShippingRate( newShippingRateId );
			selectShippingRate( newShippingRateId, packageId );
			dispatchCheckoutEvent( 'set-selected-shipping-rate', {
				shippingRateId: newShippingRateId,
			} );
		},
		[ packageId, selectShippingRate, dispatchCheckoutEvent ]
	);

	return {
		selectShippingRate: setPackageRateId,
		selectedShippingRate,
		isSelectingRate,
	};
};
