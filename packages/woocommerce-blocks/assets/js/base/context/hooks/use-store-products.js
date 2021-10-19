/**
 * Internal dependencies
 */
import { useCollectionHeader, useCollection } from './collections';

/**
 * This is a custom hook that is wired up to the `wc/store/collections` data
 * store for the `wc/store/products` route. Given a query object, this
 * will ensure a component is kept up to date with the products matching that
 * query in the store state.
 *
 * @param {Object} query   An object containing any query arguments to be
 *                         included with the collection request for the
 *                         products. Does not have to be included.
 *
 * @return {Object} This hook will return an object with three properties:
 *                  - products        An array of product objects.
 *                  - totalProducts   The total number of products that match
 *                                    the given query parameters.
 *                  - productsLoading A boolean indicating whether the products
 *                                    are still loading or not.
 */
export const useStoreProducts = ( query ) => {
	const collectionOptions = {
		namespace: '/wc/store',
		resourceName: 'products',
	};
	const { results: products, isLoading: productsLoading } = useCollection( {
		...collectionOptions,
		query,
	} );
	const { value: totalProducts } = useCollectionHeader( 'x-wp-total', {
		...collectionOptions,
		query,
	} );
	return {
		products,
		totalProducts: parseInt( totalProducts, 10 ),
		productsLoading,
	};
};
