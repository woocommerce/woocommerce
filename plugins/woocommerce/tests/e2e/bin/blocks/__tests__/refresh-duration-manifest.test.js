/* eslint-disable playwright/expect-expect -- Node's assertion module is not recognized by the Playwright lint rule. */
const assert = require( 'node:assert/strict' );
const { describe, test } = require( 'node:test' );

const {
	BLOCKS_ARTIFACT_NAME,
	collectRunDirectories,
	formatDriftReport,
	mergeMeasuredDurations,
	parseArguments,
} = require( '../refresh-duration-manifest' );

describe( 'parseArguments', () => {
	test( 'defaults to regenerating from three runs', () => {
		const options = parseArguments( [] );

		assert.equal( options.runs, 3 );
		assert.equal( options.check, false );
		assert.equal( options.threshold, 0.2 );
	} );

	test( 'accepts a check request with a custom threshold', () => {
		const options = parseArguments( [
			'--check',
			'--threshold',
			'35',
			'--runs',
			'5',
		] );

		assert.equal( options.check, true );
		assert.equal( options.threshold, 0.35 );
		assert.equal( options.runs, 5 );
	} );

	test( 'rejects values that would silently produce a useless plan', () => {
		assert.throws( () => parseArguments( [ '--runs', '0' ] ), /--runs/ );
		assert.throws( () => parseArguments( [ '--runs', 'many' ] ), /--runs/ );
		assert.throws(
			() => parseArguments( [ '--threshold', '-1' ] ),
			/--threshold/
		);
		assert.throws(
			() => parseArguments( [ '--nope' ] ),
			/Unknown argument/
		);
	} );
} );

describe( 'collectRunDirectories', () => {
	function stubRunner( { runs, artifactsByRun } ) {
		const calls = [];
		return {
			calls,
			run( command, args ) {
				calls.push( [ command, ...args ] );
				if ( args.includes( 'list' ) ) {
					return JSON.stringify(
						runs.map( ( id ) => ( { databaseId: id } ) )
					);
				}
				if ( args.includes( 'download' ) ) {
					const id = args[ args.indexOf( 'download' ) + 1 ];
					if ( ! artifactsByRun[ id ] ) {
						throw new Error( 'no valid artifacts to download' );
					}
					return '';
				}
				return '';
			},
		};
	}

	test( 'takes the newest runs that actually carry the Blocks artifact', () => {
		const runner = stubRunner( {
			runs: [ 300, 299, 298, 297 ],
			artifactsByRun: { 300: true, 299: false, 298: true, 297: true },
		} );

		const directories = collectRunDirectories( {
			run: runner.run,
			runs: 2,
			repository: 'woocommerce/woocommerce',
			workingDirectory: '/tmp/refresh',
		} );

		assert.deepEqual(
			directories.map( ( directory ) => directory.id ),
			[ '300', '298' ]
		);
		assert.ok(
			runner.calls.some( ( call ) =>
				call.includes( BLOCKS_ARTIFACT_NAME )
			),
			'Expected the Blocks report artifact to be requested by name'
		);
	} );

	test( 'explains the retention window when too few runs have artifacts', () => {
		const runner = stubRunner( {
			runs: [ 300, 299 ],
			artifactsByRun: { 300: true, 299: false },
		} );

		assert.throws(
			() =>
				collectRunDirectories( {
					run: runner.run,
					runs: 3,
					repository: 'woocommerce/woocommerce',
					workingDirectory: '/tmp/refresh',
				} ),
			/retention/i
		);
	} );
} );

describe( 'formatDriftReport', () => {
	const summary = {
		shardCount: 2,
		modelledTotalMs: 400000,
		actualTotalMs: 500000,
		totalDeviation: 0.25,
		worstShardDeviation: 0.5,
		shards: [
			{ index: 1, modelledMs: 200000, actualMs: 300000, deviation: 0.5 },
			{ index: 2, modelledMs: 200000, actualMs: 200000, deviation: 0 },
		],
		newFiles: [ 'blocks/new.spec.ts' ],
		staleFiles: [],
		drifts: [
			{
				file: 'blocks/slow.spec.ts',
				modelledMs: 100000,
				actualMs: 200000,
				deltaMs: 100000,
			},
		],
	};

	test( 'states the verdict, the worst shard, and how to fix it', () => {
		const report = formatDriftReport( summary, 0.2 );

		assert.match( report, /DRIFTED/ );
		assert.match( report, /50\.0%/ );
		assert.match( report, /blocks\/slow\.spec\.ts/ );
		assert.match( report, /refresh-sharding-manifest/ );
	} );

	test( 'reports a manifest within threshold as current', () => {
		const report = formatDriftReport(
			{ ...summary, worstShardDeviation: 0.05 },
			0.2
		);

		assert.match( report, /within threshold/i );
		assert.doesNotMatch( report, /DRIFTED/ );
	} );

	test( 'surfaces files the manifest does not know about', () => {
		assert.match(
			formatDriftReport( summary, 0.2 ),
			/blocks\/new\.spec\.ts/
		);
	} );
} );

describe( 'mergeMeasuredDurations', () => {
	test( 'averages a file across the runs that measured it', () => {
		const readRun = ( directory ) =>
			new Map(
				directory === '/a'
					? [
							[ 'blocks/a.spec.ts', 100 ],
							[ 'blocks/b.spec.ts', 200 ],
					  ]
					: [
							[ 'blocks/a.spec.ts', 300 ],
							[ 'blocks/b.spec.ts', 400 ],
					  ]
			);

		assert.deepEqual(
			mergeMeasuredDurations(
				[ { path: '/a' }, { path: '/b' } ],
				readRun
			),
			{ 'blocks/a.spec.ts': 200, 'blocks/b.spec.ts': 300 }
		);
	} );

	test( 'does not dilute a spec that only some runs measured', () => {
		const readRun = ( directory ) =>
			new Map(
				directory === '/a'
					? [ [ 'blocks/added-later.spec.ts', 500 ] ]
					: []
			);

		assert.deepEqual(
			mergeMeasuredDurations(
				[ { path: '/a' }, { path: '/b' } ],
				readRun
			),
			{ 'blocks/added-later.spec.ts': 500 }
		);
	} );
} );
