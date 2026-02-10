/**
 * External dependencies
 */
import type { Cart } from '@woocommerce/types';

/**
 * Reads a single cookie value by name.
 *
 * Uses the CookieStore API when available (Chrome 87+, Edge 87+, Safari 18.4+,
 * Firefox 140+). Falls back to parsing document.cookie with safe decoding for
 * older browsers. The fallback only decodes the requested cookie and wraps
 * decodeURIComponent in a try/catch to avoid URIError from malformed cookies
 * set by other plugins.
 */
const getCookieValue = async ( name: string ): Promise< string > => {
	if ( typeof cookieStore !== 'undefined' ) {
		const cookie = await cookieStore.get( name );
		return cookie?.value ?? '';
	}

	const entries = document.cookie.split( ';' );
	for ( const entry of entries ) {
		const eqIndex = entry.indexOf( '=' );
		if ( eqIndex === -1 ) {
			continue;
		}
		const key = entry.slice( 0, eqIndex ).trim();
		if ( key === name ) {
			const raw = entry.slice( eqIndex + 1 ).trim();
			try {
				return decodeURIComponent( raw );
			} catch {
				return raw;
			}
		}
	}
	return '';
};

const hasValidHash = async () => {
	const sessionHash = await getCookieValue( 'woocommerce_cart_hash' );
	const cachedHash = window.localStorage?.getItem( 'storeApiCartHash' ) || '';
	return cachedHash === sessionHash;
};

export const hasCartSession = async () => {
	return !! ( await getCookieValue( 'woocommerce_items_in_cart' ) );
};

export const isAddingToCart = () => {
	return !! window.location?.search?.match( /add-to-cart/ );
};

export const persistenceLayer = {
	get: async (): Promise< Cart | null > => {
		if ( ! ( await hasCartSession() ) || ! ( await hasValidHash() ) ) {
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
