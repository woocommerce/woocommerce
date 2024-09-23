/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { setOutput } from '@actions/core';
import { writeFileSync } from 'fs';

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
	.option( '--json', 'Save the jobs in a json file.' )
	.option( '--list', 'List jobs in table format console.' )
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

		const reports = [ ...new Set( resultsBlobNames ) ];

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

		if ( reports.length > 0 ) {
			Logger.notice( `Report Jobs` );
			Logger.notice( `${ reports }` );
		} else {
			Logger.notice( `No report jobs to run.` );
		}

		if ( options.list ) {
			Object.keys( jobs ).forEach( ( key ) => {
				const job = jobs[ key ].map(
					( { name, projectName, optional } ) => ( {
						name: `${ key } - ${
							key === 'lint' ? projectName : name
						}`,
						optional,
					} )
				);
				// eslint-disable-next-line no-console
				console.table( job );
			} );
		}

		if ( options.json ) {
			Logger.notice( 'Saving jobs to json file.' );

			Object.keys( jobs ).forEach( ( key ) => {
				jobs[ key ] = jobs[ key ].map(
					( {
						name,
						projectName,
						projectPath,
						testType,
						optional,
					} ) => ( {
						name,
						projectName,
						projectPath,
						testType,
						optional,
					} )
				);
			} );

			writeFileSync(
				'jobs.json',
				JSON.stringify(
					{
						baseRef: options.baseRef,
						event: options.event,
						...jobs,
					},
					null,
					2
				)
			);
		}
	} );

export default program;
