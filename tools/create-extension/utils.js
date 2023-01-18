function sortDependencies( dependencies ) {
	return Object.keys( dependencies )
		.sort()
		.reduce( ( obj, key ) => {
			obj[ key ] = dependencies[ key ];
			return obj;
		}, {} );
}

function mergePackageJsonDependencies(
	originalPackageJson,
	additionalPackageJson
) {
	const parsedOriginal = JSON.parse( originalPackageJson );
	const parsedAdditional = JSON.parse( additionalPackageJson );
	if ( parsedAdditional[ 'dependencies' ] ) {
		parsedOriginal[ 'dependencies' ] = sortDependencies( {
			...parsedOriginal[ 'dependencies' ],
			...parsedAdditional[ 'dependencies' ],
		} );
	}

	if ( parsedAdditional[ 'devDependencies' ] ) {
		parsedOriginal[ 'devDependencies' ] = sortDependencies( {
			...parsedOriginal[ 'devDependencies' ],
			...parsedAdditional[ 'devDependencies' ],
		} );
	}
	return JSON.stringify( parsedOriginal, null, '\t' );
}

module.exports = {
	mergePackageJsonDependencies,
};
