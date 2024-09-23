const { encodeCredentials } = require( '../utils/plugin-utils' );
const https = require( 'https' );
const http = require( 'http' );
const { admin } = require( '../test-data/data' );
const { BASE_URL } = process.env;

export const DEFAULT_THEME = 'twentytwentythree';

export const activateTheme = ( themeName ) => {
	return new Promise( ( resolve, reject ) => {
		const url = new URL( BASE_URL );
		const isHttp = url.protocol === 'http:';
		const requestModule = isHttp ? http : https;

		const options = {
			hostname: url.hostname,
			port: url.port || ( isHttp ? 80 : 443 ),
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

		const req = requestModule.request( options, ( res ) => {
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

		req.write( JSON.stringify( { theme_name: themeName } ) );
		req.end();
	} );
};
