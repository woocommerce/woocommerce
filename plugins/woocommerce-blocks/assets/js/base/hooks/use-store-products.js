/**
 * External dependencies
 */
import { COLLECTIONS_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useShallowEqual } from './use-shallow-equal';

const DEFAULT_OPTIONS = {
	namespace: '/wc/blocks',
	modelName: 'products',
};

/**
 * This is a custom hook that is wired up to the `wc/store/collections` data
 * store. Given a query object, this will ensure a component is kept up to date
 * with the products matching that query in the store state.
 *
 * @param {Object} query   An object containing any query arguments to be
 *                         included with the collection request for the
 *                         products. Does not have to be included.
 * @param {Object} options An optional object for adjusting the namespace and
 *                         modelName for the products query.
 *
 * @return {Object} This hook will return an object with three properties:
 *                  - products        An array of product objects.
 *                  - totalProducts   The total number of products that match the
 *                                    given query parameters.
 *                  - productsLoading A boolean indicating whether the products
 *                                    are still loading or not.
 */
export const useStoreProducts = ( query, options = DEFAULT_OPTIONS ) => {
	const { namespace, modelName } = options;
	if ( ! namespace || ! modelName ) {
		throw new Error(
			'If you provide an options object, you must have valid values ' +
				'for the namespace and the modelName properties.'
		);
	}
	// ensure we feed the previous reference object if it's equivalent
	const currentQuery = useShallowEqual( query );
	const {
		products = [],
		totalProducts = 0,
		productsLoading = true,
	} = useSelect(
		( select ) => {
			const store = select( storeKey );
			// filter out query if it is undefined.
			const args = [ namespace, modelName, currentQuery ].filter(
				( item ) => typeof item !== undefined
			);
			return {
				products: store.getCollection( ...args ),
				totalProducts: store.getCollectionHeader(
					'x-wp-total',
					...args
				),
				productsLoading: store.hasFinishedResolution(
					'getCollection',
					args
				),
			};
		},
		[ namespace, modelName, currentQuery ]
	);
	return {
		products,
		totalProducts,
		productsLoading,
	};
};
