/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useDebounce } from 'use-debounce';
import { useCheckoutContext } from '@woocommerce/base-context';
import { triggerFragmentRefresh } from '@woocommerce/base-utils';
import type { CartItem, StoreCartItemQuantity } from '@woocommerce/types';
/**
 * Internal dependencies
 */
import { useStoreCart } from './use-store-cart';
import { usePrevious } from '../use-previous';
/**
 * This is a custom hook for loading the Store API /cart/ endpoint and
 * actions for removing or changing item quantity.
 *
 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/src/RestApi/StoreApi
 *
 * @param {CartItem} cartItem      The cartItem to get quantity info from and
 *                                 will have quantity updated on.
 * @return {StoreCartItemQuantity} An object exposing data and actions relating
 *                                 to cart items.
 */
export const useStoreCartItemQuantity = (
	cartItem: CartItem
): StoreCartItemQuantity => {
	const { key: cartItemKey = '', quantity: cartItemQuantity = 1 } = cartItem;
	const { cartErrors } = useStoreCart();
	const { dispatchActions } = useCheckoutContext();

	// Store quantity in hook state. This is used to keep the UI
	// updated while server request is updated.
	const [ quantity, changeQuantity ] = useState< number >( cartItemQuantity );
	const [ debouncedQuantity ] = useDebounce< number >( quantity, 400 );
	const previousDebouncedQuantity = usePrevious( debouncedQuantity );
	const { removeItemFromCart, changeCartItemQuantity } = useDispatch(
		storeKey
	);

	const isPendingQuantity = useSelect(
		( select ) => {
			if ( ! cartItemKey ) {
				return false;
			}

			const store = select( storeKey );
			return store.isItemPendingQuantity( cartItemKey );
		},
		[ cartItemKey ]
	);
	const previousIsPendingQuantity = usePrevious( isPendingQuantity );

	const isPendingDelete = useSelect(
		( select ) => {
			if ( ! cartItemKey ) {
				return false;
			}

			const store = select( storeKey );
			return store.isItemPendingDelete( cartItemKey );
		},
		[ cartItemKey ]
	);
	const previousIsPendingDelete = usePrevious( isPendingDelete );

	const removeItem = () => {
		return cartItemKey
			? removeItemFromCart( cartItemKey ).then( () => {
					triggerFragmentRefresh();
			  } )
			: false;
	};

	// Observe debounced quantity value, fire action to update server on change.
	useEffect( () => {
		if (
			cartItemKey &&
			Number.isFinite( previousDebouncedQuantity ) &&
			previousDebouncedQuantity !== debouncedQuantity
		) {
			changeCartItemQuantity( cartItemKey, debouncedQuantity ).then(
				triggerFragmentRefresh
			);
		}
	}, [
		cartItemKey,
		changeCartItemQuantity,
		debouncedQuantity,
		previousDebouncedQuantity,
	] );

	useEffect( () => {
		if ( previousIsPendingQuantity !== isPendingQuantity ) {
			if ( isPendingQuantity ) {
				dispatchActions.incrementCalculating();
			} else {
				dispatchActions.decrementCalculating();
			}
		}
	}, [ dispatchActions, isPendingQuantity, previousIsPendingQuantity ] );

	useEffect( () => {
		if ( previousIsPendingDelete !== isPendingDelete ) {
			if ( isPendingDelete ) {
				dispatchActions.incrementCalculating();
			} else {
				dispatchActions.decrementCalculating();
			}
		}
		return () => {
			if ( isPendingDelete ) {
				dispatchActions.decrementCalculating();
			}
		};
	}, [ dispatchActions, isPendingDelete, previousIsPendingDelete ] );

	return {
		isPendingDelete,
		quantity,
		changeQuantity,
		removeItem,
		cartItemQuantityErrors: cartErrors,
	};
};
