/** @format */
/**
 * External dependencies
 */
import { isObject } from 'lodash';

export function getResourceName( prefix, identifier ) {
	const keyList = [];
	Object.keys( identifier ).forEach( key => {
		keyList.push( key );

		// whitelist nested object keys
		if ( isObject( identifier[ key ] ) ) {
			Array.prototype.push.apply( keyList, Object.keys( identifier[ key ] ) );
		}
	} );

	const identifierString = JSON.stringify( identifier, keyList.sort() );
	return `${ prefix }:${ identifierString }`;
}

export function isResourcePrefix( resourceName, prefix ) {
	const resourcePrefix = resourceName.substring( 0, resourceName.indexOf( ':' ) );
	return resourcePrefix === prefix;
}

export function getResourceIdentifier( resourceName ) {
	const identifierString = resourceName.substring( resourceName.indexOf( ':' ) + 1 );
	return JSON.parse( identifierString );
}
