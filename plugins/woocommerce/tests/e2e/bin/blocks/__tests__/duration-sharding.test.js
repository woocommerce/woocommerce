/* eslint-disable playwright/expect-expect -- Node's assertion module is not recognized by the Playwright lint rule. */
const assert = require( 'node:assert/strict' );
const { mkdtempSync, mkdirSync, rmSync, writeFileSync } = require( 'node:fs' );
const { tmpdir } = require( 'node:os' );
const path = require( 'node:path' );
const { describe, test } = require( 'node:test' );

const {
	assignDurationShards,
	discoverBlocksSpecs,
	parseDurationShard,
	planDurationShards,
	validateDurationManifest,
} = require( '../duration-sharding' );

describe( 'assignDurationShards', () => {
	test( 'places the longest files into the currently lightest shard', () => {
		const shards = assignDurationShards(
			[
				{ file: 'a.spec.ts', durationMs: 10 },
				{ file: 'b.spec.ts', durationMs: 9 },
				{ file: 'c.spec.ts', durationMs: 8 },
				{ file: 'd.spec.ts', durationMs: 7 },
			],
			2
		);

		assert.deepEqual( shards, [
			{
				index: 1,
				durationMs: 17,
				files: [ 'a.spec.ts', 'd.spec.ts' ],
			},
			{
				index: 2,
				durationMs: 17,
				files: [ 'b.spec.ts', 'c.spec.ts' ],
			},
		] );
	} );

	test( 'uses file path and shard number as stable tie-breakers', () => {
		const shards = assignDurationShards(
			[
				{ file: 'c.spec.ts', durationMs: 5 },
				{ file: 'a.spec.ts', durationMs: 5 },
				{ file: 'b.spec.ts', durationMs: 5 },
			],
			2
		);

		assert.deepEqual( shards, [
			{
				index: 1,
				durationMs: 10,
				files: [ 'a.spec.ts', 'c.spec.ts' ],
			},
			{
				index: 2,
				durationMs: 5,
				files: [ 'b.spec.ts' ],
			},
		] );
	} );

	test( 'uses locale-independent code-point ordering for file ties', () => {
		const shards = assignDurationShards(
			[
				{ file: 'a.spec.ts', durationMs: 5 },
				{ file: 'A.spec.ts', durationMs: 5 },
			],
			1
		);

		assert.deepEqual( shards[ 0 ].files, [ 'A.spec.ts', 'a.spec.ts' ] );
	} );

	test( 'assigns every file exactly once without mutating the input', () => {
		const weightedFiles = [
			{ file: 'slow.spec.ts', durationMs: 30 },
			{ file: 'medium.spec.ts', durationMs: 20 },
			{ file: 'fast.spec.ts', durationMs: 10 },
		];
		const original = structuredClone( weightedFiles );

		const shards = assignDurationShards( weightedFiles, 2 );
		const assignedFiles = shards.flatMap( ( shard ) => shard.files ).sort();

		assert.deepEqual( assignedFiles, [
			'fast.spec.ts',
			'medium.spec.ts',
			'slow.spec.ts',
		] );
		assert.deepEqual( weightedFiles, original );
	} );

	test( 'rejects invalid inventories and shard counts', () => {
		assert.throws(
			() => assignDurationShards( [], 2 ),
			/At least one weighted file is required/
		);
		assert.throws(
			() =>
				assignDurationShards(
					[
						{ file: 'duplicate.spec.ts', durationMs: 10 },
						{ file: 'duplicate.spec.ts', durationMs: 20 },
					],
					2
				),
			/Duplicate weighted file: duplicate\.spec\.ts/
		);
		assert.throws(
			() =>
				assignDurationShards(
					[ { file: 'invalid.spec.ts', durationMs: 0 } ],
					2
				),
			/Duration must be a positive number for invalid\.spec\.ts/
		);
		assert.throws(
			() =>
				assignDurationShards(
					[ { file: 'valid.spec.ts', durationMs: 10 } ],
					0
				),
			/Shard count must be a positive integer/
		);
		assert.throws(
			() =>
				assignDurationShards(
					[ { file: 'valid.spec.ts', durationMs: 10 } ],
					2
				),
			/Shard count cannot exceed the number of weighted files/
		);
	} );
} );

