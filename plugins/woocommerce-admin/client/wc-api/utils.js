export function getResourceName( prefix, identifier ) {
	const identifierString = JSON.stringify(
		identifier,
		Object.keys( identifier ).sort()
	);
	return `${ prefix }:${ identifierString }`;
}

export function getResourcePrefix( resourceName ) {
	return resourceName.substring( 0, resourceName.indexOf( ':' ) );
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
