/**
 * External dependencies
 */
import type { Cart } from '@woocommerce/types';

const getCookie = ( name: string ): string | Record< string, string > => {
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
};

const hasValidHash = () => {
	const sessionHash = getCookie( 'woocommerce_cart_hash' );
	const cachedHash = window.localStorage?.getItem( 'storeApiCartHash' ) || '';
	return cachedHash === sessionHash;
};

export const hasCartSession = () => {
	return !! getCookie( 'woocommerce_items_in_cart' );
};

export const isAddingToCart = () => {
	return !! window.location?.search?.match( /add-to-cart/ );
};

export const persistenceLayer = {
	get: () => {
		if ( ! hasCartSession() || ! hasValidHash() ) {
			return null;
		}

		const cached = window.localStorage?.getItem( 'storeApiCartData' );

		if ( ! cached ) {
			return null;
		}

		const parsed = JSON.parse( cached );

		if ( ! parsed || typeof parsed !== 'object' ) {
			return null;
		}

		return parsed;
	},
	set: ( cartData: Cart ) => {
		window.localStorage.setItem(
			'storeApiCartData',
			JSON.stringify( cartData )
		);
	},
};
