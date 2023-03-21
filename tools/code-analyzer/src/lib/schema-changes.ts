/**
 * External dependencies
 */
import { Logger } from 'cli-core/src/logger';
import { readFile } from 'fs/promises';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { generateSchemaDiff } from '../git';
import { execAsync } from '../utils';

export const scanForSchemaChanges = async (
	compareVersion: string,
	base: string,
	tmpRepoPath: string
) => {
	const pluginPath = join( tmpRepoPath, 'plugins/woocommerce' );

	const build = async () => {
		const fileStr = await readFile(
			join( pluginPath, 'package.json' ),
			'utf-8'
		);
		const packageJSON = JSON.parse( fileStr );

		// Temporarily save the current PNPM version.
		await execAsync( `tmpgPNPM="$(pnpm --version)"` );

		if ( packageJSON.engines && packageJSON.engines.pnpm ) {
			await execAsync( `npm i -g pnpm@${ packageJSON.engines.pnpm }`, {
				cwd: pluginPath,
			} );
		}

		// Note doing the minimal work to get a DB scan to work, avoiding full build for speed.
		await execAsync( 'composer install', { cwd: pluginPath } );
		await execAsync( 'pnpm run --filter=woocommerce build:feature-config', {
			cwd: pluginPath,
		} );
	};

	Logger.startTask( 'Generating schema diff...' );

	const schemaDiff = await generateSchemaDiff(
		tmpRepoPath,
		compareVersion,
		base,
		build,
		Logger.error
	);

	// Restore the previously saved PNPM version
	await execAsync( `npm i -g pnpm@"$tmpgPNPM"` );

	Logger.endTask();

	return schemaDiff;
};
