/**
 * Get cookie value by name
 *
 * @param name - Cookie name
 * @return string | null
 */
export function getCookie( name: string ): string | null {
	const value = `; ${ document.cookie }`;
	const parts = value.split( `; ${ name }=` );
	if ( parts.length === 2 ) {
		return parts.pop()?.split( ';' ).shift() || null;
	}
	return null;
}

/**
 * Generate a random token
 *
 * @param randomBytesLength - Length of the random bytes
 * @return string
 */
export function generateRandomToken( randomBytesLength: number ): string {
	let randomBytes: Uint8Array | number[];

	if ( window.crypto && window.crypto.getRandomValues ) {
		randomBytes = new Uint8Array( randomBytesLength );
		window.crypto.getRandomValues( randomBytes );
	} else {
		for ( let i = 0; i < randomBytesLength; ++i ) {
			randomBytes[ i ] = Math.floor( Math.random() * 256 );
		}
	}

	return btoa( String.fromCharCode( ...randomBytes ) );
}
