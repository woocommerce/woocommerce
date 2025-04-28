/**
 * External dependencies
 */
import type {
	ApiErrorResponse,
	Cart,
	CartResponseItem,
} from '@woocommerce/types';
import { BillingAddress, ShippingAddress } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ACTION_TYPES as types } from './action-types';

// Thunks are functions that can be dispatched, similar to actions creators.
export * from './thunks';

/**
 * An action creator that dispatches the plain action responsible for setting the cart data in the store.
 */
export function setCartData( cart: Cart ) {
	return {
		type: types.SET_CART_DATA,
		response: cart,
	};
}

/**
 * An action creator that dispatches the plain action responsible for setting the cart error data in the store.
 */
export function setErrorData( error: ApiErrorResponse | null ) {
	return {
		type: types.SET_ERROR_DATA,
		error,
	};
}

/**
 * Returns an action object used to track when a coupon is applying.
 */
export function receiveApplyingCoupon( couponCode: string ) {
	return {
		type: types.APPLYING_COUPON,
		couponCode,
	};
}

/**
 * Returns an action object used to track when a coupon is removing.
 */
export function receiveRemovingCoupon( couponCode: string ) {
	return {
		type: types.REMOVING_COUPON,
		couponCode,
	};
}

/**
 * Returns an action object for updating a single cart item in the store.
 */
export function receiveCartItem( response: CartResponseItem | null = null ) {
	return {
		type: types.RECEIVE_CART_ITEM,
		cartItem: response,
	};
}

/**
 * Returns an action object to indicate if the specified cart item quantity is
 * being updated.
 *
 * @param {string}  cartItemKey              Cart item being updated.
 * @param {boolean} [isPendingQuantity=true] Flag for update state; true if API
 *                                           request is pending.
 */
export function itemIsPendingQuantity(
	cartItemKey: string,
	isPendingQuantity = true
) {
	return {
		type: types.ITEM_PENDING_QUANTITY,
		cartItemKey,
		isPendingQuantity,
	};
}

/**
 * Returns an action object to remove a cart item from the store.
 *
 * @param {string}  cartItemKey            Cart item to remove.
 * @param {boolean} [isPendingDelete=true] Flag for update state; true if API
 *                                         request is pending.
 */
export function itemIsPendingDelete(
	cartItemKey: string,
	isPendingDelete = true
) {
	return {
		type: types.RECEIVE_REMOVED_ITEM,
		cartItemKey,
		isPendingDelete,
	};
}

/**
 * Returns an action object to mark the cart data in the store as stale.
 *
 * @param {boolean} [isCartDataStale=true] Flag to mark cart data as stale; true if
 *                                         lastCartUpdate timestamp is newer than the
 *                                         one in wcSettings.
 */
export function setIsCartDataStale( isCartDataStale = true ) {
	return {
		type: types.SET_IS_CART_DATA_STALE,
		isCartDataStale,
	};
}

/**
 * Returns an action object used to track when customer data is being updated
 * (billing and/or shipping).
 */
export function updatingCustomerData( isResolving: boolean ) {
	return {
		type: types.UPDATING_CUSTOMER_DATA,
		isResolving,
	};
}

/**
 * Returns an action object used to track when customer shipping data is being updated.
 */
export function updatingAddressFieldsForShippingRates( isResolving: boolean ) {
	return {
		type: types.UPDATING_ADDRESS_FIELDS_FOR_SHIPPING_RATES,
		isResolving,
	};
}
/**
 * Returns an action object used to track whether the shipping rate is being
 * selected or not.
 *
 * @param {boolean} isResolving True if shipping rate is being selected.
 */
export function shippingRatesBeingSelected( isResolving: boolean ) {
	return {
		type: types.UPDATING_SELECTED_SHIPPING_RATE,
		isResolving,
	};
}

/**
 * Sets billing address locally, as opposed to updateCustomerData which sends it to the server.
 */
export function setBillingAddress( billingAddress: Partial< BillingAddress > ) {
	return { type: types.SET_BILLING_ADDRESS, billingAddress };
}

/**
 * Sets shipping address locally, as opposed to updateCustomerData which sends it to the server.
 */
export function setShippingAddress(
	shippingAddress: Partial< ShippingAddress >
) {
	return { type: types.SET_SHIPPING_ADDRESS, shippingAddress };
}

/**
 * Sets the metadata to show product IDs pending being added to the cart.
 */
export function setProductsPendingAdd( productId: number, isAdding: boolean ) {
	return { type: types.PRODUCT_PENDING_ADD, productId, isAdding };
}
