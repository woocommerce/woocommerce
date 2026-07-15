/* eslint-disable playwright/expect-expect -- Node's assertion module is not recognized by the Playwright lint rule. */
const assert = require( 'node:assert/strict' );
const path = require( 'node:path' );
const { describe, test } = require( 'node:test' );

const { buildPlaywrightArguments, run } = require( '../run-tests' );

const manifest = {
	schemaVersion: 1,
	sourceRuns: [ 1 ],
	fallbackDurationMs: 5,
	files: {
		'blocks/a.spec.ts': 30,
		'blocks/b.spec.ts': 20,
		'blocks/c.spec.ts': 10,
	},
};
const files = Object.keys( manifest.files );
const baseArguments = [
	'test',
	'--config=tests/e2e/playwright.config.ts',
	'--project=blocks-chromium',
];

describe( 'buildPlaywrightArguments', () => {
	test( 'preserves the existing unsharded command behavior', () => {
		assert.deepEqual(
			buildPlaywrightArguments( {
				cliArguments: [ '--grep', 'checkout' ],
				files,
				manifest,
			} ),
			[ ...baseArguments, '--grep', 'checkout' ]
		);
	} );

	test( 'replaces the duration shard argument with explicit planned files', () => {
		assert.deepEqual(
			buildPlaywrightArguments( {
				cliArguments: [ '--duration-shard=1/2' ],
				files,
				manifest,
				expectedShardCount: 2,
			} ),
			[ ...baseArguments, 'tests/e2e/tests/blocks/a\\.spec\\.ts$' ]
		);
	} );

	test( 'escapes explicit paths as exact Playwright regular expressions', () => {
		const regexManifest = {
			...manifest,
			files: { 'blocks/a+[draft].spec.ts': 10 },
		};

		assert.deepEqual(
			buildPlaywrightArguments( {
				cliArguments: [ '--duration-shard=1/1' ],
				files: Object.keys( regexManifest.files ),
				manifest: regexManifest,
				expectedShardCount: 1,
			} ),
			[
				...baseArguments,
				'tests/e2e/tests/blocks/a\\+\\[draft\\]\\.spec\\.ts$',
			]
		);
	} );

	test( 'passes last-failed and its neutral Playwright shard through unchanged', () => {
		assert.deepEqual(
			buildPlaywrightArguments( {
				cliArguments: [
					'--duration-shard=2/2',
					'--last-failed',
					'--shard=1/1',
				],
				files,
				manifest,
				expectedShardCount: 2,
			} ),
			[
				...baseArguments,
				'tests/e2e/tests/blocks/b\\.spec\\.ts$',
				'tests/e2e/tests/blocks/c\\.spec\\.ts$',
				'--last-failed',
				'--shard=1/1',
			]
		);
	} );

	test( 'rejects multiple duration shard arguments', () => {
		assert.throws(
			() =>
				buildPlaywrightArguments( {
					cliArguments: [
						'--duration-shard=1/2',
						'--duration-shard=2/2',
					],
					files,
					manifest,
					expectedShardCount: 2,
				} ),
			/Expected at most one --duration-shard argument/
		);
	} );
} );

describe( 'run', () => {
	test( 'launches Playwright once and returns its exit status', () => {
		const calls = [];
		const spawn = ( command, arguments_, options ) => {
			calls.push( { command, arguments_, options } );
			return { status: 7 };
		};

		assert.equal( run( [ '--grep', 'checkout' ], spawn ), 7 );
		assert.equal( calls.length, 1 );
		assert.equal( calls[ 0 ].command, process.execPath );
		assert.equal(
			calls[ 0 ].arguments_[ 0 ],
			require.resolve( '@playwright/test/cli' )
		);
		assert.deepEqual( calls[ 0 ].arguments_.slice( 1 ), [
			...baseArguments,
			'--grep',
			'checkout',
		] );
		assert.equal( calls[ 0 ].options.stdio, 'inherit' );
		assert.equal( calls[ 0 ].options.env, process.env );
	} );

	test( 'throws process launch and signal failures', () => {
		const launchError = new Error( 'could not launch' );
		assert.throws(
			() => run( [], () => ( { error: launchError } ) ),
			launchError
		);
		assert.throws(
			() => run( [], () => ( { signal: 'SIGTERM' } ) ),
			/Playwright terminated with signal SIGTERM/
		);
	} );
} );

describe( 'package configuration', () => {
	test( 'uses duration-aware sharding for every Blocks E2E variant', () => {
		const packageJson = require( path.resolve(
			__dirname,
			'../../../../../package.json'
		) );
		const tests = packageJson.config.ci.tests.filter( ( testDefinition ) =>
			testDefinition.name.startsWith( 'Blocks e2e tests' )
		);
		const expectedArguments = Array.from(
			{ length: 10 },
			( _, index ) => `--duration-shard=${ index + 1 }/10`
		);

		assert.deepEqual(
			tests.map( ( testDefinition ) => testDefinition.name ),
			[
				'Blocks e2e tests',
				'Blocks e2e tests - WP pre-release',
				'Blocks e2e tests - WP latest-1',
			]
		);
		for ( const testDefinition of tests ) {
			assert.equal( testDefinition.command, 'test:e2e:blocks' );
			assert.deepEqual(
				testDefinition.shardingArguments,
				expectedArguments
			);
		}
	} );

	test( 'exposes the wrapper and its fast regression suite as scripts', () => {
		const packageJson = require( path.resolve(
			__dirname,
			'../../../../../package.json'
		) );

		assert.equal(
			packageJson.scripts[ 'test:e2e:blocks' ],
			'node tests/e2e/bin/blocks/run-tests.js'
		);
		assert.equal(
			packageJson.scripts[ 'test:e2e:sharding' ],
			'node --test tests/e2e/bin/blocks/__tests__/*.test.js'
		);
	} );

	test( 'runs the fast regression suite when sharding code changes', () => {
		const packageJson = require( path.resolve(
			__dirname,
			'../../../../../package.json'
		) );
		const testDefinition = packageJson.config.ci.tests.find(
			( candidate ) => candidate.name === 'Blocks E2E duration sharding'
		);

		assert.deepEqual( testDefinition, {
			name: 'Blocks E2E duration sharding',
			testType: 'unit',
			command: 'test:e2e:sharding',
			changes: [
				'tests/e2e/bin/blocks/block-test-durations.json',
				'tests/e2e/bin/blocks/duration-sharding.js',
				'tests/e2e/bin/blocks/run-tests.js',
				'tests/e2e/bin/blocks/__tests__/**',
			],
			onlyForDependencies: [],
			events: [ 'pull_request', 'push' ],
		} );
	} );
} );
