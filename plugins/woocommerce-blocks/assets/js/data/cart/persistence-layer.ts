/**
 * External dependencies
 */
import type { Cart } from '@woocommerce/types';

export const persistenceLayer = {
	get: () => {
		const cached = window.localStorage?.getItem( 'CART_DATA' );
		if ( ! cached ) {
			return {};
		}
		const parsed = JSON.parse( cached );
		if (
			! parsed ||
			typeof parsed !== 'object' ||
			! Array.isArray( parsed.items )
		) {
			return {};
		}
		return parsed;
	},
	set: ( cartData: Cart ) => {
		window.localStorage.setItem( 'CART_DATA', JSON.stringify( cartData ) );
	},
};
