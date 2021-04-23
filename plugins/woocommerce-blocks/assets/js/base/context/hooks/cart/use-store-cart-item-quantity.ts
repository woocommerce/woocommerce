/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useDebounce } from 'use-debounce';
import { usePrevious } from '@woocommerce/base-hooks';
import { triggerFragmentRefresh } from '@woocommerce/base-utils';
import type { CartItem, StoreCartItemQuantity } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { useStoreCart } from './use-store-cart';
import { useCheckoutContext } from '../../providers/cart-checkout';
import {
	isNumber,
	isObject,
	isString,
	objectHasProp,
} from '../../../utils/type-guards';

/**
 * Ensures the object passed has props key: string and quantity: number
 */
const cartItemHasQuantityAndKey = (
	cartItem: unknown /* Object that may have quantity and key */
): cartItem is Partial< CartItem > =>
	isObject( cartItem ) &&
	objectHasProp( cartItem, 'key' ) &&
	objectHasProp( cartItem, 'quantity' ) &&
	isString( cartItem.key ) &&
	isNumber( cartItem.quantity );

/**
 * This is a custom hook for loading the Store API /cart/ endpoint and actions for removing or changing item quantity.
 *
 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/src/RestApi/StoreApi
 *
 * @param {CartItem} cartItem      The cartItem to get quantity info from and will have quantity updated on.
 * @return {StoreCartItemQuantity} An object exposing data and actions relating to cart items.
 */
export const useStoreCartItemQuantity = (
	cartItem: CartItem | Record< string, unknown >
): StoreCartItemQuantity => {
	const verifiedCartItem = { key: '', quantity: 1 };

	if ( cartItemHasQuantityAndKey( cartItem ) ) {
		verifiedCartItem.key = cartItem.key;
		verifiedCartItem.quantity = cartItem.quantity;
	}
	const {
		key: cartItemKey = '',
		quantity: cartItemQuantity = 1,
	} = verifiedCartItem;
	const { cartErrors } = useStoreCart();
	const { dispatchActions } = useCheckoutContext();

	// Store quantity in hook state. This is used to keep the UI updated while server request is updated.
	const [ quantity, setQuantity ] = useState< number >( cartItemQuantity );
	const [ debouncedQuantity ] = useDebounce< number >( quantity, 400 );
	const previousDebouncedQuantity = usePrevious( debouncedQuantity );
	const { removeItemFromCart, changeCartItemQuantity } = useDispatch(
		storeKey
	);

	// Track when things are already pending updates.
	const isPending = useSelect(
		( select ) => {
			if ( ! cartItemKey ) {
				return {
					quantity: false,
					delete: false,
				};
			}
			const store = select( storeKey );
			return {
				quantity: store.isItemPendingQuantity( cartItemKey ),
				delete: store.isItemPendingDelete( cartItemKey ),
			};
		},
		[ cartItemKey ]
	);
	const previousIsPending = usePrevious( isPending );

	const removeItem = () => {
		return cartItemKey
			? removeItemFromCart( cartItemKey ).then( () => {
					triggerFragmentRefresh();
					return true;
			  } )
			: Promise.resolve( false );
	};

	// Observe debounced quantity value, fire action to update server on change.
	useEffect( () => {
		if (
			cartItemKey &&
			isNumber( previousDebouncedQuantity ) &&
			Number.isFinite( previousDebouncedQuantity ) &&
			previousDebouncedQuantity !== debouncedQuantity
		) {
			changeCartItemQuantity( cartItemKey, debouncedQuantity );
		}
	}, [
		cartItemKey,
		changeCartItemQuantity,
		debouncedQuantity,
		previousDebouncedQuantity,
	] );

	useEffect( () => {
		if ( typeof previousIsPending === 'undefined' ) {
			return;
		}
		if ( previousIsPending.quantity !== isPending.quantity ) {
			if ( isPending.quantity ) {
				dispatchActions.incrementCalculating();
			} else {
				dispatchActions.decrementCalculating();
			}
		}
		if ( previousIsPending.delete !== isPending.delete ) {
			if ( isPending.delete ) {
				dispatchActions.incrementCalculating();
			} else {
				dispatchActions.decrementCalculating();
			}
		}
		return () => {
			if ( isPending.quantity ) {
				dispatchActions.decrementCalculating();
			}
			if ( isPending.delete ) {
				dispatchActions.decrementCalculating();
			}
		};
	}, [ dispatchActions, isPending, previousIsPending ] );

	return {
		isPendingDelete: isPending.delete,
		quantity,
		setItemQuantity: setQuantity,
		removeItem,
		cartItemQuantityErrors: cartErrors,
	};
};
