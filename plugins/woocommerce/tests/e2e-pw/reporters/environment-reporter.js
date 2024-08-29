require( '@playwright/test/reporter' );
const { request } = require( '@playwright/test' );
const fs = require( 'fs' );
const path = require( 'path' );
const { admin } = require( '../test-data/data' );

class EnvironmentReporter {
	constructor( options ) {
		this.reportOptions = options;
	}

	async onEnd() {
		console.log( 'Getting environment details' );
		const { outputFolder } = this.reportOptions;

		if ( ! outputFolder ) {
			console.log( 'No output folder specified!' );
			return;
		}

		const { BASE_URL, CI } = process.env;
		let environmentData = '';

		if ( CI ) {
			environmentData += `CI=${ CI }`;
		}

		try {
			const wpApi = await request.newContext( {
				baseURL: BASE_URL,
				extraHTTPHeaders: {
					Authorization: `Basic ${ Buffer.from(
						`${ admin.username }:${ admin.password }`
					).toString( 'base64' ) }`,
				},
			} );

			const info = await wpApi.get( `/wp-json/e2e-environment/info` );

			if ( info.ok() ) {
				const data = await info.json();
				for ( const [ key, value ] of Object.entries( data ) ) {
					// We need to format the values to be compatible with the Java properties file format
					environmentData += `\n${ key
						.replace( / /g, '\\u0020' )
						.replace( /:/g, '-' ) }=${ value }`;
				}
			}
		} catch ( err ) {
			console.error( `Error getting environment info: ${ err }` );
		}

		const filePath = path.resolve( outputFolder, 'environment.properties' );

		try {
			fs.writeFileSync( filePath, environmentData );
		} catch ( err ) {
			console.error( `Error writing environment.properties: ${ err }` );
		}
	}
}

module.exports = EnvironmentReporter;
