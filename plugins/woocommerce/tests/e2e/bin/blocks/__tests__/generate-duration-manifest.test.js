/* eslint-disable playwright/expect-expect -- Node's assertion module is not recognized by the Playwright lint rule. */
const assert = require( 'node:assert/strict' );
const {
	mkdtempSync,
	mkdirSync,
	readFileSync,
	rmSync,
	writeFileSync,
} = require( 'node:fs' );
const { tmpdir } = require( 'node:os' );
const path = require( 'node:path' );
const { describe, test } = require( 'node:test' );
const prettier = require( 'prettier' );

const {
	buildDurationManifest,
	main,
	median,
	parseArguments,
	percentileNearestRank,
	readRunDurations,
} = require( '../generate-duration-manifest' );

function createReportDirectory() {
	return mkdtempSync( path.join( tmpdir(), 'wc-blocks-duration-report-' ) );
}

function writeCtrfReportDocument( directory, name, report ) {
	writeFileSync(
		path.join( directory, `ctrf-report-${ name }.json` ),
		JSON.stringify( report )
	);
}

function writeCtrfReport( directory, name, tests ) {
	writeCtrfReportDocument( directory, name, { results: { tests } } );
}

describe( 'readRunDurations', () => {
	test( 'sums passed Blocks tests across nested CTRF reports', () => {
		const directory = createReportDirectory();
		const nestedDirectory = path.join( directory, 'shard-2' );
		mkdirSync( nestedDirectory );
		writeCtrfReport( directory, 'one', [
			{
				filePath:
					'/home/runner/plugins/woocommerce/tests/e2e/tests/blocks/a.spec.ts',
				duration: 100,
				status: 'passed',
			},
			{
				filePath:
					'/home/runner/plugins/woocommerce/tests/e2e/tests/blocks/a.spec.ts',
				duration: 50,
				status: 'passed',
			},
			{
				filePath:
					'/home/runner/plugins/woocommerce/tests/e2e/tests/core/ignored.spec.ts',
				duration: 999,
				status: 'passed',
			},
		] );
		writeCtrfReport( nestedDirectory, 'two', [
			{
				filePath:
					'C:\\repo\\plugins\\woocommerce\\tests\\e2e\\tests\\blocks\\b.test.ts',
				duration: 200,
				status: 'passed',
			},
			{
				filePath:
					'/home/runner/plugins/woocommerce/tests/e2e/tests/blocks/skipped.spec.ts',
				duration: 10,
				status: 'skipped',
			},
		] );

		try {
			assert.deepEqual(
				[ ...readRunDurations( directory ).entries() ],
				[
					[ 'blocks/a.spec.ts', 150 ],
					[ 'blocks/b.test.ts', 200 ],
				]
			);
		} finally {
			rmSync( directory, { recursive: true, force: true } );
		}
	} );

	test( 'rejects directories without passed Blocks timing data', () => {
		const directory = createReportDirectory();
		try {
			assert.throws(
				() => readRunDurations( directory ),
				/No passed Blocks timings found/
			);
		} finally {
			rmSync( directory, { recursive: true, force: true } );
		}
	} );

	test( 'rejects a non-array CTRF tests collection', () => {
		const directory = createReportDirectory();
		writeCtrfReportDocument( directory, 'invalid-collection', {
			results: { tests: {} },
		} );

		try {
			assert.throws(
				() => readRunDurations( directory ),
				/Invalid CTRF report .*ctrf-report-invalid-collection\.json: results\.tests must be an array/
			);
		} finally {
			rmSync( directory, { recursive: true, force: true } );
		}
	} );

	test( 'rejects non-object CTRF test entries', () => {
		const directory = createReportDirectory();
		writeCtrfReport( directory, 'invalid-entry', [ null ] );

		try {
			assert.throws(
				() => readRunDurations( directory ),
				/Invalid CTRF report .*ctrf-report-invalid-entry\.json: results\.tests entries must be objects/
			);
		} finally {
			rmSync( directory, { recursive: true, force: true } );
		}
	} );
} );

describe( 'duration statistics', () => {
	test( 'uses the midpoint median and nearest-rank percentile', () => {
		assert.equal( median( [ 300, 100, 400, 200 ] ), 250 );
		assert.equal( percentileNearestRank( [ 10, 20, 30, 40 ], 0.75 ), 30 );
	} );
} );

