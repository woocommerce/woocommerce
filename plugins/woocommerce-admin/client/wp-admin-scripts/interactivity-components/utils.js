/**
 * Get the property name from an input name.
 *
 * @param {string} inputName
 * @return {string} Property name,
 */
export function getPropertyNameFromInput( inputName ) {
	if ( inputName.indexOf( '_' ) === 0 ) {
		return inputName.slice( 1 );
	}

	return inputName;
}

/**
 * Get the product ID.
 *
 * @return {number} Product ID.
 */
export function getProductId() {
	const urlParams = new URLSearchParams( window.location.search );
	return Number( urlParams.get( 'id' ) );
}
