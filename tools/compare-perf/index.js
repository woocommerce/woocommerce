#!/usr/bin/env node
/* eslint-disable no-console */

const program = require( 'commander' );
const { runPerformanceTests } = require( './performance' );

const catchException = ( command ) => {
	return async ( ...args ) => {
		try {
			await command( ...args );
		} catch ( error ) {
			console.error( error );
			process.exitCode = 1;
		}
	};
};

program
	.command( 'compare-performance [branches...]' )
	.alias( 'perf' )
	.option(
		'-c, --ci',
		'Run in CI (non interactive)'
	)
	.option(
		'--skip-benchmarking',
		'Skips benchmarking and gets straight to reporting phase (tests results already available)'
	)
	.option(
		'--rounds <count>',
		'Run each test suite this many times for each branch; results are summarized, default = 1'
	)
	.option(
		'--tests-branch <branch>',
		"Use this branch's performance test files"
	)
	.option(
		'--wp-version <version>',
		'Specify a WordPress version on which to test all branches'
	)
	.description(
		'Runs performance tests on two separate branches and outputs the result'
	)
	.action( catchException( runPerformanceTests ) );

program.parse( process.argv );