describe( 'buildDurationManifest', () => {
	test( 'uses multi-run medians, P75 fallback, and the current inventory', () => {
		const manifest = buildDurationManifest( {
			currentFiles: [
				'blocks/new.spec.ts',
				'blocks/b.spec.ts',
				'blocks/a.spec.ts',
			],
			runs: [
				{
					id: 3,
					durations: new Map( [
						[ 'blocks/a.spec.ts', 200 ],
						[ 'blocks/b.spec.ts', 30 ],
						[ 'blocks/deleted.spec.ts', 500 ],
					] ),
				},
				{
					id: 1,
					durations: new Map( [
						[ 'blocks/a.spec.ts', 100 ],
						[ 'blocks/b.spec.ts', 10 ],
					] ),
				},
				{
					id: 2,
					durations: new Map( [
						[ 'blocks/a.spec.ts', 300 ],
						[ 'blocks/b.spec.ts', 20 ],
					] ),
				},
			],
		} );

		assert.deepEqual( manifest, {
			schemaVersion: 1,
			sourceRuns: [ 1, 2, 3 ],
			fallbackDurationMs: 200,
			files: {
				'blocks/a.spec.ts': 200,
				'blocks/b.spec.ts': 20,
				'blocks/new.spec.ts': 200,
			},
		} );
	} );

	test( 'requires three unique runs with current timing coverage', () => {
		const run = {
			id: 1,
			durations: new Map( [ [ 'blocks/a.spec.ts', 100 ] ] ),
		};
		assert.throws(
			() =>
				buildDurationManifest( {
					currentFiles: [ 'blocks/a.spec.ts' ],
					runs: [ run, { ...run, id: 2 } ],
				} ),
			/At least three distinct source runs with passed Blocks timings are required to calculate median durations/
		);
		assert.throws(
			() =>
				buildDurationManifest( {
					currentFiles: [ 'blocks/a.spec.ts' ],
					runs: [ run, { ...run }, { ...run, id: 2 } ],
				} ),
			/Duplicate source run: 1/
		);
		assert.throws(
			() =>
				buildDurationManifest( {
					currentFiles: [ 'blocks/new.spec.ts' ],
					runs: [ run, { ...run, id: 2 }, { ...run, id: 3 } ],
				} ),
			/No current files have timing data/
		);
	} );
} );

describe( 'parseArguments', () => {
	test( 'parses repeated source runs and an output path', () => {
		assert.deepEqual(
			parseArguments( [
				'--run',
				'3=/reports/three',
				'--run=1=/reports/one',
				'--run',
				'2=/reports/two',
				'--output',
				'/output/manifest.json',
			] ),
			{
				runs: [
					{ id: 1, path: '/reports/one' },
					{ id: 2, path: '/reports/two' },
					{ id: 3, path: '/reports/three' },
				],
				outputPath: '/output/manifest.json',
			}
		);
	} );

	test( 'rejects malformed arguments and fewer than three runs', () => {
		assert.throws(
			() => parseArguments( [ '--run', 'invalid' ] ),
			/Run must use ID=PATH/
		);
		assert.throws(
			() => parseArguments( [ '--run', '1=/one', '--run', '2=/two' ] ),
			/Expected at least three distinct successful source runs to calculate median durations\. Pass each run as --run RUN_ID=CTRF_REPORT_DIRECTORY/
		);
		assert.throws(
			() =>
				parseArguments( [
					'--run',
					'1=/one',
					'--run',
					'1=/duplicate',
					'--run',
					'2=/two',
				] ),
			/Duplicate source run: 1/
		);
	} );
} );

describe( 'main', () => {
	test( 'writes a formatted manifest for the current Playwright inventory', () => {
		const directory = createReportDirectory();
		const outputPath = path.join( directory, 'manifest.json' );
		const arguments_ = [];

		try {
			for ( const [ id, duration ] of [
				[ 3, 300 ],
				[ 1, 100 ],
				[ 2, 200 ],
			] ) {
				const runDirectory = path.join( directory, `run-${ id }` );
				mkdirSync( runDirectory );
				writeCtrfReport( runDirectory, `${ id }`, [
					{
						filePath:
							'/repo/plugins/woocommerce/tests/e2e/tests/blocks/a.spec.ts',
						duration,
						status: 'passed',
					},
				] );
				arguments_.push( '--run', `${ id }=${ runDirectory }` );
			}
			arguments_.push( '--output', outputPath );

			main( arguments_, () => [
				'blocks/new.spec.ts',
				'blocks/a.spec.ts',
			] );

			const output = readFileSync( outputPath, 'utf8' );
			assert.equal(
				output,
				prettier.format( output, {
					...prettier.resolveConfig.sync( __filename ),
					parser: 'json',
				} )
			);
			assert.deepEqual( JSON.parse( output ), {
				schemaVersion: 1,
				sourceRuns: [ 1, 2, 3 ],
				fallbackDurationMs: 200,
				files: {
					'blocks/a.spec.ts': 200,
					'blocks/new.spec.ts': 200,
				},
			} );
		} finally {
			rmSync( directory, { recursive: true, force: true } );
		}
	} );
} );
