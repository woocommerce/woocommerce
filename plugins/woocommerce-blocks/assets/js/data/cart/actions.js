/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { ACTION_TYPES as types } from './action-types';

/**
 * Returns an action object used in updating the store with the provided items
 * retrieved from a request using the given querystring.
 *
 * This is a generic response action.
 *
 * @param {Object}   [response={}]    An object containing the response from the
 *                                    request.
 * @return {Object} Object for action.
 */
export function receiveCart( response = {} ) {
	return {
		type: types.RECEIVE_CART,
		response,
	};
}

/**
 * Returns an action object used for receiving customer facing errors from the
 * API.
 *
 * @param {Object}  [error={}]     An error object containing the error message
 *                                 and response code.
 * @param {boolean} [replace=true] Should existing errors be replaced, or should
 *                                 the error be appended.
 * @return {Object} Object for action.
 */
export function receiveError( error = {}, replace = true ) {
	return {
		type: replace ? types.REPLACE_ERRORS : types.RECEIVE_ERROR,
		error,
	};
}

/**
 * Returns an action object used to track when a coupon is applying.
 *
 * @param {string} [couponCode] Coupon being added.
 * @return {Object} Object for action.
 */
export function receiveApplyingCoupon( couponCode ) {
	return {
		type: types.APPLYING_COUPON,
		couponCode,
	};
}

/**
 * Returns an action object used to track when a coupon is removing.
 *
 * @param {string} [couponCode] Coupon being removed.
 * @return {Object} Object for action.
 */
export function receiveRemovingCoupon( couponCode ) {
	return {
		type: types.REMOVING_COUPON,
		couponCode,
	};
}

/**
 * Applies a coupon code and either invalidates caches, or receives an error if
 * the coupon cannot be applied.
 *
 * @param {string} couponCode The coupon code to apply to the cart.
 */
export function* applyCoupon( couponCode ) {
	yield receiveApplyingCoupon( couponCode );

	try {
		const result = yield apiFetch( {
			path: '/wc/store/cart/apply-coupon',
			method: 'POST',
			data: {
				code: couponCode,
			},
			cache: 'no-store',
		} );

		if ( result ) {
			yield receiveCart( result );
		}
	} catch ( error ) {
		yield receiveError( error );
	}

	yield receiveApplyingCoupon( '' );
}

/**
 * Removes a coupon code and either invalidates caches, or receives an error if
 * the coupon cannot be removed.
 *
 * @param {string} couponCode The coupon code to remove from the cart.
 */
export function* removeCoupon( couponCode ) {
	yield receiveRemovingCoupon( couponCode );

	try {
		const result = yield apiFetch( {
			path: '/wc/store/cart/remove-coupon',
			method: 'POST',
			data: {
				code: couponCode,
			},
			cache: 'no-store',
		} );

		if ( result ) {
			yield receiveCart( result );
		}
	} catch ( error ) {
		yield receiveError( error );
	}

	yield receiveRemovingCoupon( '' );
}
