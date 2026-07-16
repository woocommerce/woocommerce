/* eslint-disable playwright/expect-expect -- Node's assertion module is not recognized by the Playwright lint rule. */
const assert = require( 'node:assert/strict' );
const { existsSync, readFileSync, writeFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const { describe, test } = require( 'node:test' );

const { extractShardArgument, run } = require( '../run-tests' );

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

describe( 'extractShardArgument', () => {
	test( 'extracts standard equals and separate-value shard arguments', () => {
		assert.deepEqual(
			extractShardArgument( [ '--shard=1/2', '--grep', 'checkout' ] ),
			{
				shard: { index: 1, count: 2 },
				passthroughArguments: [ '--grep', 'checkout' ],
			}
		);
		assert.deepEqual(
			extractShardArgument( [ '--shard', '2/3', '--last-failed' ] ),
			{
				shard: { index: 2, count: 3 },
				passthroughArguments: [ '--last-failed' ],
			}
		);
		assert.deepEqual(
			extractShardArgument( [ '--', '--shard=1/2', '--list' ] ),
			{
				shard: { index: 1, count: 2 },
				passthroughArguments: [ '--list' ],
			}
		);
		assert.deepEqual(
			extractShardArgument( [
				'--shard=4/10',
				'--last-failed',
				'--shard=1/1',
			] ),
			{
				shard: { index: 4, count: 10 },
				passthroughArguments: [ '--last-failed' ],
			}
		);
	} );

	test( 'returns no shard without changing other arguments', () => {
		assert.deepEqual( extractShardArgument( [ '--grep', 'checkout' ] ), {
			shard: undefined,
			passthroughArguments: [ '--grep', 'checkout' ],
		} );
	} );

	test( 'rejects missing and unsupported duplicate shard values', () => {
		assert.throws(
			() => extractShardArgument( [ '--shard' ] ),
			/Expected a value after --shard/
		);
		for ( const arguments_ of [
			[ '--shard=1/2', '--shard=1/1' ],
			[ '--shard=1/2', '--last-failed', '--shard=2/2' ],
			[ '--shard=1/1', '--last-failed', '--shard=1/2' ],
			[ '--shard=1/2', '--last-failed', '--shard=1/1', '--shard=1/1' ],
		] ) {
			assert.throws(
				() => extractShardArgument( arguments_ ),
				/Multiple --shard arguments are only supported for --last-failed retries ending in --shard=1\/1/
			);
		}
	} );
} );

describe( 'run', () => {
	function playwrightReport( reportFiles ) {
		return {
			suites: [
				{
					specs: reportFiles.map( ( file ) => ( {
						file,
						tests: [ { projectName: 'blocks-chromium' } ],
					} ) ),
				},
			],
		};
	}

	test( 'launches Playwright once for an unsharded command', () => {
		const calls = [];
		const spawn = ( command, arguments_, options ) => {
			calls.push( { command, arguments_, options } );
			return { status: 7 };
		};

		assert.equal( run( [ '--', '--grep', 'checkout' ], spawn ), 7 );
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

	test( 'preserves the planner shard for a failed-test retry', () => {
		const calls = [];
		let selectedTestList;
		let reportPath;
		let testListPath;
		const spawnHandlers = [
			( arguments_, options ) => {
				reportPath = options.env.PLAYWRIGHT_JSON_OUTPUT_FILE;
				writeFileSync(
					reportPath,
					JSON.stringify( playwrightReport( files ) )
				);
				return { status: 0 };
			},
			( arguments_ ) => {
				testListPath = arguments_
					.find( ( argument ) =>
						argument.startsWith( '--test-list=' )
					)
					.slice( '--test-list='.length );
				selectedTestList = readFileSync( testListPath, 'utf8' );
				return { status: 7 };
			},
		];
		const spawn = ( command, arguments_, options ) => {
			const handler = spawnHandlers[ calls.length ];
			calls.push( { command, arguments_, options } );
			return handler( arguments_, options );
		};

		assert.equal(
			run(
				[ '--shard=2/2', '--last-failed', '--shard=1/1' ],
				spawn,
				manifest
			),
			7
		);
		assert.equal( calls.length, 2 );
		assert.deepEqual( calls[ 0 ].arguments_.slice( 1 ), [
			...baseArguments,
			'--list',
			'--reporter=json',
		] );
		assert.match(
			calls[ 0 ].options.env.PLAYWRIGHT_JSON_OUTPUT_FILE,
			/playwright-report\.json$/
		);
		assert.deepEqual( calls[ 0 ].options.stdio, [
			'inherit',
			'ignore',
			'inherit',
		] );
		assert.deepEqual( calls[ 1 ].arguments_.slice( 1 ), [
			...baseArguments,
			`--test-list=${ testListPath }`,
			'--last-failed',
		] );
		assert.equal(
			selectedTestList,
			'[blocks-chromium] › blocks/b.spec.ts\n' +
				'[blocks-chromium] › blocks/c.spec.ts\n'
		);
		assert.equal( existsSync( reportPath ), false );
		assert.equal( existsSync( testListPath ), false );
	} );

	test( 'does not execute tests when Playwright discovery fails', () => {
		let calls = 0;
		const spawn = () => {
			calls++;
			return { status: 4 };
		};

		assert.equal( run( [ '--shard=1/2' ], spawn, manifest ), 4 );
		assert.equal( calls, 1 );
	} );

	test( 'rejects malformed and empty Playwright inventories', () => {
		const malformedSpawn = ( command, arguments_, options ) => {
			writeFileSync(
				options.env.PLAYWRIGHT_JSON_OUTPUT_FILE,
				'not JSON'
			);
			return { status: 0 };
		};
		assert.throws(
			() => run( [ '--shard=1/2' ], malformedSpawn, manifest ),
			/Unable to parse Playwright test inventory/
		);

		const emptySpawn = ( command, arguments_, options ) => {
			writeFileSync(
				options.env.PLAYWRIGHT_JSON_OUTPUT_FILE,
				JSON.stringify( { suites: [] } )
			);
			return { status: 0 };
		};
		assert.throws(
			() => run( [ '--shard=1/2' ], emptySpawn, manifest ),
			/No tests found for Playwright project blocks-chromium/
		);
	} );

	test( 'throws process launch and signal failures during discovery', () => {
		const launchError = new Error( 'could not launch' );
		assert.throws(
			() =>
				run(
					[ '--shard=1/2' ],
					() => ( { error: launchError } ),
					manifest
				),
			launchError
		);
		assert.throws(
			() =>
				run(
					[ '--shard=1/2' ],
					() => ( { signal: 'SIGTERM' } ),
					manifest
				),
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
			( _, index ) => `--shard=${ index + 1 }/10`
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
			'pnpm test:e2e:sharding && node tests/e2e/bin/blocks/run-tests.js'
		);
		assert.equal(
			packageJson.scripts[ 'test:e2e:sharding' ],
			'node --test tests/e2e/bin/blocks/__tests__/*.test.js'
		);
		assert.equal(
			packageJson.scripts[ 'test:e2e:blocks:generate-sharding-manifest' ],
			'node tests/e2e/bin/blocks/generate-duration-manifest.js'
		);
	} );

	test( 'does not add a dedicated CI job for the fast regression suite', () => {
		const packageJson = require( path.resolve(
			__dirname,
			'../../../../../package.json'
		) );
		const testDefinition = packageJson.config.ci.tests.find(
			( candidate ) => candidate.name === 'Blocks E2E duration sharding'
		);

		assert.equal( testDefinition, undefined );
	} );
} );
