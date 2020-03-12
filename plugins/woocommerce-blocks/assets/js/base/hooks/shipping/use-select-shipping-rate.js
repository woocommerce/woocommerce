/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * This is a custom hook for loading the selected shipping rate from the cart store and actions for selecting a rate.
See also: https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/master/src/RestApi/StoreApi
 *
 * @param {Array} shippingRates an array of packages with shipping rates.
 * @return {Object} This hook will return an object with two properties:
 * - selectShippingRate    A function that immediately returns the selected
 * rate and dispatches an action generator.
 * - selectedShippingRates An object of selected shipping rates and package id as key, maintained
 * locally by a state and updated optimistically.
 */
export const useSelectShippingRate = ( shippingRates ) => {
	const initiallySelectedRates = shippingRates
		.map(
			// the API responds with those keys.
			/* eslint-disable camelcase */
			( p ) => [
				p.package_id,
				p.shipping_rates.find( ( rate ) => rate.selected )?.rate_id,
			]
			/* eslint-enable */
		)
		// A fromEntries ponyfill, creates an object from an array of arrays.
		.reduce( ( obj, [ key, val ] ) => {
			obj[ key ] = val;
			return obj;
		}, {} );

	const [ selectedShippingRates, setSelectedShipping ] = useState(
		initiallySelectedRates
	);
	const { selectShippingRate } = useDispatch( storeKey );
	const setRate = ( newShippingRate, packageId ) => {
		setSelectedShipping( {
			...selectedShippingRates,
			[ packageId ]: newShippingRate,
		} );
		selectShippingRate( newShippingRate, packageId );
	};
	return {
		selectShippingRate: setRate,
		selectedShippingRates,
	};
};
