/**
 * External dependencies
 */
import type { Query } from '@woocommerce/types/type-defs/hooks';
import type { ProductResponseItem } from '@woocommerce/types/type-defs/product-response';
/**
 * Internal dependencies
 */
import { useCollection } from './collections/use-collection';
import { useCollectionHeader } from './collections/use-collection-header';

/**
 * This is a custom hook that is wired up to the `wc/store/collections` data
 * store for the `wc/store/v1/products` route. Given a query object, this
 * will ensure a component is kept up to date with the products matching that
 * query in the store state.
 *
 * @param {Object} query An object containing any query arguments to be
 *                       included with the collection request for the
 *                       products. Does not have to be included.
 *
 * @return {Object} This hook will return an object with three properties:
 *                  - products        An array of product objects.
 *                  - totalProducts   The total number of products that match
 *                                    the given query parameters.
 *                  - productsLoading A boolean indicating whether the products
 *                                    are still loading or not.
 */
export const useStoreProducts = (
	query: Query
): {
	products: ProductResponseItem[];
	totalProducts: number;
	productsLoading: boolean;
} => {
	const collectionOptions = {
		namespace: '/wc/store/v1',
		resourceName: 'products',
	};
	const { results: products, isLoading: productsLoading } =
		useCollection< ProductResponseItem >( {
			...collectionOptions,
			query,
		} );
	const { value: totalProducts } = useCollectionHeader( 'x-wp-total', {
		...collectionOptions,
		query,
	} );
	return {
		products,
		totalProducts: parseInt( totalProducts as string, 10 ),
		productsLoading,
	};
};
