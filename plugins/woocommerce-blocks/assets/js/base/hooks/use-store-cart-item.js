/** @typedef { import('@woocommerce/type-defs/hooks').StoreCartItem } StoreCartItem */

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
 * This is a custom hook for loading the Store API /cart/ endpoint and
 * actions for removing or changing item quantity.
 *
 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/master/src/RestApi/StoreApi
 *
 * @param {string} cartItemKey Key for a cart item.
 * @return {StoreCartItem} An object exposing data and actions relating to cart items.
 */
export const useStoreCartItem = ( cartItemKey ) => {
	const { cartItems, cartIsLoading } = useStoreCart();
	const [ cartItem, setCartItem ] = useState( {
		key: '',
		isLoading: true,
		cartData: {},
		quantity: 0,
		isPending: false,
		changeQuantity: () => void null,
		removeItem: () => void null,
	} );
	// Store quantity in hook state. This is used to keep the UI
	// updated while server request is updated.
	const [ quantity, changeQuantity ] = useState( cartItem.quantity );
	const [ debouncedQuantity ] = useDebounce( quantity, 400 );
	const isPending = useSelect(
		( select ) => {
			const store = select( storeKey );
			return store.isItemQuantityPending( cartItemKey );
		},
		[ cartItemKey ]
	);
	useEffect( () => {
		if ( ! cartIsLoading ) {
			const foundCartItem = cartItems.find(
				( item ) => item.key === cartItemKey
			);
			if ( foundCartItem ) {
				setCartItem( foundCartItem );
			}
		}
	}, [ cartItems, cartIsLoading, cartItemKey ] );

	const { removeItemFromCart, changeCartItemQuantity } = useDispatch(
		storeKey
	);
	const removeItem = () => {
		removeItemFromCart( cartItemKey );
	};

	// Observe debounced quantity value, fire action to update server when it
	// changes.
	useEffect( () => {
		if ( debouncedQuantity === 0 ) {
			changeQuantity( cartItem.quantity );
			return;
		}
		changeCartItemQuantity( cartItemKey, debouncedQuantity );
	}, [ debouncedQuantity, cartItemKey, cartItem.quantity ] );

	return {
		isPending,
		quantity,
		changeQuantity,
		removeItem,
		isLoading: cartIsLoading,
		cartItem,
	};
};
