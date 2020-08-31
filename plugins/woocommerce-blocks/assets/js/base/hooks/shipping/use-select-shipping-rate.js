/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useThrowError } from '@woocommerce/base-hooks';

/**
 * This is a custom hook for loading the selected shipping rate from the cart store and actions for selecting a rate.
See also: https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/main/src/RestApi/StoreApi
 *
 * @param {Array} shippingRates an array of packages with shipping rates.
 * @return {Object} This hook will return an object with two properties:
 * - selectShippingRate    A function that immediately returns the selected
 * rate and dispatches an action generator.
 * - selectedShippingRates An object of selected shipping rates and package id as key, maintained
 * locally by a state and updated optimistically, this will only return packages that has selected
 * shipping rates.
 *
 */
export const useSelectShippingRate = ( shippingRates ) => {
	const throwError = useThrowError();
	const derivedSelectedRates = useMemo(
		() =>
			shippingRates
				.map(
					// the API responds with those keys.
					/* eslint-disable camelcase */
					( p ) => [
						p.package_id,
						p.shipping_rates.find( ( rate ) => rate.selected )
							?.rate_id,
					]
					/* eslint-enable */
				)
				// A fromEntries ponyfill, creates an object from an array of arrays.
				.reduce( ( obj, [ key, val ] ) => {
					if ( val ) {
						obj[ key ] = val;
					}
					return obj;
				}, {} ),
		[ shippingRates ]
	);

	const [ selectedShippingRates, setSelectedShipping ] = useState(
		derivedSelectedRates
	);

	// useState initial state is always remembered, so even if that value changes,
	// useState won't update, we're forcing it to update if the initial value changes,
	// useMemo helps us not running this function when we don't need to.
	useEffect( () => {
		setSelectedShipping( derivedSelectedRates );
	}, [ derivedSelectedRates ] );
	const { selectShippingRate } = useDispatch( storeKey );
	const isSelectingRate = useSelect( ( select ) => {
		return select( storeKey ).isShippingRateBeingSelected();
	}, [] );
	const setRate = ( newShippingRate, packageId ) => {
		setSelectedShipping( {
			...selectedShippingRates,
			[ packageId ]: newShippingRate,
		} );
		selectShippingRate( newShippingRate, packageId ).catch( ( error ) => {
			// we throw this error because an error on selecting a rate
			// is problematic.
			throwError( error );
		} );
	};
	return {
		selectShippingRate: setRate,
		selectedShippingRates,
		isSelectingRate,
	};
};
