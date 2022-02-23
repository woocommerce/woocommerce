/**
 * External dependencies
 */
import { select } from '@wordpress/data-controls';
import type {
	Cart,
	CartResponse,
	CartResponseItem,
	ExtensionCartUpdateArgs,
	BillingAddressShippingAddress,
} from '@woocommerce/types';
import { camelCase, mapKeys } from 'lodash';
import type { AddToCartEventDetail } from '@woocommerce/type-defs/events';
import { BillingAddress, ShippingAddress } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ACTION_TYPES as types } from './action-types';
import { STORE_KEY as CART_STORE_KEY } from './constants';
import { apiFetchWithHeaders } from '../shared-controls';
import type { ResponseError } from '../types';
import { ReturnOrGeneratorYieldUnion } from '../mapped-types';

/**
 * Returns an action object used in updating the store with the provided items
 * retrieved from a request using the given querystring.
 *
 * This is a generic response action.
 *
 * @param  {CartResponse}      response
 */
export const receiveCart = (
	response: CartResponse
): { type: string; response: Cart } => {
	const cart = ( mapKeys( response, ( _, key ) =>
		camelCase( key )
	) as unknown ) as Cart;
	return {
		type: types.RECEIVE_CART,
		response: cart,
	};
};

/**
 * Returns an action object used in updating the store with the provided cart.
 *
 * This omits the customer addresses so that only updates to cart items and totals are received. This is useful when
 * currently editing address information to prevent it being overwritten from the server.
 *
 * This is a generic response action.
 *
 * @param  {CartResponse}      response
 */
export const receiveCartContents = (
	response: CartResponse
): { type: string; response: Partial< Cart > } => {
	const cart = ( mapKeys( response, ( _, key ) =>
		camelCase( key )
	) as unknown ) as Cart;
	const { shippingAddress, billingAddress, ...cartWithoutAddress } = cart;
	return {
		type: types.RECEIVE_CART,
		response: cartWithoutAddress,
	};
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
 * Returns an action object for updating legacy cart fragments.
 */
export const updateCartFragments = () =>
	( {
		type: types.UPDATE_LEGACY_CART_FRAGMENTS,
	} as const );

/**
 * Triggers an adding to cart event so other blocks can update accordingly.
 */
export const triggerAddingToCartEvent = () =>
	( {
		type: types.TRIGGER_ADDING_TO_CART_EVENT,
	} as const );

/**
 * Triggers an added to cart event so other blocks can update accordingly.
 */
export const triggerAddedToCartEvent = ( {
	preserveCartData,
}: AddToCartEventDetail ) =>
	( {
		type: types.TRIGGER_ADDED_TO_CART_EVENT,
		preserveCartData,
	} as const );

/**
 * POSTs to the /cart/extensions endpoint with the data supplied by the extension.
 *
 * @param {Object} args The data to be posted to the endpoint
 */
export function* applyExtensionCartUpdate(
	args: ExtensionCartUpdateArgs
): Generator< unknown, CartResponse, { response: CartResponse } > {
	try {
		const { response } = yield apiFetchWithHeaders( {
			path: '/wc/store/v1/cart/extensions',
			method: 'POST',
			data: { namespace: args.namespace, data: args.data },
			cache: 'no-store',
		} );
		yield receiveCart( response );
		yield updateCartFragments();
		return response;
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
			path: '/wc/store/v1/cart/apply-coupon',
			method: 'POST',
			data: {
				code: couponCode,
			},
			cache: 'no-store',
		} );

		yield receiveCart( response );
		yield receiveApplyingCoupon( '' );
		yield updateCartFragments();
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
			path: '/wc/store/v1/cart/remove-coupon',
			method: 'POST',
			data: {
				code: couponCode,
			},
			cache: 'no-store',
		} );

		yield receiveCart( response );
		yield receiveRemovingCoupon( '' );
		yield updateCartFragments();
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
		yield triggerAddingToCartEvent();
		const { response } = yield apiFetchWithHeaders( {
			path: `/wc/store/v1/cart/add-item`,
			method: 'POST',
			data: {
				id: productId,
				quantity,
			},
			cache: 'no-store',
		} );

		yield receiveCart( response );
		yield triggerAddedToCartEvent( { preserveCartData: true } );
		yield updateCartFragments();
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
			path: `/wc/store/v1/cart/remove-item`,
			data: {
				key: cartItemKey,
			},
			method: 'POST',
			cache: 'no-store',
		} );

		yield receiveCart( response );
		yield updateCartFragments();
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
	if ( cartItem?.quantity === quantity ) {
		return;
	}
	yield itemIsPendingQuantity( cartItemKey );
	try {
		const { response } = yield apiFetchWithHeaders( {
			path: '/wc/store/v1/cart/update-item',
			method: 'POST',
			data: {
				key: cartItemKey,
				quantity,
			},
			cache: 'no-store',
		} );

		yield receiveCart( response );
		yield updateCartFragments();
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
			path: `/wc/store/v1/cart/select-shipping-rate`,
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

/**
 * Sets billing data locally, as opposed to updateCustomerData which sends it to the server.
 */
export const setBillingData = ( billingData: Partial< BillingAddress > ) =>
	( { type: types.SET_BILLING_DATA, billingData } as const );

/**
 * Sets shipping address locally, as opposed to updateCustomerData which sends it to the server.
 */
export const setShippingAddress = (
	shippingAddress: Partial< ShippingAddress >
) => ( { type: types.SET_SHIPPING_ADDRESS, shippingAddress } as const );

/**
 * Updates the shipping and/or billing address for the customer and returns an
 * updated cart.
 *
 * @param {BillingAddressShippingAddress} customerData Address data to be updated; can contain both
 *   billing_address and shipping_address.
 */
export function* updateCustomerData(
	customerData: Partial< BillingAddressShippingAddress >
): Generator< unknown, boolean, { response: CartResponse } > {
	yield updatingCustomerData( true );

	try {
		const { response } = yield apiFetchWithHeaders( {
			path: '/wc/store/v1/cart/update-customer',
			method: 'POST',
			data: customerData,
			cache: 'no-store',
		} );

		yield receiveCartContents( response );
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
	| typeof receiveCartContents
	| typeof setBillingData
	| typeof setShippingAddress
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
	| typeof updateCartFragments
>;
