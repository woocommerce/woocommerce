/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { Logger } from 'cli-core/src/logger';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { cloneRepo, generateDiff, generateSchemaDiff } from '../../git';
import { execAsync, generateJSONFile } from '../../utils';
import { scanForHookChanges } from '../../lib/hook-changes';
import { scanForTemplateChanges } from '../../lib/template-changes';
import { scanForSchemaChanges } from '../../lib/schema-changes';
import { scanForDBChanges } from '../../lib/db-changes';
const program = new Command()
	.argument(
		'<compare>',
		'GitHub branch or commit hash to compare against the base branch/commit.'
	)
	.argument(
		'<sinceVersion>',
		'Specify the version used to determine which changes are included (version listed in @since code doc).'
	)
	.option(
		'-b, --base <base>',
		'GitHub base branch or commit hash.',
		'trunk'
	)
	.option(
		'-s, --source <source>',
		'Git repo url or local path to a git repo.',
		process.cwd()
	)
	.option(
		'-f, --fileName <fileName>',
		'Filename for the generated change JSON',
		'changes.json'
	)
	.option(
		'-ss, --skipSchemaCheck',
		'Skip the schema check, enable this if you are not analyzing WooCommerce'
	)
	.action( async ( compare, sinceVersion, options ) => {
		const { fileName, skipSchemaCheck, source, base } = options;

		Logger.startTask( `Making temporary clone of ${ source }...` );
		const tmpRepoPath = await cloneRepo( source );
		Logger.endTask();

		Logger.notice(
			`Temporary clone of ${ source } created at ${ tmpRepoPath }`
		);

		const diff = await generateDiff(
			tmpRepoPath,
			base,
			compare,
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
				compare,
				base,
				build,
				Logger.error
			);

			const schemaChanges = schemaDiff
				? scanForSchemaChanges( schemaDiff )
				: {};

			Logger.endTask();

			await generateJSONFile( join( process.cwd(), fileName ), {
				templates: Object.fromEntries( templateChanges.entries() ),
				hooks: Object.fromEntries( hookChanges.entries() ),
				db: dbChanges || {},
				schema: schemaChanges,
			} );

			Logger.notice( `Generated changes file at ${ fileName }` );
		}
	} );

program.parse( process.argv );
