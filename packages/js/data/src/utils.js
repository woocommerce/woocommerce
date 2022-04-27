export function getResourceName( prefix, identifier ) {
	const identifierString = JSON.stringify(
		identifier,
		Object.keys( identifier ).sort()
	);
	return `${ prefix }:${ identifierString }`;
}

export function getResourcePrefix( resourceName ) {
	const hasPrefixIndex = resourceName.indexOf( ':' );
	return hasPrefixIndex < 0
		? resourceName
		: resourceName.substring( 0, hasPrefixIndex );
}

export function isResourcePrefix( resourceName, prefix ) {
	const resourcePrefix = getResourcePrefix( resourceName );
	return resourcePrefix === prefix;
}

export function getResourceIdentifier( resourceName ) {
	const identifierString = resourceName.substring(
		resourceName.indexOf( ':' ) + 1
	);
	return JSON.parse( identifierString );
}
