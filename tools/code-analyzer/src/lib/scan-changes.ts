/**
 * External dependencies
 */
import { Logger } from 'cli-core/src/logger';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { cloneRepo, generateDiff, generateSchemaDiff } from '../git';
import { execAsync } from '../utils';
import { scanForDBChanges } from './db-changes';
import { scanForHookChanges } from './hook-changes';
import { scanForSchemaChanges } from './schema-changes';
import { scanForTemplateChanges } from './template-changes';

export const scanForChanges = async (
	compareVersion: string,
	sinceVersion: string,
	skipSchemaCheck: boolean,
	source: string,
	base: string
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

	const pluginPath = join( tmpRepoPath, 'plugins/woocommerce' );

	Logger.startTask( 'Detecting hook changes...' );
	const hookChanges = scanForHookChanges( diff, sinceVersion );
	Logger.endTask();

	Logger.startTask( 'Detecting template changes...' );
	const templateChanges = scanForTemplateChanges( diff, sinceVersion );
	Logger.endTask();

	Logger.startTask( 'Detecting DB changes...' );
	const dbChanges = scanForDBChanges( diff );
	Logger.endTask();

	let schemaChanges: Record< string, string > = {};

	if ( ! skipSchemaCheck ) {
		const build = async () => {
			// Note doing the minimal work to get a DB scan to work, avoiding full build for speed.
			await execAsync( 'composer install', { cwd: pluginPath } );
			await execAsync(
				'pnpm run build:feature-config --filter=woocommerce',
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

		schemaChanges = schemaDiff ? scanForSchemaChanges( schemaDiff ) : {};

		Logger.endTask();
	}

	return {
		hooks: hookChanges,
		templates: templateChanges,
		schema: schemaChanges,
		db: dbChanges,
	};
};
