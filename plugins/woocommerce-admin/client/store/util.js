/** @format */

/**
 * Returns a string representation of a sorted query object.
 *
 * @param {Object} query Current state
 * @return {String}       Query Key
 */

export function getJsonString( query = {} ) {
	return JSON.stringify( query, Object.keys( query ).sort() );
}
