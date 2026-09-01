/* eslint-disable playwright/expect-expect -- Node's assertion module is not recognized by the Playwright lint rule. */
const assert = require( 'node:assert/strict' );
const { describe, test } = require( 'node:test' );

const {
	assertPlanCoversCorpus,
	assignDurationShards,
	collectProjectFiles,
	planDurationShards,
	selectShardFiles,
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

describe( 'Playwright project inventory', () => {
	const report = {
		suites: [
			{
				specs: [
					{
						file: '../fixtures/blocks-setup.ts',
						tests: [ { projectName: 'blocks setup' } ],
					},
					{
						file: 'blocks/z.spec.ts',
						tests: [ { projectName: 'blocks-chromium' } ],
					},
				],
				suites: [
					{
						specs: [
							{
								file: 'blocks\\nested\\a.test.ts',
								tests: [ { projectName: 'blocks-chromium' } ],
							},
						],
					},
				],
			},
		],
	};

	test( 'returns the sorted files Playwright collected for one project', () => {
		assert.deepEqual( collectProjectFiles( report, 'blocks-chromium' ), [
			'blocks/nested/a.test.ts',
			'blocks/z.spec.ts',
		] );
	} );

	test( 'rejects non-array Playwright inventory collections', () => {
		for ( const [ malformedReport, expectedMessage ] of [
			[ { suites: {} }, /suites must be an array/ ],
			[ { suites: [ { specs: {} } ] }, /suite\.specs must be an array/ ],
			[
				{ suites: [ { specs: [ { tests: {} } ] } ] },
				/spec\.tests must be an array/,
			],
		] ) {
			assert.throws(
				() => collectProjectFiles( malformedReport, 'blocks-chromium' ),
				expectedMessage
			);
		}
	} );

	test( 'rejects non-object Playwright inventory entries', () => {
		for ( const [ malformedReport, expectedMessage ] of [
			[ { suites: [ null ] }, /suite entries must be objects/ ],
			[
				{ suites: [ { specs: [ null ] } ] },
				/spec entries must be objects/,
			],
			[
				{ suites: [ { specs: [ { tests: [ null ] } ] } ] },
				/test entries must be objects/,
			],
		] ) {
			assert.throws(
				() => collectProjectFiles( malformedReport, 'blocks-chromium' ),
				expectedMessage
			);
		}
	} );

	test( 'rejects matching Playwright specs without a file path', () => {
		const malformedReport = {
			suites: [
				{
					specs: [
						{
							tests: [ { projectName: 'blocks-chromium' } ],
						},
					],
				},
			],
		};

		assert.throws(
			() => collectProjectFiles( malformedReport, 'blocks-chromium' ),
			/Matching spec\.file must be a non-empty string/
		);
	} );

	test( 'plans the complete collected inventory when the manifest drifts', () => {
		const files = collectProjectFiles( report, 'blocks-chromium' );
		const manifest = {
			schemaVersion: 1,
			fallbackDurationMs: 25,
			files: {
				'blocks/nested/a.test.ts': 100,
				'blocks/z.spec.ts': 50,
			},
		};
		const newFile = files[ 0 ];
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

		assert.equal( driftedManifest.files[ newFile ], undefined );

		const plannedFiles = planDurationShards( {
			files,
			manifest: driftedManifest,
			shardCount: 2,
		} )
			.flatMap( ( shard ) => shard.files )
			.sort();
		assert.deepEqual( plannedFiles, files );
	} );
} );

describe( 'assertPlanCoversCorpus', () => {
	test( 'accepts a plan that partitions the discovered files exactly', () => {
		assert.doesNotThrow( () =>
			assertPlanCoversCorpus(
				[
					{ files: [ 'blocks/a.spec.ts' ] },
					{ files: [ 'blocks/b.spec.ts' ] },
				],
				[ 'blocks/a.spec.ts', 'blocks/b.spec.ts' ]
			)
		);
	} );

	test( 'rejects a plan that would silently drop a discovered file', () => {
		assert.throws(
			() =>
				assertPlanCoversCorpus(
					[ { files: [ 'blocks/a.spec.ts' ] } ],
					[ 'blocks/a.spec.ts', 'blocks/b.spec.ts' ]
				),
			/Missing: blocks\/b\.spec\.ts/
		);
	} );

	test( 'rejects a plan that schedules a file nobody discovered', () => {
		assert.throws(
			() =>
				assertPlanCoversCorpus(
					[
						{
							files: [
								'blocks/a.spec.ts',
								'blocks/ghost.spec.ts',
							],
						},
					],
					[ 'blocks/a.spec.ts' ]
				),
			/Unexpected: blocks\/ghost\.spec\.ts/
		);
	} );

	test( 'rejects a plan that runs the same file in two shards', () => {
		assert.throws(
			() =>
				assertPlanCoversCorpus(
					[
						{ files: [ 'blocks/a.spec.ts' ] },
						{ files: [ 'blocks/a.spec.ts' ] },
					],
					[ 'blocks/a.spec.ts' ]
				),
			/assigns blocks\/a\.spec\.ts to more than one shard/
		);
	} );
} );

describe( 'selectShardFiles', () => {
	const files = [
		'blocks/fast.spec.ts',
		'blocks/medium.spec.ts',
		'blocks/slow.spec.ts',
	];
	const manifest = {
		schemaVersion: 1,
		fallbackDurationMs: 40,
		files: {
			'blocks/fast.spec.ts': 10,
			'blocks/medium.spec.ts': 40,
			'blocks/slow.spec.ts': 100,
		},
	};

	test( 'balances by duration rather than by discovery order', () => {
		const first = selectShardFiles( {
			files,
			manifest,
			shard: { current: 1, total: 2 },
		} );
		const second = selectShardFiles( {
			files,
			manifest,
			shard: { current: 2, total: 2 },
		} );

		assert.equal( first.fallbackReason, null );
		assert.deepEqual( [ ...first.files ], [ 'blocks/slow.spec.ts' ] );
		assert.deepEqual( [ ...second.files ].sort(), [
			'blocks/fast.spec.ts',
			'blocks/medium.spec.ts',
		] );
	} );

	test( 'covers every discovered file exactly once across all shards', () => {
		const planned = [ 1, 2, 3 ].flatMap( ( current ) => [
			...selectShardFiles( {
				files,
				manifest,
				shard: { current, total: 3 },
			} ).files,
		] );

		assert.deepEqual( planned.sort(), [ ...files ].sort() );
	} );

	test( 'selects everything for a single shard, as used by --last-failed retries', () => {
		const selection = selectShardFiles( {
			files,
			manifest,
			shard: { current: 1, total: 1 },
		} );

		assert.deepEqual( [ ...selection.files ].sort(), [ ...files ].sort() );
	} );

	test( 'weights an unrecorded file with the manifest fallback', () => {
		const selection = selectShardFiles( {
			files: [ ...files, 'blocks/brand-new.spec.ts' ],
			manifest,
			shard: { current: 1, total: 2 },
		} );

		assert.equal( selection.fallbackReason, null );
		assert.ok(
			[ 1, 2 ]
				.flatMap( ( current ) => [
					...selectShardFiles( {
						files: [ ...files, 'blocks/brand-new.spec.ts' ],
						manifest,
						shard: { current, total: 2 },
					} ).files,
				] )
				.includes( 'blocks/brand-new.spec.ts' ),
			'A file missing from the manifest must still be scheduled'
		);
	} );

	test( 'reports a fallback reason instead of throwing on an unusable manifest', () => {
		const selection = selectShardFiles( {
			files,
			manifest: { schemaVersion: 99, fallbackDurationMs: 1, files: {} },
			shard: { current: 1, total: 2 },
		} );

		assert.equal( selection.files, null );
		assert.match(
			selection.fallbackReason,
			/Unsupported duration manifest schema/
		);
	} );

	test( 'reports a fallback reason when there are fewer files than shards', () => {
		const selection = selectShardFiles( {
			files: [ 'blocks/only.spec.ts' ],
			manifest,
			shard: { current: 1, total: 10 },
		} );

		assert.equal( selection.files, null );
		assert.match(
			selection.fallbackReason,
			/Shard count cannot exceed the number of weighted files/
		);
	} );
} );
