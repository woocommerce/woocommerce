/**
 * External dependencies
 */
import { makeRe } from 'minimatch';

/**
 * Internal dependencies
 */
import { PackageJSON } from './package-file';

/**
 * A configuration error type.
 */
export class ConfigError extends Error {}

/**
 * The type of the job.
 */
export const enum JobType {
	Lint = 'lint',
	Test = 'test',
}

/**
 * The type of the test job.
 */
export const testTypes = [ 'default', 'e2e', 'api', 'performance' ] as const;

/**
 * The variables that can be used in tokens on command strings
 * that will be replaced during job creation.
 */
export enum CommandVarOptions {
	BaseRef = 'baseRef',
	Event = 'event',
}

/**
 * The base config requirements for all jobs.
 */
interface BaseJobConfig {
	/**
	 * The type of the job.
	 */
	type: JobType;

	/**
	 * The changes that should trigger this job.
	 */
	changes: RegExp[];

	/**
	 * The command to run for the job.
	 */
	command: string;

	/**
	 * The type of GitHub events this job is supposed to run on.
	 * Example: push, pull_request
	 */
	events: string[];

	/**
	 * Indicates whether or not a job has been created for this config.
	 */
	jobCreated?: boolean;
}

/**
 * Parses and validates a raw change config entry.
 *
 * @param {string|string[]} raw        The raw config to parse.
 * @param {string[]}        extraGlobs Any extra globs that should be added to the configuration.
 */
function parseChangesConfig(
	raw: unknown,
	extraGlobs: string[] = []
): RegExp[] {
	const changes: RegExp[] = [];

	// Make sure to include any extra glob patterns that were passed in.
	// This allows us to make sure we're watching for changes in files
	// that may implicitly be impactful but shouldn't need to be
	// stated explicitly in the list of file changes.
	for ( const entry of extraGlobs ) {
		const regex = makeRe( entry );
		if ( ! regex ) {
			throw new Error( 'Invalid extra glob pattern.' );
		}

		changes.push( regex );
	}

	if ( typeof raw === 'string' ) {
		const regex = makeRe( raw );
		if ( ! regex ) {
			throw new ConfigError(
				'Changes configuration is an invalid glob pattern.'
			);
		}

		changes.push( regex );
		return changes;
	}

	if ( ! Array.isArray( raw ) ) {
		throw new ConfigError(
			'Changes configuration must be a string or array of strings.'
		);
	}

	for ( const entry of raw ) {
		if ( typeof entry !== 'string' ) {
			throw new ConfigError(
				'Changes configuration must be a string or array of strings.'
			);
		}

		const regex = makeRe( entry );
		if ( ! regex ) {
			throw new ConfigError(
				'Changes configuration is an invalid glob pattern.'
			);
		}

		changes.push( regex );
	}
	return changes;
}

/**
 * The configuration of the lint job.
 */
export interface LintJobConfig extends BaseJobConfig {
	/**
	 * The type of the job.
	 */
	type: JobType.Lint;
}

/**
 * Checks to see whether or not the variables in a command are valid.
 *
 * @param {string} command The command to validate.
 */
function validateCommandVars( command: string ) {
	const matches = command.matchAll( /<([^>]+)>/g );
	if ( ! matches ) {
		return;
	}

	const commandOptions: string[] = Object.values( CommandVarOptions );
	for ( const match of matches ) {
		if ( match.length !== 2 ) {
			throw new ConfigError(
				'Invalid command variable. Variables must be in the format "<variable>".'
			);
		}

		if ( ! commandOptions.includes( match[ 1 ] ) ) {
			throw new ConfigError(
				`Invalid command variable "${ match[ 1 ] }".`
			);
		}
	}
}

/**
 * Parses the lint job configuration.
 *
 * @param {Object} raw The raw config to parse.
 */
function parseLintJobConfig( raw: any ): LintJobConfig {
	if ( ! raw.changes ) {
		throw new ConfigError(
			'A "changes" option is required for the lint job.'
		);
	}

	if ( ! raw.command || typeof raw.command !== 'string' ) {
		throw new ConfigError(
			'A string "command" option is required for the lint job.'
		);
	}

	validateCommandVars( raw.command );

	return {
		type: JobType.Lint,
		changes: parseChangesConfig( raw.changes, [ 'package.json' ] ),
		command: raw.command,
		events: raw.events || [],
	};
}

/**
 * The configuration vars for test environments.
 */
export interface TestEnvConfigVars {
	/**
	 * The version of WordPress that should be used.
	 */
	wpVersion?: string;

	/**
	 * The version of PHP that should be used.
	 */
	phpVersion?: string;
}

/**
 * Parses the test env config vars.
 *
 * @param {Object} raw The raw config to parse.
 */
