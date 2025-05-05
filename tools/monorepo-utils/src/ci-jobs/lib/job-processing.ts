/**
 * Internal dependencies
 */
import { Logger } from '../../core/logger';
import {
	CommandVarOptions,
	JobType,
	LintJobConfig,
	TestJobConfig,
} from './config';
import { ProjectFileChanges } from './file-changes';
import { ProjectNode } from './project-graph';
import { TestEnvVars, parseTestEnvConfig } from './test-environment';

/**
 * A linting job.
 */
interface LintJob {
	projectName: string;
	projectPath: string;
	command: string;
	optional: boolean;
}

/**
 * A testing job environment.
 */
interface TestJobEnv {
	shouldCreate: boolean;
	envVars: TestEnvVars;
	start?: string;
}

/**
 * A testing job report.
 */
interface TestJobReport {
	resultsBlobName: string;
	resultsPath: string;
	allure: boolean;
}

/**
 * A testing job.
 */
interface TestJob {
	projectName: string;
	projectPath: string;
	name: string;
	command: string;
	testEnv: TestJobEnv;
	shardNumber: number;
	optional: boolean;
	testType: string;
	report: TestJobReport;
}

/**
 * All of the jobs that should be run.
 */
interface Jobs {
	lint: LintJob[];
	test: TestJob[];
}

/**
 * The options to be used when creating jobs.
 */
export interface CreateOptions {
	commandVars?: { [ key in CommandVarOptions ]: string };
}

/**
 * Replaces any variable tokens in the command with their value.
 *
 * @param {string} command The command to process.
 * @param {Object} options The options to use when creating the job.
 * @return {string} The command after token replacements.
 */
function replaceCommandVars( command: string, options: CreateOptions ): string {
	return command.replace( /<([^>]+)>/g, ( _match, key ) => {
		if ( options.commandVars?.[ key ] === undefined ) {
			throw new Error( `Missing command variable '${ key }'.` );
		}

		return options.commandVars[ key ];
	} );
}

/**
 * Multiplies a job based on the shards job config. It updates the job names and command - currently only supporting Playwright sharding.
 *
 * @param {TestJob}       job       The job to be multiplied.
 * @param {TestJobConfig} jobConfig The job config.
 * @return {TestJob[]} The list of sharded jobs.
 */
export function getShardedJobs(
	job: TestJob,
	jobConfig: TestJobConfig
): TestJob[] {
	let createdJobs = [];
	const shards = jobConfig.shardingArguments.length;

	if ( shards <= 1 ) {
		createdJobs.push( job );
	} else {
		createdJobs = Array( shards )
			.fill( null )
			.map( ( _, i ) => {
				const jobCopy = JSON.parse( JSON.stringify( job ) );
				jobCopy.shardNumber = i + 1;
				jobCopy.name = `${ job.name } ${ i + 1 }/${ shards }`;
				jobCopy.command = `${ job.command } ${ jobConfig.shardingArguments[ i ] }`;
				return jobCopy;
			} );
	}

	return createdJobs;
}

/**
 * Checks the config against the changes and creates one if it should be run.
 *
 * @param {string}              projectName The name of the project that the job is for.
 * @param {string}              projectPath The path of the project that the job is for.
 * @param {Object}              config      The config object for the lint job.
 * @param {Array.<string>|true} changes     The file changes that have occurred for the project or true if all projects should be marked as changed.
 * @param {Object}              options     The options to use when creating the job.
 * @return {Object|null} The job that should be run or null if no job should be run.
 */
function createLintJob(
	projectName: string,
	projectPath: string,
	config: LintJobConfig,
	changes: string[] | true,
	options: CreateOptions
): LintJob | null {
	let triggered = false;

	// When we're forcing changes for all projects we don't need to check
	// for any changed files before triggering the job.
	if ( changes === true ) {
		triggered = true;
	} else {
		// Projects can configure jobs to be triggered when a
		// changed file matches a path regex.
		for ( const file of changes ) {
			for ( const change of config.changes ) {
				if ( change.test( file ) ) {
					triggered = true;
					break;
				}
			}

			if ( triggered ) {
				break;
			}
		}
	}

	if ( ! triggered ) {
		return null;
	}

	return {
		projectName,
		projectPath,
		command: replaceCommandVars( config.command, options ),
		optional: config.optional,
	};
}

/**
 * Checks the config against the changes and creates one if it should be run.
 *
 * @param {string}              projectName The name of the project that the job is for.
 * @param {string}              projectPath The path of the project that the job is for.
 * @param {Object}              config      The config object for the test job.
 * @param {Array.<string>|true} changes     The file changes that have occurred for the project or true if all projects should be marked as changed.
 * @param {Object}              options     The options to use when creating the job.
 * @param {number}              shardNumber The shard number for the job.
 * @return {Promise.<Object|null>} The job that should be run or null if no job should be run.
 */
