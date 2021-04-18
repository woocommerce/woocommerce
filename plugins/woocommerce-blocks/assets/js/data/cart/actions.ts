/**
 * External dependencies
 */
import { select } from '@wordpress/data-controls';
import type {
	Cart,
	CartResponse,
	CartResponseItem,
	CartBillingAddress,
	CartShippingAddress,
} from '@woocommerce/types';
import { ReturnOrGeneratorYieldUnion } from '@automattic/data-stores';
import { camelCase, mapKeys } from 'lodash';

/**
 * Internal dependencies
 */
import { ACTION_TYPES as types } from './action-types';
import { STORE_KEY as CART_STORE_KEY } from './constants';
import { apiFetchWithHeaders } from '../shared-controls';
import type { ResponseError } from '../types';

/**
 * Returns an action object used in updating the store with the provided items
 * retrieved from a request using the given querystring.
 *
 * This is a generic response action.
 *
 * @param  {CartResponse}      response
 */
export const receiveCart = ( response: CartResponse ) => {
	const cart = ( mapKeys( response, ( _, key ) =>
		camelCase( key )
	) as unknown ) as Cart;
	return {
		type: types.RECEIVE_CART,
		response: cart,
	} as const;
};

/**
 * Returns an action object used for receiving customer facing errors from the API.
 *
 * @param   {ResponseError|null} [error=null]     An error object containing the error
 *                                         message and response code.
 * @param   {boolean}       [replace=true] Should existing errors be replaced,
 *                                         or should the error be appended.
 */
export const receiveError = (
	error: ResponseError | null = null,
	replace = true
) =>
	( {
		type: replace ? types.REPLACE_ERRORS : types.RECEIVE_ERROR,
		error,
	} as const );

/**
 * Returns an action object used to track when a coupon is applying.
 *
 * @param  {string} [couponCode] Coupon being added.
 */
export const receiveApplyingCoupon = ( couponCode: string ) =>
	( {
		type: types.APPLYING_COUPON,
		couponCode,
	} as const );

/**
 * Returns an action object used to track when a coupon is removing.
 *
 * @param   {string} [couponCode] Coupon being removed..
 */
export const receiveRemovingCoupon = ( couponCode: string ) =>
	( {
		type: types.REMOVING_COUPON,
		couponCode,
	} as const );

/**
 * Returns an action object for updating a single cart item in the store.
 *
 * @param  {CartResponseItem} [response=null] A cart item API response.
 */
export const receiveCartItem = ( response: CartResponseItem | null = null ) =>
	( {
		type: types.RECEIVE_CART_ITEM,
		cartItem: response,
	} as const );

/**
 * Returns an action object to indicate if the specified cart item quantity is
 * being updated.
 *
 * @param   {string}  cartItemKey              Cart item being updated.
 * @param   {boolean} [isPendingQuantity=true] Flag for update state; true if API
 *                                             request is pending.
 */
export const itemIsPendingQuantity = (
	cartItemKey: string,
	isPendingQuantity = true
) =>
	( {
		type: types.ITEM_PENDING_QUANTITY,
		cartItemKey,
		isPendingQuantity,
	} as const );

/**
 * Returns an action object to remove a cart item from the store.
 *
 * @param   {string}  cartItemKey            Cart item to remove.
 * @param   {boolean} [isPendingDelete=true] Flag for update state; true if API
 *                                           request is pending.
 */
export const itemIsPendingDelete = (
	cartItemKey: string,
	isPendingDelete = true
) =>
	( {
		type: types.RECEIVE_REMOVED_ITEM,
		cartItemKey,
		isPendingDelete,
	} as const );
/**
 * Returns an action object to mark the cart data in the store as stale.
 *
 * @param   {boolean} [isCartDataStale=true] Flag to mark cart data as stale; true if
 * 											 lastCartUpdate timestamp is newer than the
 * 											 one in wcSettings.
 */
export const setIsCartDataStale = ( isCartDataStale = true ) =>
	( {
		type: types.SET_IS_CART_DATA_STALE,
		isCartDataStale,
	} as const );

/**
 * Returns an action object used to track when customer data is being updated
 * (billing and/or shipping).
 */
