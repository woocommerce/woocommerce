/** @typedef { import('@woocommerce/type-defs/hooks').StoreCartItems } StoreCartItems */

/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useStoreCart } from './use-store-cart';

/**
 * This is a custom hook for loading the Store API /cart/ endpoint and
 * actions for removing or changing item quantity.
 * See also: https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/master/src/RestApi/StoreApi
 *
 * @param {string} cartItemKey Key for a cart item.
 * @return {StoreCartItems} An object exposing data and actions relating to cart items.
 */
export const useStoreCartItem = ( cartItemKey ) => {
	const { cartItems, cartIsLoading } = useStoreCart();
	const cartItem = cartItems.filter( ( item ) => item.key === cartItemKey );

	const results = useSelect(
		( select, { dispatch } ) => {
			const store = select( storeKey );
			const isPending = store.isItemQuantityPending( cartItemKey );
			const { removeItemFromCart } = dispatch( storeKey );

			return {
				isPending,
				removeItem: () => {
					removeItemFromCart( cartItemKey );
				},
			};
		},
		[ cartItemKey ]
	);

	return {
		isLoading: cartIsLoading,
		cartItem,
		...results,
	};
};
