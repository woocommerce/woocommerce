/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';
import { CartResponse, Cart } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { receiveCart, receiveError } from './actions';
import { STORE_KEY, CART_API_ERROR } from './constants';

/**
 * Resolver for retrieving all cart data.
 */
export function* getCartData(): Generator< unknown, void, CartResponse > {
	const cartData = yield apiFetch( {
		path: '/wc/store/v1/cart',
		method: 'GET',
		cache: 'no-store',
	} );

	if ( ! cartData ) {
		yield receiveError( CART_API_ERROR );
		return;
	}

	yield receiveCart( cartData );
}

/**
 * Resolver for retrieving cart totals.
 */
export function* getCartTotals(): Generator< unknown, void, Cart > {
	yield controls.resolveSelect( STORE_KEY, 'getCartData' );
}
