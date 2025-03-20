/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

// Stores the current hash for the middleware.
let currentCartHash = window.localStorage.getItem( 'storeApiCartHash' );

/**
 * Updates the stored CartHash within localStorage so it is persisted between page loads.
 *
 * @param {string} cartHash Incoming hash string
 */
const updateCartHash = ( cartHash ) => {
	// If the "new" CartHash matches the current CartHash, we don't need to update.
	if ( cartHash === currentCartHash ) {
		return;
	}
	currentCartHash = cartHash;
	window.localStorage.setItem( 'storeApiCartHash', currentCartHash );
};

/**
 * Set the current CartHash from a header object.
 *
 * @param {Object} headers Headers object.
 */
const setCartHash = ( headers ) => {
	const cartHash =
		typeof headers?.get === 'function'
			? headers.get( 'Cart-Hash' )
			: headers[ 'Cart-Hash' ];

	if ( cartHash ) {
		updateCartHash( cartHash );
	}
};

apiFetch.setCartHash = setCartHash;
