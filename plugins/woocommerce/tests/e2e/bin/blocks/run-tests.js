#!/usr/bin/env node
const { spawnSync } = require( 'node:child_process' );
const { mkdtempSync, rmSync, writeFileSync } = require( 'node:fs' );
const { tmpdir } = require( 'node:os' );
const path = require( 'node:path' );

const manifest = require( './block-test-durations.json' );
const {
	BLOCKS_PROJECT,
	discoverBlocksFiles,
	PLAYWRIGHT_BASE_ARGUMENTS,
} = require( './discover-blocks-files' );
const {
	buildProjectTestList,
	parseShard,
	planDurationShards,
} = require( './duration-sharding' );

function extractShardArgument( cliArguments ) {
	const shardValues = [];
	const passthroughArguments = [];
	const normalizedArguments =
		cliArguments[ 0 ] === '--' ? cliArguments.slice( 1 ) : cliArguments;

	for ( let index = 0; index < normalizedArguments.length; index++ ) {
		const argument = normalizedArguments[ index ];
		if ( argument === '--shard' ) {
			if ( index + 1 >= normalizedArguments.length ) {
				throw new Error( 'Expected a value after --shard' );
			}
			shardValues.push( normalizedArguments[ ++index ] );
		} else if ( argument.startsWith( '--shard=' ) ) {
			shardValues.push( argument.slice( '--shard='.length ) );
		} else {
			passthroughArguments.push( argument );
		}
	}

	const hasRetryOverride =
		shardValues.length === 2 &&
		shardValues[ 1 ] === '1/1' &&
		passthroughArguments.includes( '--last-failed' );
	if ( shardValues.length > 1 && ! hasRetryOverride ) {
		throw new Error(
			'Multiple --shard arguments are only supported for --last-failed retries ending in --shard=1/1'
		);
	}

	return {
		shard:
			shardValues.length >= 1
				? parseShard( shardValues[ 0 ] )
				: undefined,
		passthroughArguments,
	};
}

function throwProcessFailure( result ) {
	if ( result.error ) {
		throw result.error;
	}
	if ( result.signal ) {
		throw new Error(
			`Playwright terminated with signal ${ result.signal }`
		);
	}
}

function run(
	cliArguments = process.argv.slice( 2 ),
	spawn = spawnSync,
	durationManifest = manifest
) {
	const playwrightCli = require.resolve( '@playwright/test/cli' );
	const { shard, passthroughArguments } =
		extractShardArgument( cliArguments );

	if ( ! shard ) {
		const result = spawn(
			process.execPath,
			[
				playwrightCli,
				...PLAYWRIGHT_BASE_ARGUMENTS,
				...passthroughArguments,
			],
			{
				stdio: 'inherit',
				env: process.env,
			}
		);
		throwProcessFailure( result );
		return result.status ?? 1;
	}

	const discovery = discoverBlocksFiles( spawn );
	if ( discovery.status !== 0 ) {
		return discovery.status;
	}

	const temporaryDirectory = mkdtempSync(
		path.join( tmpdir(), 'wc-blocks-shard-' )
	);
	try {
		const selectedShard = planDurationShards( {
			files: discovery.files,
			manifest: durationManifest,
			shardCount: shard.count,
		} )[ shard.index - 1 ];
		const testListPath = path.join( temporaryDirectory, 'test-list.txt' );
		writeFileSync(
			testListPath,
			buildProjectTestList( BLOCKS_PROJECT, selectedShard.files )
		);

		const executionResult = spawn(
			process.execPath,
			[
				playwrightCli,
				...PLAYWRIGHT_BASE_ARGUMENTS,
				`--test-list=${ testListPath }`,
				...passthroughArguments,
			],
			{
				stdio: 'inherit',
				env: process.env,
			}
		);
		throwProcessFailure( executionResult );
		return executionResult.status ?? 1;
	} finally {
		rmSync( temporaryDirectory, { recursive: true, force: true } );
	}
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
	extractShardArgument,
	run,
};
