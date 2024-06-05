/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { setOutput } from '@actions/core';

/**
 * Internal dependencies
 */
import { Logger } from '../core/logger';
import { buildProjectGraph } from './lib/project-graph';
import { getFileChanges } from './lib/file-changes';
import { createJobsForChanges } from './lib/job-processing';
import { isGithubCI } from '../core/environment';

const program = new Command( 'ci-jobs' )
	.description(
		'Generates CI workflow jobs based on the changes since the base ref.'
	)
	.option(
		'-r --base-ref <baseRef>',
		'Base ref to compare the current ref against for change detection. If not specified, all projects will be considered changed.',
		''
	)
	.option(
		'-e --event <event>',
		'Github event for which to run the jobs. If not specified, all events will be considered.',
		''
	)
	.action( async ( options ) => {
		Logger.startTask( 'Parsing Project Graph', true );
		const projectGraph = buildProjectGraph();
		Logger.endTask( true );

		if ( options.event === '' ) {
			Logger.warn( 'No event was specified, considering all projects.' );
		} else {
			Logger.warn(
				`Only projects configured for '${ options.event }' event will be considered.`
			);
		}

		let fileChanges;
		if ( options.baseRef === '' ) {
			Logger.warn(
				'No base ref was specified, forcing all projects to be marked as changed.'
			);
			fileChanges = true;
		} else {
			Logger.startTask( 'Pulling File Changes', true );
			fileChanges = getFileChanges( projectGraph, options.baseRef );
			Logger.endTask( true );
		}

		Logger.startTask( 'Creating Jobs', true );
		const jobs = await createJobsForChanges( projectGraph, fileChanges, {
			commandVars: {
				baseRef: options.baseRef,
				event: options.event,
			},
		} );
		Logger.endTask( true );

		// Rename the test jobs to include the project name and test type.
		for ( const job of jobs.test ) {
			const optional = job.optional ? ' (optional)' : '';
			job.name = `${ job.name } - ${ job.projectName } [${ job.testType }]${ optional }`;
			Logger.notice( `-  ${ job.name }` );
		}

		const resultsBlobNames = jobs.test
			.map( ( job ) => {
				if ( job.report && job.report.allure ) {
					return job.report.resultsBlobName;
				}
				return undefined;
			} )
			.filter( Boolean );

		const reports = new Set( resultsBlobNames );

		if ( isGithubCI() ) {
			setOutput( 'lint-jobs', JSON.stringify( jobs.lint ) );
			setOutput( 'test-jobs', JSON.stringify( jobs.test ) );
			setOutput( 'report-jobs', JSON.stringify( reports ) );
			return;
		}

		if ( jobs.lint.length > 0 ) {
			Logger.notice( 'Lint Jobs' );
			for ( const job of jobs.lint ) {
				const optional = job.optional ? '(optional)' : '';
				Logger.notice(
					`-  ${ job.projectName } - ${ job.command }${ optional }`
				);
			}
		} else {
			Logger.notice( 'No lint jobs to run.' );
		}

		if ( jobs.test.length > 0 ) {
			Logger.notice( `Test Jobs` );
			for ( const job of jobs.test ) {
				Logger.notice( `-  ${ job.name }` );
			}
		} else {
			Logger.notice( `No test jobs to run.` );
		}

		if ( reports.size > 0 ) {
			Logger.notice( `Report Jobs` );
			for ( const job of reports ) {
				Logger.notice( JSON.stringify( job ) );
			}
		} else {
			Logger.notice( `No report jobs to run.` );
		}
	} );

export default program;
