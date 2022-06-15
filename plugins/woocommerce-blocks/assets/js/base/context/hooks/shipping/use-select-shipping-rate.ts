/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useThrowError } from '@woocommerce/base-hooks';
import { SelectShippingRateType } from '@woocommerce/type-defs/shipping';

/**
 * Internal dependencies
 */
import { useStoreEvents } from '../use-store-events';

/**
 * This is a custom hook for selecting shipping rates for a shipping package.
 *
 * @return {Object} This hook will return an object with these properties:
 * 		- selectShippingRate: A function that immediately returns the selected rate and dispatches an action generator.
 *		- isSelectingRate: True when rates are being resolved to the API.
 */
export const useSelectShippingRate = (): SelectShippingRateType => {
	const throwError = useThrowError();
	const { dispatchCheckoutEvent } = useStoreEvents();

	const { selectShippingRate: dispatchSelectShippingRate } = useDispatch(
		storeKey
	) as {
		selectShippingRate: unknown;
	} as {
		selectShippingRate: (
			newShippingRateId: string,
			packageId: string | number
		) => Promise< unknown >;
	};

	// Selects a shipping rate, fires an event, and catch any errors.
	const selectShippingRate = useCallback(
		( newShippingRateId, packageId ) => {
			dispatchSelectShippingRate( newShippingRateId, packageId )
				.then( () => {
					dispatchCheckoutEvent( 'set-selected-shipping-rate', {
						shippingRateId: newShippingRateId,
					} );
				} )
				.catch( ( error ) => {
					// Throw an error because an error when selecting a rate is problematic.
					throwError( error );
				} );
		},
		[ dispatchSelectShippingRate, dispatchCheckoutEvent, throwError ]
	);

	// See if rates are being selected.
	const isSelectingRate = useSelect< boolean >( ( select ) => {
		return select( storeKey ).isShippingRateBeingSelected();
	}, [] );

	return {
		selectShippingRate,
		isSelectingRate,
	};
};
