/**
 * Internal dependencies
 */
import { useCollection } from './use-collection';

/**
 * This is a custom hook for loading the Store API /cart endpoint.
 * See also: https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/master/src/RestApi/StoreApi
 *
 * @return {Object} This hook will return an object with these properties:
 *                  - cartData      Cart data (see cart API for details).
 *                  - cartIsLoading True when cart data has completed loading.
 */
export const useStoreCart = () => {
	const collectionOptions = {
		namespace: '/wc/store',
		resourceName: 'cart',
	};
	const { results: cartData, isLoading } = useCollection( collectionOptions );
	return {
		cartData,
		isLoading,
	};
};
