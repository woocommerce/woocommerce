/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useThrowError } from '../use-throw-error';

/**
 * This is a custom hook for selecting shipping rates
 *
 * @return {Object} This hook will return an object with these properties:
 * 		- selectShippingRate: A function that immediately returns the selected rate and dispatches an action generator.
 *		- isSelectingRate: True when rates are being resolved to the API.
 */
export const useSelectShippingRates = () => {
	const throwError = useThrowError();
	const { selectShippingRate } = useDispatch( storeKey );

	// Sets a rate for a package in state (so changes are shown right away to consumers of the hook) and in the stores.
	const setRate = useCallback(
		( newShippingRate, packageId ) => {
			selectShippingRate( newShippingRate, packageId ).catch(
				( error ) => {
					// we throw this error because an error on selecting a rate is problematic.
					throwError( error );
				}
			);
		},
		[ throwError, selectShippingRate ]
	);

	// See if rates are being selected.
	const isSelectingRate = useSelect( ( select ) => {
		return select( storeKey ).isShippingRateBeingSelected();
	}, [] );

	return {
		selectShippingRate: setRate,
		isSelectingRate,
	};
};
