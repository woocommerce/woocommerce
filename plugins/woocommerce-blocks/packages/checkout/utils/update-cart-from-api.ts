/**
 * External dependencies
 */
import { CART_STORE_KEY } from '@woocommerce/block-data';
import { select, dispatch } from '@wordpress/data';
import type { CartResponse } from '@woocommerce/type-defs/cart-response';

/**
 * When executed, this will invalidate the getCartData selector, causing a request to be made
 * to the API. This is in place to allow extensions to signal that they have modified the cart,
 * and that it needs to be reloaded in the client.
 */
export const updateCartFromApi = (): void => {
	const { getCartData } = select( CART_STORE_KEY );
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Can't figure out why invalidateResolutionForStoreSelector isn't available
	// but it's a standard action dispatched by @wordpress/data.
	const { invalidateResolutionForStoreSelector, receiveCart } = dispatch(
		CART_STORE_KEY
	);
	invalidateResolutionForStoreSelector( 'getCartData' );
	const cartData = ( getCartData() as unknown ) as CartResponse;
	receiveCart( cartData );
};