function parseTestEnvConfigVars( raw: any ): TestEnvConfigVars {
	const config: TestEnvConfigVars = {};
	if ( ! raw ) {
		return config;
	}

	if ( raw.wpVersion ) {
		if ( typeof raw.wpVersion !== 'string' ) {
			throw new ConfigError( 'The "wpVersion" option must be a string.' );
		}

		config.wpVersion = raw.wpVersion;
	}

	if ( raw.phpVersion ) {
		if ( typeof raw.phpVersion !== 'string' ) {
			throw new ConfigError(
				'The "phpVersion" option must be a string.'
			);
		}

		config.phpVersion = raw.phpVersion;
	}

	return config;
}

/**
 * The configuration of a test environment.
 */
interface TestEnvConfig {
	/**
	 * The command that should be used to start the test environment.
	 */
	start: string;

	/**
	 * Any configuration variables that should be used when building the environment.
	 */
	config: TestEnvConfigVars;
}

/**
 * The configuration of a test job.
 */
export interface TestJobConfig extends BaseJobConfig {
	/**
	 * The type of the job.
	 */
	type: JobType.Test;

	/**
	 * The type of the test.
	 */
	testType: ( typeof testTypes )[ number ];

	/**
	 * The name for the job.
	 */
	name: string;

	/**
	 * The number of shards to be created for this job.
	 */
	shardingArguments: string[];

	/**
	 * The configuration for the test environment if one is needed.
	 */
	testEnv?: TestEnvConfig;

	/**
	 * The key(s) to use when identifying what jobs should be triggered by a cascade.
	 */
	cascadeKeys?: string[];
}

/**
 * parses the cascade config.
 *
 * @param {string|string[]} raw The raw config to parse.
 */
function parseTestCascade( raw: unknown ): string[] {
	if ( typeof raw === 'string' ) {
		return [ raw ];
	}

	if ( ! Array.isArray( raw ) ) {
		throw new ConfigError(
			'Cascade configuration must be a string or array of strings.'
		);
	}

	const changes: string[] = [];
	for ( const entry of raw ) {
		if ( typeof entry !== 'string' ) {
			throw new ConfigError(
				'Cascade configuration must be a string or array of strings.'
			);
		}

		changes.push( entry );
	}
	return changes;
}

/**
 * Parses the test job config.
 *
 * @param {Object} raw The raw config to parse.
 */
function parseTestJobConfig( raw: any ): TestJobConfig {
	if ( ! raw.name || typeof raw.name !== 'string' ) {
		throw new ConfigError(
			'A string "name" option is required for test jobs.'
		);
	}

	if ( ! raw.changes ) {
		throw new ConfigError(
			'A "changes" option is required for the test jobs.'
		);
	}

	if ( ! raw.command || typeof raw.command !== 'string' ) {
		throw new ConfigError(
			'A string "command" option is required for the test jobs.'
		);
	}

	let testType: ( typeof testTypes )[ number ] = 'default';
	if (
		raw.testType &&
		testTypes.includes( raw.testType.toString().toLowerCase() )
	) {
		testType = raw.testType.toLowerCase();
	}

	validateCommandVars( raw.command );

	const config: TestJobConfig = {
		type: JobType.Test,
		testType,
		shardingArguments: raw.shardingArguments || [],
		events: raw.events || [],
		name: raw.name,
		changes: parseChangesConfig( raw.changes, [ 'package.json' ] ),
		command: raw.command,
	};

	if ( raw.testEnv ) {
		if ( typeof raw.testEnv !== 'object' ) {
			throw new ConfigError( 'The "testEnv" option must be an object.' );
		}

		if ( ! raw.testEnv.start || typeof raw.testEnv.start !== 'string' ) {
			throw new ConfigError(
				'A string "start" option is required for test environments.'
			);
		}

		validateCommandVars( raw.testEnv.start );

		config.testEnv = {
			start: raw.testEnv.start,
			config: parseTestEnvConfigVars( raw.testEnv.config ),
		};
	}

	if ( raw.cascade ) {
		config.cascadeKeys = parseTestCascade( raw.cascade );
	}

	return config;
}

/**
 * The configuration of a job.
 */
type JobConfig = LintJobConfig | TestJobConfig;

/**
 * A project's CI configuration.
 */
export interface CIConfig {
	/**
	 * The configuration for jobs in this config.
	 */
	jobs: JobConfig[];
}

/**
 * Parses the raw CI config.
 *
 * @param {Object} raw The raw config.
 */
export function parseCIConfig( raw: PackageJSON ): CIConfig {
	const config: CIConfig = {
		jobs: [],
	};

	const ciConfig = raw.config?.ci;

	if ( ! ciConfig ) {
		return config;
	}

	if ( ciConfig.lint ) {
		if ( typeof ciConfig.lint !== 'object' ) {
			throw new ConfigError( 'The "lint" option must be an object.' );
		}

		config.jobs.push( parseLintJobConfig( ciConfig.lint ) );
	}

	if ( ciConfig.tests ) {
		if ( ! Array.isArray( ciConfig.tests ) ) {
			throw new ConfigError( 'The "tests" option must be an array.' );
		}

		for ( const rawTestConfig of ciConfig.tests ) {
			config.jobs.push( parseTestJobConfig( rawTestConfig ) );
		}
	}

	return config;
}
