/**
 * External dependencies
 */
import type { Cart } from '@woocommerce/types';

function getCookie( name: string ): string | Record< string, string > {
	const cookies = document.cookie
		.split( ';' )
		.reduce< Record< string, string > >( ( acc, cookieString ) => {
			const [ key, value ] = cookieString
				.split( '=' )
				.map( ( s ) => s.trim() );
			if ( key && value ) {
				acc[ key ] = decodeURIComponent( value );
			}
			return acc;
		}, {} );
	return name ? cookies[ name ] || '' : cookies;
}

export const hasCartSession = () => {
	return !! getCookie( 'woocommerce_items_in_cart' );
};

export const isAddingToCart = () => {
	return !! window.location.search.match( /add-to-cart/ );
};

export const persistenceLayer = {
	get: () => {
		if ( ! hasCartSession() || isAddingToCart() ) {
			return {};
		}

		const cached = window.localStorage?.getItem( 'CART_DATA' );

		if ( ! cached ) {
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
