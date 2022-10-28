/**
 * External dependencies
 */
import { Logger } from 'cli-core/src/logger';
import { join } from 'path';
import { cloneRepo, generateDiff } from 'cli-core/src/git';
import { readFile } from 'fs/promises';
import { execSync } from 'child_process';

/**
 * Internal dependencies
 */
import { execAsync } from '../utils';
import { scanForDBChanges } from './db-changes';
import { scanForHookChanges } from './hook-changes';
import { scanForTemplateChanges } from './template-changes';
import { SchemaDiff, generateSchemaDiff } from '../git';

export const scanForChanges = async (
	compareVersion: string,
	sinceVersion: string,
	skipSchemaCheck: boolean,
	source: string,
	base: string,
	outputStyle: string
) => {
	Logger.startTask( `Making temporary clone of ${ source }...` );
	const tmpRepoPath = await cloneRepo( source );
	Logger.endTask();

	Logger.notice(
		`Temporary clone of ${ source } created at ${ tmpRepoPath }`
	);

	const diff = await generateDiff(
		tmpRepoPath,
		base,
		compareVersion,
		Logger.error
	);

	// Only checkout the compare version if we're in CLI mode.
	if ( outputStyle === 'cli' ) {
		execSync( `cd ${ tmpRepoPath } && git checkout ${ compareVersion }`, {
			stdio: 'pipe',
		} );
	}

	const pluginPath = join( tmpRepoPath, 'plugins/woocommerce' );

	Logger.startTask( 'Detecting hook changes...' );
	const hookChanges = scanForHookChanges( diff, sinceVersion, tmpRepoPath );
	Logger.endTask();

	Logger.startTask( 'Detecting template changes...' );
	const templateChanges = scanForTemplateChanges( diff, sinceVersion );
	Logger.endTask();

	Logger.startTask( 'Detecting DB changes...' );
	const dbChanges = scanForDBChanges( diff );
	Logger.endTask();

	let schemaChanges: SchemaDiff[] = [];

	if ( ! skipSchemaCheck ) {
		const build = async () => {
			const fileStr = await readFile(
				join( pluginPath, 'package.json' ),
				'utf-8'
			);
			const packageJSON = JSON.parse( fileStr );

			if ( packageJSON.engines && packageJSON.engines.pnpm ) {
				await execAsync(
					`npm i -g pnpm@${ packageJSON.engines.pnpm }`,
					{
						cwd: pluginPath,
					}
				);
			}

			// Note doing the minimal work to get a DB scan to work, avoiding full build for speed.
			await execAsync( 'composer install', { cwd: pluginPath } );
			await execAsync(
				'pnpm run --filter=woocommerce build:feature-config',
				{
					cwd: pluginPath,
				}
			);
		};

		Logger.startTask( 'Generating schema diff...' );

		const schemaDiff = await generateSchemaDiff(
			tmpRepoPath,
			compareVersion,
			base,
			build,
			Logger.error
		);

		schemaChanges = schemaDiff || [];

		Logger.endTask();
	}

	return {
		hooks: hookChanges,
		templates: templateChanges,
		schema: schemaChanges,
		db: dbChanges,
	};
};
