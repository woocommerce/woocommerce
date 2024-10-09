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

		const queryString = window.location.search;
		const urlParams = new URLSearchParams( queryString );

		// If the URL has an 'add-to-cart' parameter, return an empty object because the local storage will be stale.
		if ( urlParams.get( 'add-to-cart' ) ) {
			return {};
		}

		const parsed = JSON.parse( cached );

		if ( ! parsed || typeof parsed !== 'object' ) {
			return {};
		}

		return parsed;
	},
	set: ( cartData: Cart ) => {
		window.localStorage.setItem( 'CART_DATA', JSON.stringify( cartData ) );
	},
};
