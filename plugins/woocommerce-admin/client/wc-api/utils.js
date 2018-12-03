/** @format */

export function getResourceName( prefix, identifier ) {
	const identifierString = JSON.stringify( identifier, Object.keys( identifier ).sort() );
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