async function createTestJob(
	projectName: string,
	projectPath: string,
	config: TestJobConfig,
	changes: string[] | true,
	options: CreateOptions,
	shardNumber: number
): Promise< TestJob | null > {
	let triggered = false;
	// When we're forcing changes for all projects we don't need to check
	// for any changed files before triggering the job.
	if ( changes === true ) {
		triggered = true;
	} else {
		// Projects can configure jobs to be triggered when a
		// changed file matches a path regex.
		for ( const file of changes ) {
			for ( const change of config.changes ) {
				if ( change.test( file ) ) {
					triggered = true;
					break;
				}
			}

			if ( triggered ) {
				break;
			}
		}
	}

	if ( ! triggered ) {
		return null;
	}

	const createdJob: TestJob = {
		projectName,
		projectPath,
		name: config.name,
		command: replaceCommandVars( config.command, options ),
		testEnv: {
			shouldCreate: false,
			envVars: {},
		},
		report: config.report,
		shardNumber,
		optional: config.optional,
		testType: config.testType,
	};

	// We want to make sure that we're including the configuration for
	// any test environment that the job will need in order to run.
	if ( config.testEnv ) {
		createdJob.testEnv = {
			shouldCreate: true,
			envVars: await parseTestEnvConfig( config.testEnv.config ),
			start: replaceCommandVars( config.testEnv.start, options ),
		};
	}

	// Pre-release versions (beta, RC) are not always available, and we should not create the job if that's the case.
	if (
		[ 'beta', 'rc', 'prerelease', 'pre-release' ].includes(
			config?.testEnv?.config?.wpVersion
		) &&
		! createdJob.testEnv.envVars.WP_VERSION
	) {
		Logger.warn(
			`No WP offer was found for config.wpVersion:${ config.testEnv.config.wpVersion }. Job was not created.`
		);
		return null;
	}

	if ( createdJob.testEnv.envVars.WP_VERSION ) {
		createdJob.name += ` [WP ${ createdJob.testEnv.envVars.WP_VERSION }]`;
	}

	return createdJob;
}

/**
 * Recursively checks the project for any jobs that should be executed and returns them.
 *
 * @param {Object}      node    The current project node to examine.
 * @param {Object|true} changes The changed files keyed by their project or true if all projects should be marked as changed.
 * @param {Object}      options The options to use when creating the job.
 * @return {Promise.<Object>} The jobs that have been created for the project.
 */
async function createJobsForProject(
	node: ProjectNode,
	changes: ProjectFileChanges | true,
	options: CreateOptions
): Promise< Jobs > {
	// We're going to traverse the project graph and check each node for any jobs that should be triggered.
	const newJobs: Jobs = {
		lint: [],
		test: [],
	};

	const dependenciesWithChanges = [];

	for ( const dependency of node.dependencies ) {
		const dependencyJobs = await createJobsForProject(
			dependency,
			changes,
			options
		);

		newJobs.lint.push( ...dependencyJobs.lint );
		newJobs.test.push( ...dependencyJobs.test );

		// First line of detection: implicit changes list points to the dependency.
		const dependencyHasChanges =
			( changes[ dependency.name ] || [] ).length > 0;
		// Second line of detection: the dependency spawns jobs.
		const dependencySpawnsJobs =
			dependencyJobs.test.length + dependencyJobs.lint.length > 0;

		if ( dependencyHasChanges || dependencySpawnsJobs ) {
			dependenciesWithChanges.push( dependency.name );
		}
	}

	// Projects that don't have any CI configuration don't have any potential jobs for us to check for.
	if ( ! node.ciConfig ) {
		return newJobs;
	}

	for ( const jobConfig of node.ciConfig.jobs ) {
		// Make sure that we don't queue the same job more than once.
		if ( jobConfig.jobCreated ) {
			continue;
		}

		// Do not create a job if:
		// - there is an event argument in ci-job cli,
		// - a non-empty list of events is defined in the job config,
		// - the event argument is not in the defined list of events.
		if (
			options.commandVars?.event &&
			jobConfig.events.length > 0 &&
			! jobConfig.events
				.map( ( e ) => e.toLowerCase() )
				.includes( options.commandVars.event.toLowerCase() )
		) {
			continue;
		}

		// Jobs will check to see whether or not they should trigger based on the files
		// that have been changed in the project. When "true" is given, however, it
		// means that we should consider ALL files to have been changed and
		// trigger any jobs for the project.
		let projectChanges;
		if ( changes === true ) {
			projectChanges = true;
		} else {
			projectChanges = changes[ node.name ] ?? [];
		}

		switch ( jobConfig.type ) {
			case JobType.Lint: {
				const created = createLintJob(
					node.name,
					node.path,
					jobConfig,
					projectChanges,
					options
				);
				if ( ! created ) {
					break;
				}

				jobConfig.jobCreated = true;
				newJobs.lint.push( created );
				break;
			}
			case JobType.Test: {
				if ( dependenciesWithChanges.length > 0 ) {
					if ( ! jobConfig.onlyForDependencies ) {
						// onlyForDependencies is not defined, meaning there are no exceptions so we should run the job.
						projectChanges = true;
					}

					// If onlyForDependencies is defined, we should only run the job if one of the listed dependencies has changes.
					if (
						jobConfig.onlyForDependencies &&
						jobConfig.onlyForDependencies.some( ( dep ) =>
							dependenciesWithChanges.includes( dep )
						)
					) {
						projectChanges = true;
					}
				}

				const created = await createTestJob(
					node.name,
					node.path,
					jobConfig,
					projectChanges,
					options,
					0
				);
				if ( ! created ) {
					break;
				}

				jobConfig.jobCreated = true;

				newJobs.test.push( ...getShardedJobs( created, jobConfig ) );
				break;
			}
		}
	}

	return newJobs;
}

/**
 * Creates jobs to run for the given project graph and file changes.
 *
 * @param {Object}      root    The root node for the project graph.
 * @param {Object|true} changes The changed files keyed by their project or true if all projects should be marked as changed.
 * @param {Object}      options The options to use when creating the job.
 * @return {Promise.<Object>} The jobs that should be run.
 */
export function createJobsForChanges(
	root: ProjectNode,
	changes: ProjectFileChanges | true,
	options: CreateOptions
): Promise< Jobs > {
	return createJobsForProject( root, changes, options );
}
