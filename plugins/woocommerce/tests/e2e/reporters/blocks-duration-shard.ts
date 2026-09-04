/**
 * External dependencies
 */
import path from 'node:path';
import type {
	FullConfig,
	Suite,
	TestCase,
	TestRun,
} from '@playwright/test/reporter';

/**
 * Internal dependencies
 */
import {
	selectShardFiles,
	type DurationManifest,
} from '../bin/blocks/duration-sharding';
import durationManifest from '../bin/blocks/block-test-durations.json';

const BLOCKS_PROJECT = 'blocks-chromium';

/**
 * Names of every project that another project depends on. Playwright prepends
 * these to the root suite for `preprocess` and keeps them readonly, so they
 * must not count towards deciding whether this run is Blocks-only.
 *
 * @param config Resolved Playwright configuration.
 */
function dependencyProjectNames( config: FullConfig ): Set< string > {
	return new Set(
		config.projects.flatMap( ( project ) => project.dependencies ?? [] )
	);
}

/**
 * Groups a project's tests by the manifest key of the spec file they live in.
 *
 * Manifest keys are paths relative to the config root so a plan means the same
 * thing in a developer checkout and on a CI runner.
 *
 * @param config       Resolved Playwright configuration.
 * @param projectSuite Suite for the project being sharded.
 */
function groupTestsByFile(
	config: FullConfig,
	projectSuite: Suite
): Map< string, TestCase[] > {
	const testsByFile = new Map< string, TestCase[] >();

	for ( const testCase of projectSuite.allTests() ) {
		const file = path
			.relative( config.rootDir, testCase.location.file )
			.replaceAll( '\\', '/' );
		const tests = testsByFile.get( file );

		if ( tests ) {
			tests.push( testCase );
		} else {
			testsByFile.set( file, [ testCase ] );
		}
	}

	return testsByFile;
}

/**
 * Replaces Playwright's count-based sharding for the Blocks project with a
 * duration-balanced partition of whole spec files.
 *
 * Playwright assigns contiguous ranges of tests in discovery order, which
 * leaves the slow editor specs bunched in the final shard while the other nine
 * finish and idle. This reporter receives the discovered suite before sharding
 * happens, packs the spec files into the requested number of shards by recorded
 * duration, and excludes the files belonging to the other shards.
 *
 * Whole files stay together because the Blocks project runs with
 * `fullyParallel: false` and a single worker, so a file is the smallest unit
 * that can be rescheduled without changing isolation semantics.
 */
class BlocksDurationShardReporter {
	/**
	 * This reporter only excludes tests and never writes progress output, so
	 * Playwright should keep enhancing the terminal reporter's output.
	 */
	printsToStdio(): boolean {
		return false;
	}

	async preprocess( {
		config,
		suite,
		testRun,
	}: {
		config: FullConfig;
		suite: Suite;
		testRun: TestRun;
	} ): Promise< void > {
		if ( ! config.shard ) {
			return;
		}

		const dependencies = dependencyProjectNames( config );
		const selectedProjects = suite.suites.filter(
			( projectSuite ) => ! dependencies.has( projectSuite.title )
		);

		// Only take over sharding when the run is exactly the Blocks project.
		// Every wider invocation keeps Playwright's own partitioning, which is
		// the right behaviour for the other projects in this config.
		if (
			selectedProjects.length !== 1 ||
			selectedProjects[ 0 ].title !== BLOCKS_PROJECT
		) {
			return;
		}

		const testsByFile = groupTestsByFile( config, selectedProjects[ 0 ] );
		const { files: shardFiles, fallbackReason } = selectShardFiles( {
			files: [ ...testsByFile.keys() ].sort(),
			manifest: durationManifest as DurationManifest,
			shard: config.shard,
		} );

		if ( ! shardFiles ) {
			// Leaving Playwright's sharding in place still runs every test, so
			// an unusable manifest costs balance rather than coverage. Announce
			// it loudly instead of failing the run.
			console.log(
				`::warning title=Blocks duration sharding disabled::${ fallbackReason }. Falling back to Playwright's count-based sharding.`
			);
			return;
		}

		for ( const [ file, tests ] of testsByFile ) {
			if ( shardFiles.has( file ) ) {
				continue;
			}
			for ( const testCase of tests ) {
				testRun.exclude( testCase );
			}
		}

		testRun.skipSharding();
	}
}

export default BlocksDurationShardReporter;
