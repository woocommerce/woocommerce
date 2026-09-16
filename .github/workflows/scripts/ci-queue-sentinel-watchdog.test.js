const assert = require( 'node:assert/strict' );
const test = require( 'node:test' );

const { findStaleRuns } = require( './ci-queue-sentinel-watchdog' );

const nowMs = Date.parse( '2026-09-01T10:33:13Z' );
const minutesAgo = ( minutes ) => new Date( nowMs - minutes * 60000 ).toISOString();
const run = ( id, status, startedMinutesAgo, extra = {} ) => ( {
	id,
	status,
	html_url: `https://github.com/woocommerce/woocommerce/actions/runs/${ id }`,
	created_at: minutesAgo( startedMinutesAgo ),
	run_started_at: minutesAgo( startedMinutesAgo ),
	...extra,
} );

test( 'flags a run that has been in progress longer than the stale window', () => {
	// Run 33496275435 sat in_progress with no runner from 1 Sep 10:13 UTC.
	const stale = findStaleRuns( [ run( 33496275435, 'in_progress', 20 ) ], nowMs, 12, '99' );

	assert.equal( stale.length, 1 );
	assert.equal( stale[ 0 ].id, 33496275435 );
	assert.equal( stale[ 0 ].status, 'in_progress' );
	assert.equal( Math.round( stale[ 0 ].ageMin ), 20 );
	assert.equal( stale[ 0 ].html_url, 'https://github.com/woocommerce/woocommerce/actions/runs/33496275435' );
} );

test( 'flags a queued run past the stale window', () => {
	const stale = findStaleRuns( [ run( 1, 'queued', 13 ) ], nowMs, 12, '99' );

	assert.deepEqual( stale.map( ( r ) => r.id ), [ 1 ] );
} );

test( 'ignores runs inside the stale window', () => {
	const runs = [ run( 1, 'in_progress', 12 ), run( 2, 'queued', 0.5 ), run( 3, 'in_progress', 11.9 ) ];

	assert.deepEqual( findStaleRuns( runs, nowMs, 12, '99' ), [] );
} );

test( 'never flags the current run, whatever its age', () => {
	const runs = [ run( 42, 'in_progress', 60 ) ];

	assert.deepEqual( findStaleRuns( runs, nowMs, 12, '42' ), [] );
	assert.deepEqual( findStaleRuns( runs, nowMs, 12, 42 ), [] );
	assert.equal( findStaleRuns( runs, nowMs, 12, '43' ).length, 1 );
} );

test( 'ignores runs that are not queued or in progress', () => {
	const runs = [ run( 1, 'completed', 60 ), run( 2, 'waiting', 60 ), run( 3, 'pending', 60 ) ];

	assert.deepEqual( findStaleRuns( runs, nowMs, 12, '99' ), [] );
} );

test( 'measures a re-run from its latest attempt, not from creation', () => {
	const rerun = run( 1, 'in_progress', 2, { created_at: minutesAgo( 60 * 24 ) } );
	const noStart = run( 2, 'queued', 30, { run_started_at: undefined } );

	assert.deepEqual( findStaleRuns( [ rerun ], nowMs, 12, '99' ), [] );
	assert.deepEqual( findStaleRuns( [ noStart ], nowMs, 12, '99' ).map( ( r ) => r.id ), [ 2 ] );
} );

test( 'returns stale runs oldest first', () => {
	const runs = [ run( 1, 'queued', 15 ), run( 2, 'in_progress', 400 ), run( 3, 'queued', 30 ) ];

	assert.deepEqual( findStaleRuns( runs, nowMs, 12, '99' ).map( ( r ) => r.id ), [ 2, 3, 1 ] );
} );

test( 'returns nothing for an empty list', () => {
	assert.deepEqual( findStaleRuns( [], nowMs, 12, '99' ), [] );
} );
