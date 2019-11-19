/**
 * Internal dependencies
 */
import { useCollection } from './use-collection';
import { useCollectionHeader } from './use-collection-header';

/**
 * This is a custom hook that is wired up to the `wc/store/collections` data
 * store for the `'wc/store/products'` route. Given a query object, this
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
	// @todo see @https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/1097
	// where the namespace is going to be changed. Not doing in this pull.
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
		totalProducts,
		productsLoading,
	};
};
