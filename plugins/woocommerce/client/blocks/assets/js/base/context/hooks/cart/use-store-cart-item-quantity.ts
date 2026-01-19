/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback, useState, useEffect } from '@wordpress/element';
import {
	cartStore,
	checkoutStore,
	processErrorResponse,
} from '@woocommerce/block-data';
import { useDebounce } from 'use-debounce';
import { usePrevious } from '@woocommerce/base-hooks';
import {
	CartItem,
	StoreCartItemQuantity,
	isNumber,
	isObject,
	isString,
	objectHasProp,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { useStoreCart } from './use-store-cart';

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
 * @param {CartItem} cartItem The cartItem to get quantity info from and will have quantity updated on.
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
	const { key: cartItemKey = '', quantity: cartItemQuantity = 1 } =
		verifiedCartItem;
	const { cartErrors } = useStoreCart();
	const { __internalStartCalculation, __internalFinishCalculation } =
		useDispatch( checkoutStore );

	// Store quantity in hook state. This is used to keep the UI updated while server request is updated.
	const [ quantity, setQuantity ] = useState< number >( cartItemQuantity );
	const [ debouncedQuantity ] = useDebounce< number >( quantity, 400 );
	const previousDebouncedQuantity = usePrevious( debouncedQuantity );
	const { removeItemFromCart, changeCartItemQuantity } =
		useDispatch( cartStore );

	// Track when things are already pending updates.
	const isPending = useSelect(
		( select ) => {
			if ( ! cartItemKey ) {
				return {
					quantity: false,
					delete: false,
				};
			}
			const store = select( cartStore );
			return {
				quantity: store.isItemPendingQuantity( cartItemKey ),
				delete: store.isItemPendingDelete( cartItemKey ),
			};
		},
		[ cartItemKey ]
	);

	// Update local state when server updates, but only if:
	// 1. User hasn't made a change waiting to be debounced
	// 2. No API request is currently in flight
	// 3. No API call about to fire (debounce just caught up but thunk hasn't started)
	// This prevents stale API responses from overwriting user's pending changes,
	// while still allowing server-initiated changes (e.g., bundled products) to sync.
	useEffect( () => {
		const hasPendingLocalChange = quantity !== debouncedQuantity;
		const hasInflightRequest = isPending.quantity;
		// Only block if debounce JUST caught up and differs from server (about to fire API)
		// If debounce didn't change, server must have changed cartItemQuantity → allow sync
		const debouncedJustChanged =
			debouncedQuantity !== previousDebouncedQuantity;
		const aboutToFireApiCall =
			debouncedJustChanged && debouncedQuantity !== cartItemQuantity;
		if (
			! hasPendingLocalChange &&
			! hasInflightRequest &&
			! aboutToFireApiCall
		) {
			setQuantity( cartItemQuantity );
		}
	}, [
		cartItemQuantity,
		quantity,
		debouncedQuantity,
		previousDebouncedQuantity,
		isPending.quantity,
	] );

	const removeItem = useCallback( () => {
		if ( cartItemKey ) {
			return removeItemFromCart( cartItemKey ).catch( ( error ) => {
				processErrorResponse( error );
			} );
		}
		return Promise.resolve( false );
	}, [ cartItemKey, removeItemFromCart ] );

	// Observe debounced quantity value, fire action to update server on change.
	useEffect( () => {
		if (
			cartItemKey &&
			isNumber( previousDebouncedQuantity ) &&
			Number.isFinite( previousDebouncedQuantity ) &&
			previousDebouncedQuantity !== debouncedQuantity
		) {
			changeCartItemQuantity( cartItemKey, debouncedQuantity ).catch(
				( error ) => {
					processErrorResponse( error );
				}
			);
		}
	}, [
		cartItemKey,
		changeCartItemQuantity,
		debouncedQuantity,
		previousDebouncedQuantity,
	] );

	useEffect( () => {
		if ( isPending.delete ) {
			__internalStartCalculation();
		} else {
			__internalFinishCalculation();
		}
		return () => {
			if ( isPending.delete ) {
				__internalFinishCalculation();
			}
		};
	}, [
		__internalFinishCalculation,
		__internalStartCalculation,
		isPending.delete,
	] );

	useEffect( () => {
		if ( isPending.quantity || debouncedQuantity !== quantity ) {
			__internalStartCalculation();
		} else {
			__internalFinishCalculation();
		}
		return () => {
			if ( isPending.quantity || debouncedQuantity !== quantity ) {
				__internalFinishCalculation();
			}
		};
	}, [
		__internalStartCalculation,
		__internalFinishCalculation,
		isPending.quantity,
		debouncedQuantity,
		quantity,
	] );

	return {
		isPendingDelete: isPending.delete,
		quantity,
		setItemQuantity: setQuantity,
		removeItem,
		cartItemQuantityErrors: cartErrors,
	};
};
