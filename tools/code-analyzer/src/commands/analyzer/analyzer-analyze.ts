/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { Logger } from 'cli-core/src/logger';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { generateJSONFile } from '../../utils';
import { scanForChanges } from '../../lib/scan-changes';
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
		const { fileName, skipSchemaCheck = false, source, base } = options;

		const changes = await scanForChanges(
			compare,
			sinceVersion,
			skipSchemaCheck,
			source,
			base
		);

		await generateJSONFile( join( process.cwd(), fileName ), changes );

		Logger.notice( `Generated changes file at ${ fileName }` );
	} );

program.parse( process.argv );
