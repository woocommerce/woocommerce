/**
 * External dependencies
 */
import { useDebounce } from 'use-debounce';

/**
 * Internal dependencies
 */
import { useCollection } from './use-collection';

/**
 * This is a custom hook that is wired up to the `wc/store/collections` data
 * store for the `wc/store/cart/shipping-rates` route. Given a query object, this
 * will ensure a component is kept up to date with the shipping rates matching that
 * query in the store state.
 *
 * @param {Object} query   An object containing any query arguments to be
 *                         included with the collection request for the
 *                         shipping rates. Does not have to be included.
 *
 * @return {Object} This hook will return an object with three properties:
 *                  - shippingRates        An array of shipping rate objects.
 *                  - shippingRatesLoading A boolean indicating whether the shipping
 *                                         rates are still loading or not.
 */
export const useShippingRates = ( query ) => {
	const [ debouncedQuery ] = useDebounce( query, 300 );

	const {
		results: shippingRates,
		isLoading: shippingRatesLoading,
	} = useCollection( {
		namespace: '/wc/store',
		resourceName: 'cart/shipping-rates',
		query: debouncedQuery,
	} );

	return {
		shippingRates,
		shippingRatesLoading,
	};
};