describe( 'parseDurationShard', () => {
	test( 'parses the first and last configured shards', () => {
		assert.deepEqual( parseDurationShard( '1/10' ), {
			index: 1,
			count: 10,
		} );
		assert.deepEqual( parseDurationShard( '10/10' ), {
			index: 10,
			count: 10,
		} );
	} );

	test( 'rejects malformed, out-of-range, and unexpected shard counts', () => {
		for ( const value of [ '1', '0/10', '11/10', 'one/10', '1/9' ] ) {
			assert.throws(
				() => parseDurationShard( value ),
				/Duration shard must use N\/10 with N between 1 and 10/
			);
		}
	} );
} );

describe( 'duration manifest', () => {
	const validManifest = {
		schemaVersion: 1,
		sourceRuns: [ 101, 102, 103 ],
		fallbackDurationMs: 25,
		files: {
			'blocks/known.spec.ts': 100,
			'blocks/stale.spec.ts': 200,
		},
	};

	test( 'validates the supported schema and positive durations', () => {
		assert.doesNotThrow( () => validateDurationManifest( validManifest ) );
		assert.throws(
			() =>
				validateDurationManifest( {
					...validManifest,
					schemaVersion: 2,
				} ),
			/Unsupported duration manifest schema: 2/
		);
		assert.throws(
			() =>
				validateDurationManifest( {
					...validManifest,
					fallbackDurationMs: 0,
				} ),
			/Manifest fallbackDurationMs must be a positive number/
		);
		assert.throws(
			() =>
				validateDurationManifest( {
					...validManifest,
					files: { 'blocks/known.spec.ts': -1 },
				} ),
			/Manifest duration must be positive for blocks\/known\.spec\.ts/
		);
	} );

	test( 'uses fallback weights for new files and ignores stale entries', () => {
		const shards = planDurationShards( {
			files: [ 'blocks/known.spec.ts', 'blocks/new.spec.ts' ],
			manifest: validManifest,
			shardCount: 2,
		} );

		assert.deepEqual( shards, [
			{
				index: 1,
				durationMs: 100,
				files: [ 'blocks/known.spec.ts' ],
			},
			{
				index: 2,
				durationMs: 25,
				files: [ 'blocks/new.spec.ts' ],
			},
		] );
	} );
} );

describe( 'discoverBlocksSpecs', () => {
	test( 'returns sorted POSIX spec paths and excludes non-spec files', () => {
		const fixtureRoot = mkdtempSync(
			path.join( tmpdir(), 'wc-duration-shards-' )
		);
		mkdirSync( path.join( fixtureRoot, 'blocks', 'nested' ), {
			recursive: true,
		} );
		writeFileSync( path.join( fixtureRoot, 'blocks', 'z.spec.ts' ), '' );
		writeFileSync(
			path.join( fixtureRoot, 'blocks', 'nested', 'a.spec.ts' ),
			''
		);
		writeFileSync(
			path.join( fixtureRoot, 'blocks', 'nested', 'helper.ts' ),
			''
		);

		try {
			assert.deepEqual( discoverBlocksSpecs( fixtureRoot ), [
				'blocks/nested/a.spec.ts',
				'blocks/z.spec.ts',
			] );
		} finally {
			rmSync( fixtureRoot, { recursive: true, force: true } );
		}
	} );

	test( 'plans the complete current inventory when the manifest drifts', () => {
		const testsRoot = path.resolve( __dirname, '../../../tests' );
		const manifest = require( '../block-test-durations.json' );
		const files = discoverBlocksSpecs( testsRoot );
		const newFile = files.find( ( file ) =>
			Object.hasOwn( manifest.files, file )
		);
		assert.ok( newFile, 'Expected at least one measured current spec' );
		const driftedManifest = {
			...manifest,
			files: {
				...Object.fromEntries(
					Object.entries( manifest.files ).filter(
						( [ file ] ) => file !== newFile
					)
				),
				'blocks/deleted-stale.spec.ts': 1,
			},
		};

		validateDurationManifest( manifest );
		assert.deepEqual(
			manifest.sourceRuns,
			[ 29257210019, 29258795060, 29256225917 ]
		);
		assert.equal( driftedManifest.files[ newFile ], undefined );

		const plannedFiles = planDurationShards( {
			files,
			manifest: driftedManifest,
			shardCount: 10,
		} )
			.flatMap( ( shard ) => shard.files )
			.sort();
		assert.deepEqual( plannedFiles, files );
	} );
} );
