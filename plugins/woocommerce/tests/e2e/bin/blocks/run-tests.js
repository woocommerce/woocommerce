#!/usr/bin/env node
const { spawnSync } = require( 'node:child_process' );
const path = require( 'node:path' );

const manifest = require( './block-test-durations.json' );
const {
	discoverBlocksSpecs,
	parseDurationShard,
	planDurationShards,
} = require( './duration-sharding' );

const BLOCKS_TESTS_ROOT = path.resolve( __dirname, '../../tests' );
const DURATION_SHARD_COUNT = 10;
const PLAYWRIGHT_BASE_ARGUMENTS = [
	'test',
	'--config=tests/e2e/playwright.config.ts',
	'--project=blocks-chromium',
];

function escapeRegularExpression( value ) {
	return value.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
}

function buildPlaywrightArguments( {
	cliArguments,
	files = discoverBlocksSpecs( BLOCKS_TESTS_ROOT ),
	manifest: durationManifest = manifest,
	expectedShardCount = DURATION_SHARD_COUNT,
} ) {
	const durationShardArguments = cliArguments.filter( ( argument ) =>
		argument.startsWith( '--duration-shard=' )
	);

	if ( durationShardArguments.length > 1 ) {
		throw new Error( 'Expected at most one --duration-shard argument' );
	}
	if ( durationShardArguments.length === 0 ) {
		return [ ...PLAYWRIGHT_BASE_ARGUMENTS, ...cliArguments ];
	}

	const durationShardValue = durationShardArguments[ 0 ].slice(
		'--duration-shard='.length
	);
	const { index } = parseDurationShard(
		durationShardValue,
		expectedShardCount
	);
	const selectedShard = planDurationShards( {
		files,
		manifest: durationManifest,
		shardCount: expectedShardCount,
	} )[ index - 1 ];
	const passthroughArguments = cliArguments.filter(
		( argument ) => ! argument.startsWith( '--duration-shard=' )
	);
	const selectedFiles = selectedShard.files.map(
		( file ) =>
			`${ escapeRegularExpression( `tests/e2e/tests/${ file }` ) }$`
	);

	return [
		...PLAYWRIGHT_BASE_ARGUMENTS,
		...selectedFiles,
		...passthroughArguments,
	];
}

function run( cliArguments = process.argv.slice( 2 ), spawn = spawnSync ) {
	const playwrightCli = require.resolve( '@playwright/test/cli' );
	const result = spawn(
		process.execPath,
		[ playwrightCli, ...buildPlaywrightArguments( { cliArguments } ) ],
		{
			stdio: 'inherit',
			env: process.env,
		}
	);

	if ( result.error ) {
		throw result.error;
	}
	if ( result.signal ) {
		throw new Error(
			`Playwright terminated with signal ${ result.signal }`
		);
	}

	return result.status ?? 1;
}

if ( require.main === module ) {
	try {
		process.exitCode = run();
	} catch ( error ) {
		console.error( error.message );
		process.exitCode = 1;
	}
}

module.exports = {
	buildPlaywrightArguments,
	run,
};
