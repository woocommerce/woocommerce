/**
 * External dependencies
 */
import {
	Cart,
	CartResponse,
	ApiErrorResponse,
	isApiErrorResponse,
} from '@woocommerce/types';
import { camelCaseKeys } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { notifyQuantityChanges } from './notify-quantity-changes';
import { notifyCartErrors } from './notify-errors';
import { CartDispatchFromMap, CartSelectFromMap } from './index';

/**
 * A thunk used in updating the store with the cart items retrieved from a request. This also notifies the shopper
 * of any unexpected quantity changes occurred.
 *
 * @param {CartResponse} response
 */
export const receiveCart =
	( response: Partial< CartResponse > ) =>
	( {
		dispatch,
		select,
	}: {
		dispatch: CartDispatchFromMap;
		select: CartSelectFromMap;
	} ) => {
		const newCart = camelCaseKeys( response ) as unknown as Cart;
		const oldCart = select.getCartData();
		notifyCartErrors( newCart.errors, oldCart.errors );
		notifyQuantityChanges( {
			oldCart,
			newCart,
			cartItemsPendingQuantity: select.getItemsPendingQuantityUpdate(),
			cartItemsPendingDelete: select.getItemsPendingDelete(),
		} );
		dispatch.setCartData( newCart );
		dispatch.setErrorData( null );
	};

/**
 * Updates the store with the provided cart but omits the customer addresses.
 *
 * This is useful when currently editing address information to prevent it being overwritten from the server.
 *
 * @param {CartResponse} response
 */
export const receiveCartContents =
	( response: Partial< CartResponse > ) =>
	( { dispatch }: { dispatch: CartDispatchFromMap } ) => {
		// eslint-disable-next-line @typescript-eslint/naming-convention
		const { shipping_address, billing_address, ...cartWithoutAddress } =
			response;
		dispatch.receiveCart( cartWithoutAddress );
	};

/**
 * A thunk used in updating the store with cart errors retrieved from a request.
 */
export const receiveError =
	( response: ApiErrorResponse | null = null ) =>
	( { dispatch }: { dispatch: CartDispatchFromMap } ) => {
		if ( ! isApiErrorResponse( response ) ) {
			return;
		}
		if ( response.data?.cart ) {
			dispatch.receiveCart( response?.data?.cart );
		}
		dispatch.setErrorData( response );
	};

export type Thunks =
	| typeof receiveCart
	| typeof receiveCartContents
	| typeof receiveError;
