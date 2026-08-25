/**
 * External dependencies
 */
import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';
import { test } from 'node:test';

/**
 * Internal dependencies
 */
import {
	escapeForPlaywrightFilter,
	parseShardPlan,
	planShards,
} from '../../bin/blocks-shard-plan.mjs';

const PLUGIN_ROOT = path.resolve( import.meta.dirname, '../../../..' );

const listSpecFiles = () =>
	readdirSync( path.join( PLUGIN_ROOT, 'tests/e2e/tests/blocks' ), {
		recursive: true,
		withFileTypes: true,
	} )
		.filter(
			( entry ) => entry.isFile() && entry.name.endsWith( '.spec.ts' )
		)
		.map( ( entry ) =>
			path
				.relative(
					PLUGIN_ROOT,
					path.join( entry.parentPath ?? entry.path, entry.name )
				)
				.split( path.sep )
				.join( '/' )
		)
		.sort();

const loadManifest = () =>
	JSON.parse(
		readFileSync(
			path.join( PLUGIN_ROOT, 'tests/e2e/blocks-shard-durations.json' ),
			'utf8'
		)
	);

test( 'assigns every spec file to exactly one shard', () => {
	const files = [ 'a.spec.ts', 'b.spec.ts', 'c.spec.ts', 'd.spec.ts' ];
	const shards = planShards(
		files,
		{
			'a.spec.ts': 400,
			'b.spec.ts': 300,
			'c.spec.ts': 200,
			'd.spec.ts': 100,
		},
		2
	);
	const assigned = shards.flatMap( ( shard ) => shard.files );

	assert.equal( assigned.length, files.length );
	assert.deepEqual( [ ...assigned ].sort(), files );
} );

test( 'balances shards by duration rather than by file count', () => {
	const shards = planShards(
		[ 'heavy.spec.ts', 'a.spec.ts', 'b.spec.ts', 'c.spec.ts' ],
		{
			'heavy.spec.ts': 600,
			'a.spec.ts': 200,
			'b.spec.ts': 200,
			'c.spec.ts': 200,
		},
		2
	);

	assert.deepEqual(
		shards.map( ( shard ) => shard.estimatedMs ),
		[ 600, 600 ]
	);
	// The heavy file gets a shard to itself rather than an even 2/2 split.
	assert.deepEqual( shards[ 0 ].files, [ 'heavy.spec.ts' ] );
} );

test( 'produces the same partition on every call, whatever the input order', () => {
	const durations = { 'a.spec.ts': 300, 'b.spec.ts': 300, 'c.spec.ts': 100 };
	const forwards = planShards(
		[ 'a.spec.ts', 'b.spec.ts', 'c.spec.ts' ],
		durations,
		2
	);
	const backwards = planShards(
		[ 'c.spec.ts', 'b.spec.ts', 'a.spec.ts' ],
		durations,
		2
	);

	assert.deepEqual( forwards, backwards );
} );

test( 'still schedules a spec file that has no recorded duration', () => {
	const shards = planShards(
		[ 'known.spec.ts', 'brand-new.spec.ts' ],
		{ 'known.spec.ts': 500 },
		2,
		250
	);
	const assigned = shards.flatMap( ( shard ) => shard.files );

	assert.ok( assigned.includes( 'brand-new.spec.ts' ) );
	assert.equal( assigned.length, 2 );
} );

test( 'handles more shards than files without dropping any', () => {
	const shards = planShards( [ 'a.spec.ts' ], { 'a.spec.ts': 100 }, 3 );

	assert.equal( shards.length, 3 );
	assert.deepEqual(
		shards.flatMap( ( shard ) => shard.files ),
		[ 'a.spec.ts' ]
	);
} );

test( 'rejects a shard count that is not a positive integer', () => {
	assert.throws( () => planShards( [], {}, 0 ) );
	assert.throws( () => planShards( [], {}, -1 ) );
	assert.throws( () => planShards( [], {}, 1.5 ) );
} );

test( 'parses and validates a shard plan argument', () => {
	assert.deepEqual( parseShardPlan( '3/10' ), { index: 3, total: 10 } );
	assert.throws( () => parseShardPlan( '11/10' ) );
	assert.throws( () => parseShardPlan( '0/10' ) );
	assert.throws( () => parseShardPlan( 'half' ) );
} );

test( 'escapes path characters Playwright would read as a regular expression', () => {
	assert.equal(
		escapeForPlaywrightFilter( 'tests/e2e/tests/blocks/cart.spec.ts' ),
		'tests/e2e/tests/blocks/cart\\.spec\\.ts'
	);
} );

test( 'the committed manifest still covers most of the suite', () => {
	const files = listSpecFiles();
	const { durations } = loadManifest();
	const unmeasured = files.filter( ( file ) => ! ( file in durations ) );

	assert.ok( files.length > 0, 'expected to find Blocks spec files' );

	// A handful of newly added files is fine, since the runner schedules them at
	// the default weight and they still run. This guards against the manifest
	// rotting far enough that the shards drift back out of balance.
	assert.ok(
		unmeasured.length <= Math.ceil( files.length * 0.1 ),
		`${ unmeasured.length } of ${
			files.length
		} spec files have no entry in blocks-shard-durations.json. Refresh it with tests/e2e/bin/update-blocks-shard-durations.mjs:\n${ unmeasured.join(
			'\n'
		) }`
	);
} );

test( 'the real ten-shard plan covers the suite exactly once', () => {
	const files = listSpecFiles();
	const { durations, defaultMs } = loadManifest();
	const shards = planShards( files, durations, 10, defaultMs );
	const assigned = shards.flatMap( ( shard ) => shard.files );

	assert.equal( assigned.length, files.length );
	assert.equal( new Set( assigned ).size, files.length );
} );
