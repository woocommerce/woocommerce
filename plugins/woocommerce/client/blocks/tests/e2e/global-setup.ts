/* eslint-disable no-console */

/**
 * External dependencies
 */
import { runCLI } from '@wp-playground/cli';
import {
	createReadStream,
	unlinkSync,
	readFileSync,
	mkdirSync,
	existsSync,
	rmSync,
} from 'fs';
import { resolve } from 'path';
import * as unzipper from 'unzipper';

/**
 * Internal dependencies
 */

export default async function () {
	const snapshotDir = resolve( __dirname, './playground/tmp/' );
	const snapshotPath = resolve( __dirname, './playground/tmp/wordpress.zip' );
	const blueprint = JSON.parse(
		readFileSync(
			resolve( __dirname, './playground/blueprint.json' ),
			'utf8'
		)
	);

	if ( ! existsSync( snapshotDir ) ) mkdirSync( snapshotDir );

	if ( ! existsSync( snapshotPath ) )
		try {
			await runCLI( {
				command: 'build-snapshot',
				outfile: snapshotPath,
				blueprint,
				port: 9401,
			} );
		} catch ( error ) {
			// runCLI exits with a error that needs to be fixed in Playground
			// Error: process.exit unexpectedly called with "0"
		}

	// extract the snapshot zip
	if ( ! existsSync( resolve( snapshotDir, 'wordpress' ) ) )
		await createReadStream( snapshotPath )
			.pipe( unzipper.Extract( { path: snapshotDir } ) )
			.promise();

	if (
		existsSync(
			resolve( snapshotDir, 'wordpress/wp-content/plugins/woocommerce' )
		)
	)
		rmSync(
			resolve( snapshotDir, 'wordpress/wp-content/plugins/woocommerce' ),
			{ recursive: true, force: true }
		);

	// // Create the wp-config.php file
	// copyFileSync(
	// 	join( snapshotDir, 'wordpress', 'wp-config-sample.php' ),
	// 	wpConfigPath
	// );

	// if ( existsSync( snapshotPath ) ) unlinkSync( snapshotPath );
}
