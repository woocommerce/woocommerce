/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { Logger } from '../core/logger';
import { buildProjectGraph } from './lib/project-graph';
import { getFileChanges } from './lib/file-changes';
import { createJobsForChanges } from './lib/job-processing';

const program = new Command( 'ci-jobs' )
	.description(
		'Generates CI workflow jobs based on the changes since the base ref.'
	)
	.argument(
		'<base-ref>',
		'Base ref to compare the current ref against for change detection.'
	)
	.action( async ( baseRef: string ) => {
		const projectGraph = buildProjectGraph();
		const fileChanges = getFileChanges( projectGraph, baseRef );
		const jobs = createJobsForChanges( projectGraph, fileChanges );
		Logger.notice( JSON.stringify( jobs, null, '\\t' ) );
	} );

export default program;
