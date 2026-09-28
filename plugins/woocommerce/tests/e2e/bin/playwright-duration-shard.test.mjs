/**
 * External dependencies
 */
import assert from 'node:assert/strict';
import { test } from 'node:test';

/**
 * Internal dependencies
 */
import { packByDuration, parseArgs } from './playwright-duration-shard.mjs';

const files = ( bins ) => bins.map( ( bin ) => bin.files );

test( 'packs the longest files first onto the least loaded shard', () => {
	const durations = { a: 70, b: 50, c: 40, d: 30, e: 10 };
	const bins = packByDuration( Object.keys( durations ), durations, 2 );

	assert.deepEqual( files( bins ), [
		[ 'a', 'd' ],
		[ 'b', 'c', 'e' ],
	] );
	assert.deepEqual(
		bins.map( ( bin ) => bin.load ),
		[ 100, 100 ]
	);
} );

test( 'weighs files missing from the durations at the median', () => {
	const bins = packByDuration(
		[ 'new', 'a', 'b', 'c', 'd' ],
		{ a: 100, b: 30, c: 20, d: 10 },
		2
	);

	assert.deepEqual( files( bins ), [ [ 'a' ], [ 'b', 'new', 'c', 'd' ] ] );
	assert.equal( bins[ 1 ].load, 85 );
} );

test( 'assigns the same files regardless of input order', () => {
	const durations = { a: 5, b: 5, c: 5, d: 5 };
	const forward = packByDuration( [ 'a', 'b', 'c', 'd' ], durations, 3 );
	const backward = packByDuration( [ 'd', 'c', 'b', 'a' ], durations, 3 );

	assert.deepEqual( files( forward ), files( backward ) );
	assert.deepEqual( files( forward ), [ [ 'a', 'd' ], [ 'b' ], [ 'c' ] ] );
} );

test( 'spreads files evenly without durations and leaves spare shards empty', () => {
	assert.deepEqual( files( packByDuration( [ 'c', 'b', 'a' ], {}, 2 ) ), [
		[ 'a', 'c' ],
		[ 'b' ],
	] );
	assert.deepEqual( files( packByDuration( [ 'a' ], { a: 1 }, 3 ) ), [
		[ 'a' ],
		[],
		[],
	] );
} );

test( 'takes the last --shard and strips every one from the arguments', () => {
	assert.deepEqual(
		parseArgs( [
			'--project=blocks-chromium',
			'--shard=3/10',
			'--last-failed',
			'--shard',
			'1/1',
		] ),
		{
			shard: { current: 1, total: 1 },
			project: 'blocks-chromium',
			rest: [ '--project=blocks-chromium', '--last-failed' ],
		}
	);
} );

test( 'reads the project only from a single --project=<name>', () => {
	assert.equal( parseArgs( [ '--project=a', '--project=b' ] ).project, null );
	assert.equal( parseArgs( [ '--project', 'a' ] ).project, null );
} );
