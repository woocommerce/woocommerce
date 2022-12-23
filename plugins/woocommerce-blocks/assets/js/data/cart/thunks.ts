/**
 * External dependencies
 */
import { CartResponse, Cart } from '@woocommerce/types';
import { camelCase, mapKeys } from 'lodash';

/**
 * Internal dependencies
 */
import { notifyQuantityChanges } from './notify-quantity-changes';
import { CartDispatchFromMap, CartSelectFromMap } from './index';

/**
 * A thunk used in updating the store with the cart items retrieved from a request. This also notifies the shopper
 * of any unexpected quantity changes occurred.
 *
 * @param {CartResponse} response
 */
export const receiveCart =
	( response: CartResponse ) =>
	( {
		dispatch,
		select,
	}: {
		dispatch: CartDispatchFromMap;
		select: CartSelectFromMap;
	} ) => {
		const cart = mapKeys( response, ( _, key ) =>
			camelCase( key )
		) as unknown as Cart;
		notifyQuantityChanges( {
			oldCart: select.getCartData(),
			newCart: cart,
			cartItemsPendingQuantity: select.getItemsPendingQuantityUpdate(),
			cartItemsPendingDelete: select.getItemsPendingDelete(),
		} );
		dispatch.setCartData( cart );
	};
