/**
 * Internal dependencies
 */
import { JobType, LintJobConfig, TestJobConfig } from './config';
import { ProjectFileChanges } from './file-changes';
import { ProjectNode } from './project-graph';
import { TestEnvVars, parseTestEnvConfig } from './test-environment';

/**
 * A linting job.
 */
interface LintJob {
	projectName: string;
	command: string;
}

/**
 * A testing job environment.
 */
interface TestJobEnv {
	shouldCreate: boolean;
	start: string;
	envVars: TestEnvVars;
}

/**
 * A testing job.
 */
interface TestJob {
	projectName: string;
	name: string;
	command: string;
	testEnv: TestJobEnv;
}

/**
 * All of the jobs that should be run.
 */
interface Jobs {
	lint: LintJob[];
	test: TestJob[];
}

/**
 * Checks the config against the changes and creates one if it should be run.
 *
 * @param {string}         projectName The name of the project that the job is for.
 * @param {Object}         config      The config object for the lint job.
 * @param {Array.<string>} changes     The file changes that have occurred for the project.
 * @return {Object|null} The job that should be run or null if no job should be run.
 */
function createLintJob(
	projectName: string,
	config: LintJobConfig,
	changes: string[]
): LintJob | null {
	let triggered = false;

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

	if ( ! triggered ) {
		return null;
	}

	return {
		projectName,
		command: config.command,
	};
}

/**
 * Checks the config against the changes and creates one if it should be run.
 *
 * @param {string}         projectName The name of the project that the job is for.
 * @param {Object}         config      The config object for the test job.
 * @param {Array.<string>} changes     The file changes that have occurred for the project.
 * @param {Array.<string>} cascadeKeys The cascade keys that have been triggered in dependencies.
 * @return {Promise.<Object|null>} The job that should be run or null if no job should be run.
 */
async function createTestJob(
	projectName: string,
	config: TestJobConfig,
	changes: string[],
	cascadeKeys: string[]
): Promise< TestJob | null > {
	let triggered = false;

	// Some jobs can be configured to trigger when a dependency has a job that
	// was triggered. For example, a code change in a dependency might mean
	// that code is impacted in the current project even if no files were
	// actually changed in this project.
	if (
		config.cascadeKeys &&
		config.cascadeKeys.some( ( value ) => cascadeKeys.includes( value ) )
	) {
		triggered = true;
	}

	// Projects can configure jobs to be triggered when a
	// changed file matches a path regex.
	if ( ! triggered ) {
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
		name: config.name,
		command: config.command,
		testEnv: {
			shouldCreate: false,
			start: '',
			envVars: {},
		},
	};

	// We want to make sure that we're including the configuration for
	// any test environment that the job will need in order to run.
	if ( config.testEnv ) {
		createdJob.testEnv = {
			shouldCreate: true,
			start: config.testEnv.start,
			envVars: await parseTestEnvConfig( config.testEnv.config ),
		};
	}

	return createdJob;
}

/**
 * Recursively checks the project for any jobs that should be executed and returns them.
 *
 * @param {Object}         node         The current project node to examine.
 * @param {Object}         changedFiles The files that have changed for the project.
 * @param {Array.<string>} cascadeKeys  The cascade keys that have been triggered in dependencies.
 * @return {Promise.<Object>} The jobs that have been created for the project.
 */
async function createJobsForProject(
	node: ProjectNode,
	changedFiles: ProjectFileChanges,
	cascadeKeys: string[]
): Promise< Jobs > {
	// We're going to traverse the project graph and check each node for any jobs that should be triggered.
	const newJobs: Jobs = {
		lint: [],
		test: [],
	};

	// In order to simplify the way that cascades work we're going to recurse depth-first and check our dependencies
	// for jobs before ourselves. This lets any cascade keys created in dependencies cascade to dependents.
	const newCascadeKeys = [];
	for ( const dependency of node.dependencies ) {
		// Each dependency needs to have its own cascade keys so that they don't cross-contaminate.
		const dependencyCascade = [ ...cascadeKeys ];

		const dependencyJobs = await createJobsForProject(
			dependency,
			changedFiles,
			dependencyCascade
		);
		newJobs.lint.push( ...dependencyJobs.lint );
		newJobs.test.push( ...dependencyJobs.test );

		// Track any new cascade keys added by the dependency.
		// Since we're filtering out duplicates after the
		// dependencies are checked we don't need to
		// worry about their presence right now.
		newCascadeKeys.push( ...dependencyCascade );
	}

	// Now that we're done looking at the dependencies we can add the cascade keys that
	// they created. Make sure to avoid adding duplicates so that we don't waste time
	// checking the same keys multiple times when we create the jobs.
	cascadeKeys.push(
		...newCascadeKeys.filter( ( value ) => ! cascadeKeys.includes( value ) )
	);

	// Projects that don't have any CI configuration don't have any potential jobs for us to check for.
	if ( ! node.ciConfig ) {
		return newJobs;
	}

	for ( const jobConfig of node.ciConfig.jobs ) {
		// Make sure that we don't queue the same job more than once.
		if ( jobConfig.jobCreated ) {
			continue;
		}

		switch ( jobConfig.type ) {
			case JobType.Lint: {
				const created = createLintJob(
					node.name,
					jobConfig,
					changedFiles[ node.name ] ?? []
				);
				if ( ! created ) {
					break;
				}

				jobConfig.jobCreated = true;
				newJobs.lint.push( created );
				break;
			}

			case JobType.Test: {
				const created = await createTestJob(
					node.name,
					jobConfig,
					changedFiles[ node.name ] ?? [],
					cascadeKeys
				);
				if ( ! created ) {
					break;
				}

				jobConfig.jobCreated = true;
				newJobs.test.push( created );

				// We need to track any cascade keys that this job is associated with so that
				// dependent projects can trigger jobs with matching keys. We are expecting
				// the array passed to this function to be modified by reference so this
				// behavior is intentional.
				if ( jobConfig.cascadeKeys ) {
					cascadeKeys.push( ...jobConfig.cascadeKeys );
				}
				break;
			}
		}
	}

	return newJobs;
}

/**
 * Creates jobs to run for the given project graph and file changes.
 *
 * @param {Object} root    The root node for the project graph.
 * @param {Object} changes The file changes that have occurred.
 * @return {Promise.<Object>} The jobs that should be run.
 */
export function createJobsForChanges(
	root: ProjectNode,
	changes: ProjectFileChanges
): Promise< Jobs > {
	return createJobsForProject( root, changes, [] );
}
