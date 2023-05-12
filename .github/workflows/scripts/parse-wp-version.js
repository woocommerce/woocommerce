const https = require( 'http' );

module.exports = async function parseWPVersion( wpVersion ) {
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

	return new Promise( ( resolve, reject ) => {
		// We're going to download the correct zip archive based on the version they're requesting.
		const parsedVersion = wpVersion.match( /([0-9]+)\.([0-9]+)(?:\.([0-9]+))?/ );
		if ( ! parsedVersion ) {
			throw new Error( `Invalid 'wp-version': ${ wpVersion } must be 'trunk', 'nightly', 'latest', 'X.X', or 'X.X.X'.` );
		}

		// When they've provided a specific version we can just provide that.
		if ( parsedVersion[ 3 ] !== undefined ) {
			let zipVersion = `${ parsedVersion[ 1 ] }.${ parsedVersion[ 2 ] }`;
			// .0 versions do not have a patch.
			if ( parsedVersion[ 3 ] !== '0' ) {
				zipVersion += `.${ parsedVersion[ 3 ] }`;
			}

			resolve( `https://wordpress.org/wordpress-${ zipVersion }.zip` );
		}

		const request = https.get(
			'http://api.wordpress.org/core/stable-check/1.0/',
			( response ) => {
				// Listen for the response data.
				let data = '';
				response.on('data', (chunk) => {
					data += chunk;
				});

				// Once we have the entire response we can process it.
				response.on('end', () => {
					// Parse the response and find the latest version of every minor release.
					const latestVersions = {};
					const rawVersions = JSON.parse( data );
					for ( const v in rawVersions ) {
						// Parse the version so we can find the latest.
						const matches = v.match( /([0-9]+)\.([0-9]+)(?:\.([0-9]+))?/ );
						const minor = `${ matches[1] }.${ matches[2] }`;
						const patch = matches[ 3 ] === undefined ? 0 : parseInt( matches[ 3 ] );

						// We will only be keeping the latest release of each minor.
						if ( latestVersions[ minor ] === undefined || patch > latestVersions[ minor ] ) {
							latestVersions[ minor ] = patch;
						}
					}

					let zipVersion = `${ parsedVersion[ 1 ] }.${ parsedVersion[ 2 ] }`;
					// .0 versions do not have a patch.
					if ( latestVersions[ zipVersion ] !== 0 ) {
						zipVersion += `.${ latestVersions[ zipVersion ]}`;
					}

					resolve( `https://wordpress.org/wordpress-${ zipVersion }.zip` );
				});
			},
		);

		request.on( 'error', ( error ) => {
			reject( error );
		} );
	} );
}
