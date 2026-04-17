/**
 * External dependencies
 */
import { request } from '@playwright/test';
import fs from 'fs';
import path from 'path';

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data';

class EnvironmentReporter {
	reportOptions: { outputFolder?: string };

	constructor( options: { outputFolder?: string } ) {
		this.reportOptions = options;
	}

	async onEnd() {
		console.log( '::debug::Getting environment details' );
		const { outputFolder } = this.reportOptions;

		if ( ! outputFolder ) {
			console.error(
				'Error getting environment info: no output folder specified!'
			);
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

			const info = await wpApi.get( `./wp-json/e2e-environment/info` );

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

export default EnvironmentReporter;
