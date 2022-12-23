/**
 * External dependencies
 */
import { camelCase, mapKeys } from 'lodash';
import { Cart, CartResponse } from '@woocommerce/types';

export const mapCartResponseToCart = ( responseCart: CartResponse ): Cart => {
	return mapKeys( responseCart, ( _, key ) =>
		camelCase( key )
	) as unknown as Cart;
};
