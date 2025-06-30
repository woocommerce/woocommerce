/* eslint-disable no-console */

/**
 * External dependencies
 */
import { runCLI } from '@wp-playground/cli';
import { createReadStream, unlinkSync } from 'fs';
import { resolve } from 'path';
import * as unzipper from 'unzipper';

/**
 * Internal dependencies
 */

export default async function () {
	const snapshotPath = resolve( __dirname, './playground/tmp/wordpress.zip' );
	const snapshotDir = resolve( __dirname, './playground/tmp/' );
	try {
		await runCLI( {
			command: 'build-snapshot',
			outfile: snapshotPath,
			blueprint: resolve( __dirname, './playground/blueprint.json' ),
			port: 9401,
			quiet: true,
		} );
	} catch ( error ) {
		// runCLI exits with a error that needs to be fixed in Playground
		// Error: process.exit unexpectedly called with "0"
	}

	// extract the snapshot zip
	await createReadStream( snapshotPath )
		.pipe( unzipper.Extract( { path: snapshotDir } ) )
		.promise();

	unlinkSync(
		resolve( snapshotDir, 'wordpres/wp-content/plugins/woocommerce' )
	);

	// // Create the wp-config.php file
	// copyFileSync(
	// 	join( snapshotDir, 'wordpress', 'wp-config-sample.php' ),
	// 	wpConfigPath
	// );

	// remove the zip file
	unlinkSync( snapshotPath );
}
