/**
 * The configuration of the lint job.
 */
export interface LintJobConfig {
	/**
	 * The name for the job.
	 */
	name: string;

	/**
	 * The changes that should trigger this job.
	 */
	changes: RegExp | RegExp[];

	/**
	 * The linting command to run.
	 */
	command: string;
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
 * The configuration of a test environment.
 */
export interface TestEnvConfig {
	/**
	 * The command that should be used to start the test environment.
	 */
	start: string;

	/**
	 * Any configuration variables that should be used when building the environment.
	 */
	config?: TestEnvConfigVars;
}

/**
 * The configuration of a test job.
 */
export interface TestJobConfig {
	/**
	 * The name for the job.
	 */
	name: string;

	/**
	 * The changes that should trigger this job.
	 */
	changes: RegExp | RegExp[];

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
	cascade?: string | string[];
}

/**
 * A project's CI configuration.
 */
export interface CIConfig {
	/**
	 * The configuration for the lint job.
	 */
	lint: LintJobConfig;

	/**
	 * Configuration for any test jobs.
	 */
	tests: TestJobConfig[];
}
