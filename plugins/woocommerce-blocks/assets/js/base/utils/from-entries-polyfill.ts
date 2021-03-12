/**
 * A polyfill for Object.fromEntries function.
 *
 * @param {Array<[string, unknown]>} array Array to be turned back to object
 * @return {Record< string, unknown >} the newly created object
 */
export const fromEntriesPolyfill = (
	array: Array< [ string, unknown ] >
): Record< string, unknown > =>
	array.reduce< Record< string, unknown > >( ( obj, [ key, val ] ) => {
		obj[ key ] = val;
		return obj;
	}, {} );
