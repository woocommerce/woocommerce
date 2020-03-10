/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useDebounce } from 'use-debounce';

/**
 * Internal dependencies
 */
import { useStoreCart } from './use-store-cart';

/**
 * @typedef {import('@woocommerce/type-defs/hooks').StoreCartItemQuantity} StoreCartItemQuantity
 * @typedef {import('@woocommerce/type-defs/cart').CartItem} CartItem
 */

/**
 * This is a custom hook for loading the Store API /cart/ endpoint and
 * actions for removing or changing item quantity.
 *
 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/master/src/RestApi/StoreApi
 *
 * @param {CartItem} cartItem      The cartItem to get quantity info from and
 *                                 will have quantity updated on.
 * @return {StoreCartItemQuantity} An object exposing data and actions relating
 *                                 to cart items.
 */
export const useStoreCartItemQuantity = ( cartItem ) => {
	const { cartErrors } = useStoreCart();
	// Store quantity in hook state. This is used to keep the UI
	// updated while server request is updated.
	const [ quantity, changeQuantity ] = useState( cartItem.quantity );
	const [ debouncedQuantity ] = useDebounce( quantity, 400 );
	const isPending = useSelect(
		( select ) => {
			const store = select( storeKey );
			return store.isItemQuantityPending( cartItem.key );
		},
		[ cartItem.key ]
	);

	const { removeItemFromCart, changeCartItemQuantity } = useDispatch(
		storeKey
	);
	const removeItem = () => {
		removeItemFromCart( cartItem.key );
	};

	// Observe debounced quantity value, fire action to update server when it
	// changes.
	useEffect( () => {
		changeCartItemQuantity( cartItem.key, debouncedQuantity );
	}, [ debouncedQuantity, cartItem.key ] );

	return {
		isPending,
		quantity,
		changeQuantity,
		removeItem,
		cartItemQuantityErrors: cartErrors,
	};
};