export const updatingCustomerData = ( isResolving: boolean ) =>
	( {
		type: types.UPDATING_CUSTOMER_DATA,
		isResolving,
	} as const );

/**
 * Returns an action object used to track whether the shipping rate is being
 * selected or not.
 *
 * @param  {boolean} isResolving True if shipping rate is being selected.
 */
export const shippingRatesBeingSelected = ( isResolving: boolean ) =>
	( {
		type: types.UPDATING_SELECTED_SHIPPING_RATE,
		isResolving,
	} as const );

/**
 * Applies a coupon code and either invalidates caches, or receives an error if
 * the coupon cannot be applied.
 *
 * @param  {string} couponCode The coupon code to apply to the cart.
 * @throws            Will throw an error if there is an API problem.
 */
export function* applyCoupon(
	couponCode: string
): Generator< unknown, boolean, { response: CartResponse } > {
	yield receiveApplyingCoupon( couponCode );

	try {
		const { response } = yield apiFetchWithHeaders( {
			path: '/wc/store/cart/apply-coupon',
			method: 'POST',
			data: {
				code: couponCode,
			},
			cache: 'no-store',
		} );

		yield receiveCart( response );
		yield receiveApplyingCoupon( '' );
	} catch ( error ) {
		yield receiveError( error );
		yield receiveApplyingCoupon( '' );

		// If updated cart state was returned, also update that.
		if ( error.data?.cart ) {
			yield receiveCart( error.data.cart );
		}

		// Re-throw the error.
		throw error;
	}

	return true;
}

/**
 * Removes a coupon code and either invalidates caches, or receives an error if
 * the coupon cannot be removed.
 *
 * @param  {string} couponCode The coupon code to remove from the cart.
 * @throws            Will throw an error if there is an API problem.
 */
export function* removeCoupon(
	couponCode: string
): Generator< unknown, boolean, { response: CartResponse } > {
	yield receiveRemovingCoupon( couponCode );

	try {
		const { response } = yield apiFetchWithHeaders( {
			path: '/wc/store/cart/remove-coupon',
			method: 'POST',
			data: {
				code: couponCode,
			},
			cache: 'no-store',
		} );

		yield receiveCart( response );
		yield receiveRemovingCoupon( '' );
	} catch ( error ) {
		yield receiveError( error );
		yield receiveRemovingCoupon( '' );

		// If updated cart state was returned, also update that.
		if ( error.data?.cart ) {
			yield receiveCart( error.data.cart );
		}

		// Re-throw the error.
		throw error;
	}

	return true;
}

/**
 * Adds an item to the cart:
 * - Calls API to add item.
 * - If successful, yields action to add item from store.
 * - If error, yields action to store error.
 *
 * @param  {number} productId    Product ID to add to cart.
 * @param  {number} [quantity=1] Number of product ID being added to cart.
 * @throws           Will throw an error if there is an API problem.
 */
export function* addItemToCart(
	productId: number,
	quantity = 1
): Generator< unknown, void, { response: CartResponse } > {
	try {
		const { response } = yield apiFetchWithHeaders( {
			path: `/wc/store/cart/add-item`,
			method: 'POST',
			data: {
				id: productId,
				quantity,
			},
			cache: 'no-store',
		} );

		yield receiveCart( response );
	} catch ( error ) {
		yield receiveError( error );

		// If updated cart state was returned, also update that.
		if ( error.data?.cart ) {
			yield receiveCart( error.data.cart );
		}

		// Re-throw the error.
		throw error;
	}
}

/**
 * Removes specified item from the cart:
 * - Calls API to remove item.
 * - If successful, yields action to remove item from store.
 * - If error, yields action to store error.
 * - Sets cart item as pending while API request is in progress.
 *
 * @param {string} cartItemKey Cart item being updated.
 */
export function* removeItemFromCart(
	cartItemKey: string
): Generator< unknown, void, { response: CartResponse } > {
	yield itemIsPendingDelete( cartItemKey );

	try {
		const { response } = yield apiFetchWithHeaders( {
			path: `/wc/store/cart/remove-item/?key=${ cartItemKey }`,
			method: 'POST',
			cache: 'no-store',
		} );

		yield receiveCart( response );
	} catch ( error ) {
		yield receiveError( error );

		// If updated cart state was returned, also update that.
		if ( error.data?.cart ) {
			yield receiveCart( error.data.cart );
		}
	}
	yield itemIsPendingDelete( cartItemKey, false );
}

