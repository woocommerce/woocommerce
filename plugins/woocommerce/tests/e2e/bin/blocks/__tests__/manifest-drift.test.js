/* eslint-disable playwright/expect-expect -- Node's assertion module is not recognized by the Playwright lint rule. */
const assert = require( 'node:assert/strict' );
const { describe, test } = require( 'node:test' );

const { summarizeManifestDrift } = require( '../duration-sharding' );

const manifest = {
	schemaVersion: 1,
	fallbackDurationMs: 100,
	files: {
		'blocks/a.spec.ts': 100,
		'blocks/b.spec.ts': 100,
		'blocks/c.spec.ts': 100,
		'blocks/d.spec.ts': 100,
	},
};

describe( 'summarizeManifestDrift', () => {
	test( 'reports no drift when the manifest still predicts reality', () => {
		const summary = summarizeManifestDrift( {
			manifest,
			actualDurations: {
				'blocks/a.spec.ts': 100,
				'blocks/b.spec.ts': 100,
				'blocks/c.spec.ts': 100,
				'blocks/d.spec.ts': 100,
			},
			shardCount: 2,
		} );

		assert.equal( summary.totalDeviation, 0 );
		assert.equal( summary.worstShardDeviation, 0 );
		assert.deepEqual( summary.drifts, [] );
	} );

	test( 'measures deviation per shard, not just overall', () => {
		// a and b land in different shards, so doubling only a skews one shard
		// while the overall total moves half as much.
		const summary = summarizeManifestDrift( {
			manifest,
			actualDurations: {
				'blocks/a.spec.ts': 200,
				'blocks/b.spec.ts': 100,
				'blocks/c.spec.ts': 100,
				'blocks/d.spec.ts': 100,
			},
			shardCount: 2,
		} );

		assert.equal( summary.modelledTotalMs, 400 );
		assert.equal( summary.actualTotalMs, 500 );
		assert.equal( summary.totalDeviation, 0.25 );
		assert.equal( summary.worstShardDeviation, 0.5 );
		assert.equal( summary.shards.length, 2 );
	} );

	test( 'ranks the biggest absolute drifts first and keeps their direction', () => {
		const summary = summarizeManifestDrift( {
			manifest,
			actualDurations: {
				'blocks/a.spec.ts': 100,
				'blocks/b.spec.ts': 160,
				'blocks/c.spec.ts': 40,
				'blocks/d.spec.ts': 400,
			},
			shardCount: 2,
		} );

		assert.deepEqual(
			summary.drifts.map( ( drift ) => drift.file ),
			[ 'blocks/d.spec.ts', 'blocks/b.spec.ts', 'blocks/c.spec.ts' ]
		);
		assert.equal( summary.drifts[ 0 ].deltaMs, 300 );
		assert.equal( summary.drifts[ 2 ].deltaMs, -60 );
	} );

	test( 'separates files the manifest has never seen from ones it has outlived', () => {
		const summary = summarizeManifestDrift( {
			manifest,
			actualDurations: {
				'blocks/a.spec.ts': 100,
				'blocks/b.spec.ts': 100,
				'blocks/c.spec.ts': 100,
				'blocks/brand-new.spec.ts': 100,
			},
			shardCount: 2,
		} );

		assert.deepEqual( summary.newFiles, [ 'blocks/brand-new.spec.ts' ] );
		assert.deepEqual( summary.staleFiles, [ 'blocks/d.spec.ts' ] );
	} );

	test( 'plans a new file with the fallback weight rather than ignoring it', () => {
		const summary = summarizeManifestDrift( {
			manifest,
			actualDurations: {
				'blocks/a.spec.ts': 100,
				'blocks/b.spec.ts': 100,
				'blocks/c.spec.ts': 100,
				'blocks/d.spec.ts': 100,
				'blocks/brand-new.spec.ts': 100,
			},
			shardCount: 5,
		} );

		assert.equal( summary.modelledTotalMs, 500 );
		assert.equal( summary.worstShardDeviation, 0 );
	} );

	test( 'rejects an empty measurement rather than reporting a clean bill', () => {
		assert.throws(
			() =>
				summarizeManifestDrift( {
					manifest,
					actualDurations: {},
					shardCount: 2,
				} ),
			/No measured durations/
		);
	} );
} );
