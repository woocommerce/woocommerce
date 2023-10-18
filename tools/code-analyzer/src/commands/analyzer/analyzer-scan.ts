/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { Logger } from '@woocommerce/monorepo-utils/src/core/logger';
import { join } from 'path';

/**
 * Internal dependencies
 */
import {
	scanChangesForDB,
	scanChangesForHooks,
	scanChangesForTemplates,
	ScanType,
} from '../../lib/scan-changes';
import {
	printDatabaseUpdates,
	printHookResults,
	printTemplateResults,
} from '../../print';

const printEmptyNotice = ( scanType: ScanType ) => {
	Logger.notice( `\n\n##  ${ scanType.toUpperCase() } CHANGES` );
	Logger.notice( '---------------------------------------------------' );
	Logger.notice( `No ${ scanType } changes found.` );
	Logger.notice( '---------------------------------------------------' );
};

const program = new Command()
	.command( 'scan' )
	.argument(
		'<type>',
		'Type of change to scan for. Options: templates, hooks, database.'
	)
	.argument(
		'<compare>',
		'GitHub branch/tag/commit hash to compare against the base branch/tag/commit hash.'
	)
	.argument(
		'[base]',
		'Base branch to compare against. Defaults to trunk.',
		'trunk'
	)
	.option(
		'-s, --since <sinceVersion>',
		'Specify the version used to determine which changes are included (version listed in @since code doc). Only needed for hook and template changes.'
	)
	.option(
		'-src, --source <source>',
		'Git repo url or local path to a git repo.',
		join( process.cwd(), '../../' )
	)
	.option(
		'-o, --outputStyle <outputStyle>',
		'Output style for the results. Options: github, cli. Github output will set the results as an output variable for Github actions.',
		'cli'
	)
	.action( async ( scanType, compare, base, options ) => {
		const { since: sinceVersion, source, outputStyle } = options;

		if (
			( scanType === 'hooks' || scanType === 'templates' ) &&
			! sinceVersion
		) {
			Logger.error(
				`To scan for ${ scanType } changes you must provide the since argument.`
			);
		}

		switch ( scanType ) {
			case 'hooks':
				// We know sinceVersion will exist but TS can't infer that here.
				if ( sinceVersion ) {
					const hookChanges = await scanChangesForHooks(
						compare,
						sinceVersion,
						base,
						source
					);

					if ( hookChanges.length ) {
						printHookResults(
							hookChanges,
							outputStyle,
							'HOOKS',
							Logger.notice
						);
					} else {
						printEmptyNotice( 'hooks' );
					}
				}
				break;
			case 'templates':
				// We know sinceVersion will exist but TS can't infer that here.
				if ( sinceVersion ) {
					const templateChanges = await scanChangesForTemplates(
						compare,
						sinceVersion,
						base,
						source
					);
					if ( templateChanges && templateChanges.length ) {
						printTemplateResults(
							templateChanges,
							outputStyle,
							'TEMPLATES',
							Logger.notice
						);
					} else {
						printEmptyNotice( 'templates' );
					}
				}
				break;
			case 'database':
				const dbChanges = await scanChangesForDB(
					compare,
					base,
					source
				);
				if ( dbChanges ) {
					printDatabaseUpdates( dbChanges, 'cli', Logger.notice );
				} else {
					printEmptyNotice( 'database' );
				}
				break;
			default:
				Logger.error(
					`Invalid scan type: ${ scanType } Options: templates, hooks, database.`
				);
		}
	} );

program.parse( process.argv );
