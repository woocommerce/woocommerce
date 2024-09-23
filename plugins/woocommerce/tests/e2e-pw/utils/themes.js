const { exec } = require( 'node:child_process' );
const { encodeCredentials } = require( '../utils/plugin-utils' );
const https = require( 'https' );
const { admin } = require( '../test-data/data' );
const { BASE_URL } = process.env;

export const DEFAULT_THEME = 'twentytwentythree';

export const activateTheme = ( themeName ) => {
	return new Promise( ( resolve, reject ) => {
		const isLocalhost = BASE_URL.includes( 'localhost' );
		if ( isLocalhost ) {
			// Command for local environment
			const command = `wp-env run tests-cli wp theme install ${ themeName } --activate`;

			exec( command, ( error, stdout ) => {
				if ( error ) {
					console.error( `Error executing command: ${ error }` );
					return reject( error );
				}
				resolve( stdout );
			} );
		} else {
			// HTTPS request for external environment
			const url = new URL( BASE_URL );
			const options = {
				hostname: url.hostname,
				port: url.port || 443,
				path: '/wp-json/e2e-theme/activate',
				method: 'POST',
				headers: {
					Authorization: `Basic ${ encodeCredentials(
						admin.username,
						admin.password
					) }`,
					'Content-Type': 'application/json',
				},
			};

			const req = https.request( options, ( res ) => {
				let data = '';

				res.on( 'data', ( chunk ) => {
					data += chunk;
				} );

				res.on( 'end', () => {
					resolve( JSON.parse( data ).message );
				} );
			} );

			req.on( 'error', ( error ) => {
				reject( error );
			} );

			// Send the request body
			req.write( JSON.stringify( { theme_name: themeName } ) );
			req.end();
		}
	} );
};
