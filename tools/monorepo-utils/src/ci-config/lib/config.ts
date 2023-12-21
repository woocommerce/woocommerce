/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * A configuration error type.
 */
export class ConfigError extends Error {}

/**
 * Parses and validates a raw change config entry.
 *
 * @param {string|string[]} raw The raw config to parse.
 */
function parseChangesConfig( raw: unknown ): RegExp[] {
	if ( typeof raw === 'string' ) {
		return [ new RegExp( raw ) ];
	}

	if ( ! Array.isArray( raw ) ) {
		throw new ConfigError(
			'Changes configuration must be a string or array of strings.'
		);
	}

	const changes: RegExp[] = [];
	for ( const entry of raw ) {
		if ( typeof entry !== 'string' ) {
			throw new ConfigError(
				'Changes configuration must be a string or array of strings.'
			);
		}

		changes.push( new RegExp( entry ) );
	}
	return changes;
}

/**
 * The configuration of the lint job.
 */
interface LintJobConfig {
	/**
	 * The changes that should trigger this job.
	 */
	changes: RegExp[];

	/**
	 * The linting command to run.
	 */
	command: string;
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

	return {
		changes: parseChangesConfig( raw.changes ),
		command: raw.command,
	};
}

/**
 * The configuration vars for test environments.
 */
interface TestEnvConfigVars {
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
interface TestJobConfig {
	/**
	 * The name for the job.
	 */
	name: string;

	/**
	 * The changes that should trigger this job.
	 */
	changes: RegExp[];

	/**
	 * The test command to run.
	 */
	command: string;

	/**
	 * The configuration for the test environment if one is needed.
	 */
	testEnv?: TestEnvConfig;

	/**
	 * The key(s) to use when identifying what jobs should be triggered by a cascade.
	 */
	cascade?: string[];
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

	const config: TestJobConfig = {
		name: raw.name,
		changes: parseChangesConfig( raw.changes ),
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

		config.testEnv = {
			start: raw.testEnv.start,
			config: parseTestEnvConfigVars( raw.testEnv.config ),
		};
	}

	if ( raw.cascade ) {
		config.cascade = parseTestCascade( raw.cascade );
	}

	return config;
}

/**
 * A project's CI configuration.
 */
interface CIConfig {
	/**
	 * The configuration for the lint job.
	 */
	lint?: LintJobConfig;

	/**
	 * Configuration for any test jobs.
	 */
	tests?: TestJobConfig[];
}

/**
 * Parses the raw CI config.
 *
 * @param {Object} raw The raw config.
 */
export function parseCIConfig( raw: any ): CIConfig {
	const config: CIConfig = {};

	if ( raw.lint ) {
		if ( typeof raw.lint !== 'object' ) {
			throw new ConfigError( 'The "lint" option must be an object.' );
		}

		config.lint = parseLintJobConfig( raw.lint );
	}

	if ( raw.tests ) {
		if ( ! Array.isArray( raw.tests ) ) {
			throw new ConfigError( 'The "tests" option must be an array.' );
		}

		config.tests = raw.tests.map( parseTestJobConfig );
	}

	return config;
}
