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
		// Wrap in try/catch for two reasons:
		//
		// 1. In Jest, when a jsdom `Window` is torn down between tests while
		//    a subscribe callback is still draining through wp-data's
		//    middleware, reading `window.localStorage` throws inside the
		//    jsdom getter (it dereferences `this._document._origin` on a
		//    null `_document`). This crash kills the entire worker and
		//    cascades to misattributed failures in unrelated suites.
		//
		// 2. In production browsers, `localStorage.setItem` can throw —
		//    private-mode Safari, exceeded quota, disabled storage. The
		//    previous code would propagate that into the cart reducer and
		//    crash the app; losing cart persistence for one session is the
		//    right failure mode.
		try {
			window.localStorage?.setItem(
				'storeApiCartData',
				JSON.stringify( cartData )
			);
		} catch {
			// Intentionally empty — persistence is best-effort.
		}
	},
};
