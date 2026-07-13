import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const pluginRoot = resolve(
	dirname( fileURLToPath( import.meta.url ) ),
	'../../..'
);
const repositoryRoot = resolve( pluginRoot, '../..' );
const packageJson = JSON.parse(
	readFileSync( resolve( pluginRoot, 'package.json' ), 'utf8' )
);
const jobs = packageJson.config.ci.tests;
const defaultApiJob = jobs.find( ( job ) => job.name === 'Core API tests' );
const trackingJob = jobs.find(
	( job ) => job.name === 'Core API tests (tracking enabled)'
);
const configurationJob = jobs.find(
	( job ) => job.name === 'Core API tracking CI configuration'
);

assert.ok( defaultApiJob, 'The default Core API job must exist.' );
assert.ok( trackingJob, 'The tracking-enabled Core API job must exist.' );
assert.deepEqual(
	trackingJob.changes,
	defaultApiJob.changes,
	'The tracking job must use the default Core API change filter.'
);
assert.equal( trackingJob.testType, 'api' );
assert.equal( trackingJob.usesSharedPluginBuild, true );
assert.equal( trackingJob.command, 'test:api:tracking' );
assert.equal( trackingJob.optional, false );
assert.equal( trackingJob.testEnv.start, 'env:e2e:tracking --debug' );
assert.deepEqual( trackingJob.events, [
	'pull_request',
	'nightly-checks',
	'release-checks',
	'core-api-tracking',
] );
assert.deepEqual( trackingJob.report, {
	resultsBlobName: 'core-api-report-tracking-enabled',
	resultsPath: 'tests/e2e/test-results',
	allure: true,
} );
assert.ok( configurationJob, 'The tracking configuration job must exist.' );
assert.equal( configurationJob.command, 'test:api:tracking:config' );
assert.equal( configurationJob.optional, false );
assert.deepEqual( configurationJob.changes, [
	'package.json',
	'tests/e2e/bin/test-env-setup.sh',
	'tests/e2e/bin/validate-api-tracking-ci.mjs',
] );
assert.deepEqual( configurationJob.events, [ 'pull_request', 'push' ] );
assert.equal( Object.hasOwn( configurationJob, 'testEnv' ), false );

const runGenerator = ( event ) => {
	const generatorEnv = {
		...process.env,
		FORCE_COLOR: '0',
		NO_COLOR: '1',
	};
	delete generatorEnv.CI;
	delete generatorEnv.GITHUB_ACTIONS;
	delete generatorEnv.GITHUB_OUTPUT;

	const result = spawnSync(
		'pnpm',
		[ 'utils', 'ci-jobs', '--event', event ],
		{
			cwd: repositoryRoot,
			encoding: 'utf8',
			env: generatorEnv,
		}
	);
	const output = `${ result.stdout ?? '' }${ result.stderr ?? '' }`;

	assert.equal(
		result.status,
		0,
		`The CI job generator failed for ${ event }:\n${ output }`
	);

	return output;
};

const count = ( output, value ) => output.split( value ).length - 1;
const trackingJobName =
	'Core API tests (tracking enabled) - @woocommerce/plugin-woocommerce [api]';
const defaultApiJobName =
	'Core API tests - @woocommerce/plugin-woocommerce [api]';
const configurationJobName =
	'Core API tracking CI configuration - @woocommerce/plugin-woocommerce [unit]';
const trackingReport = 'core-api-report-tracking-enabled';

for ( const event of [
	'pull_request',
	'nightly-checks',
	'release-checks',
	'core-api-tracking',
] ) {
	const output = runGenerator( event );

	assert.equal(
		count( output, trackingJobName ),
		1,
		`Expected one tracking job for ${ event }.`
	);
	assert.equal(
		count( output, trackingReport ),
		1,
		`Expected one tracking report for ${ event }.`
	);
	assert.doesNotMatch(
		output,
		/Core API tests \(tracking enabled\).*\(optional\)/,
		`The tracking job must be required for ${ event }.`
	);

	if ( event === 'pull_request' ) {
		assert.equal(
			count( output, defaultApiJobName ),
			1,
			'The default Core API job must remain on pull requests.'
		);
		assert.equal(
			count( output, configurationJobName ),
			1,
			'The tracking configuration job must run on pull requests.'
		);
	}
}

const pushOutput = runGenerator( 'push' );
assert.equal( count( pushOutput, trackingJobName ), 0 );
assert.equal( count( pushOutput, trackingReport ), 0 );
assert.equal( count( pushOutput, configurationJobName ), 1 );

console.log( 'Core API tracking CI configuration is valid.' );