/**
 * Persists a quantity change the for specified cart item:
 * - Calls API to set quantity.
 * - If successful, yields action to update store.
 * - If error, yields action to store error.
 *
 * @param {string} cartItemKey Cart item being updated.
 * @param {number} quantity    Specified (new) quantity.
 */
export function* changeCartItemQuantity(
	cartItemKey: string,
	quantity: number
	// eslint-disable-next-line @typescript-eslint/no-explicit-any -- unclear how to represent multiple different yields as type
): Generator< unknown, void, any > {
	const cartItem = yield select( CART_STORE_KEY, 'getCartItem', cartItemKey );
	yield itemIsPendingQuantity( cartItemKey );

	if ( cartItem?.quantity === quantity ) {
		return;
	}
	try {
		const { response } = yield apiFetchWithHeaders( {
			path: '/wc/store/cart/update-item',
			method: 'POST',
			data: {
				key: cartItemKey,
				quantity,
			},
			cache: 'no-store',
		} );

		yield receiveCart( response );
	} catch ( error ) {
		yield receiveError( error );

		// If updated cart state was returned, also update that.
		if ( error.data?.cart ) {
			yield receiveCart( error.data.cart );
		}
	}
	yield itemIsPendingQuantity( cartItemKey, false );
}

/**
 * Selects a shipping rate.
 *
 * @param {string}          rateId      The id of the rate being selected.
 * @param {number | string} [packageId] The key of the packages that we will
 *   select within.
 */
export function* selectShippingRate(
	rateId: string,
	packageId = 0
): Generator< unknown, boolean, { response: CartResponse } > {
	try {
		yield shippingRatesBeingSelected( true );
		const { response } = yield apiFetchWithHeaders( {
			path: `/wc/store/cart/select-shipping-rate`,
			method: 'POST',
			data: {
				package_id: packageId,
				rate_id: rateId,
			},
			cache: 'no-store',
		} );

		yield receiveCart( response );
	} catch ( error ) {
		yield receiveError( error );
		yield shippingRatesBeingSelected( false );

		// If updated cart state was returned, also update that.
		if ( error.data?.cart ) {
			yield receiveCart( error.data.cart );
		}

		// Re-throw the error.
		throw error;
	}
	yield shippingRatesBeingSelected( false );
	return true;
}

type BillingAddressShippingAddress = {
	// eslint-disable-next-line camelcase
	billing_address: CartBillingAddress;
	// eslint-disable-next-line camelcase
	shipping_address: CartShippingAddress;
};

/**
 * Updates the shipping and/or billing address for the customer and returns an
 * updated cart.
 *
 * @param {BillingAddressShippingAddress} customerData Address data to be updated; can contain both
 *   billing_address and shipping_address.
 */
export function* updateCustomerData(
	customerData: BillingAddressShippingAddress
): Generator< unknown, boolean, { response: CartResponse } > {
	yield updatingCustomerData( true );

	try {
		const { response } = yield apiFetchWithHeaders( {
			path: '/wc/store/cart/update-customer',
			method: 'POST',
			data: customerData,
			cache: 'no-store',
		} );

		yield receiveCart( response );
	} catch ( error ) {
		yield receiveError( error );
		yield updatingCustomerData( false );

		// If updated cart state was returned, also update that.
		if ( error.data?.cart ) {
			yield receiveCart( error.data.cart );
		}

		// rethrow error.
		throw error;
	}

	yield updatingCustomerData( false );
	return true;
}

export type CartAction = ReturnOrGeneratorYieldUnion<
	| typeof receiveCart
	| typeof receiveError
	| typeof receiveApplyingCoupon
	| typeof receiveRemovingCoupon
	| typeof receiveCartItem
	| typeof itemIsPendingQuantity
	| typeof itemIsPendingDelete
	| typeof updatingCustomerData
	| typeof shippingRatesBeingSelected
	| typeof setIsCartDataStale
	| typeof updateCustomerData
	| typeof removeItemFromCart
	| typeof changeCartItemQuantity
	| typeof addItemToCart
>;
