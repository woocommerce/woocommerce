module.exports = function parseWPVersion( wpVersion ) {
    	// Start with versions we can infer immediately.
	switch ( wpVersion ) {
        case 'master':
		case 'trunk': {
			return 'WordPress/WordPress#master';
		}

		case 'nightly': {
			return 'https://wordpress.org/nightly-builds/wordpress-latest.zip';
		}

		case 'latest': {
			return 'https://wordpress.org/latest.zip';
		}
	}

	// Set the correct branch based on the version request being made.
	const matches = wpVersion.match( /([0-9]+)\.([0-9]+)(?:\.([0-9]+))?/ );
	if ( ! matches ) {
		throw new Error( `Invalid 'wp-version': ${ wpVersion } must be 'trunk', 'nightly', 'latest', 'X.X', or 'X.X.X'.` );
	}

	// They've requested a very specific version and we should respect that.
	if ( matches[ 3 ] !== undefined ) {
		// .0 versions are tagged without the patch.
		if ( matches[ 3 ] === '0' ) {
			return `WordPress/WordPress#tags/${ matches[ 1 ] }.${ matches[ 2 ] }.${ matches[ 3 ] }`;
		}

		return `WordPress/WordPress#tags/${ matches[ 1 ] }.${ matches[ 2 ] }.${ matches[ 3 ] }`;
	}

	// We can give them the batest release from a given development branch.
	return `WordPress/WordPress#${ matches[ 1 ] }.${ matches[ 2 ] }-branch`;
}
