/**
 * External dependencies
 */
import { camelCase, mapKeys } from 'lodash';
import { Cart } from '@woocommerce/type-defs/cart';
import { CartResponse } from '@woocommerce/type-defs/cart-response';

export const mapCartResponseToCart = ( responseCart: CartResponse ): Cart => {
	return mapKeys( responseCart, ( _, key ) =>
		camelCase( key )
	) as unknown as Cart;
};
