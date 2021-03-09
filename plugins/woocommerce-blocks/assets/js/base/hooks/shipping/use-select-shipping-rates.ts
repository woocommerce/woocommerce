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
export const useSelectShippingRates = (): {
	selectShippingRate: (
		newShippingRateId: string,
		packageId: string
	) => unknown;
	isSelectingRate: boolean;
} => {
	const throwError = useThrowError();
	const { selectShippingRate } = ( useDispatch( storeKey ) as {
		selectShippingRate: unknown;
	} ) as {
		selectShippingRate: (
			newShippingRateId: string,
			packageId: string
		) => Promise< unknown >;
	};

	// Sets a rate for a package in state (so changes are shown right away to consumers of the hook) and in the stores.
	const setRate = useCallback(
		( newShippingRateId, packageId ) => {
			selectShippingRate( newShippingRateId, packageId ).catch(
				( error ) => {
					// we throw this error because an error on selecting a rate is problematic.
					throwError( error );
				}
			);
		},
		[ throwError, selectShippingRate ]
	);

	// See if rates are being selected.
	const isSelectingRate = useSelect< boolean >( ( select ) => {
		return select( storeKey ).isShippingRateBeingSelected();
	}, [] );

	return {
		selectShippingRate: setRate,
		isSelectingRate,
	};
};
