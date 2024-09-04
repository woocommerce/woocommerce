/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { Logger } from '../../core/logger';
import { getAllProjects } from '../lib/utils';
import { buildProjectGraph } from '../../ci-jobs/lib/project-graph';
import { getFileChanges } from '../../ci-jobs/lib/file-changes';
import { isGithubCI } from '../core/environment';

export const getAffectedCommand = new Command( 'get-affected' )
	.description( 'Get a list of the affected projects to be synced.' )
	.option(
		'-r --base-ref <baseRef>',
		'Base ref to compare the current ref against for change detection. If not specified, all projects will be considered changed.',
		''
	)
	.action( async ( options ) => {
		Logger.startTask( 'Parsing Project Graph', true );
		const projectGraph = buildProjectGraph();
		Logger.endTask( true );

		let fileChanges = getAllProjects();
		if ( options.baseRef === '' ) {
			Logger.warn(
				'No base ref was specified, forcing all projects to be marked as changed.'
			);
		} else {
			Logger.startTask( 'Pulling File Changes', true );
			fileChanges = getFileChanges( projectGraph, options.baseRef );
			Logger.endTask( true );
		}

		Logger.notice( 'Affected projects:' );
		Logger.notice( JSON.stringify( fileChanges ) );
		if ( isGithubCI() ) {
			setOutput( 'projects', JSON.stringify( fileChanges ) );
		}
	} );