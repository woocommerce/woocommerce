const assert = require( 'node:assert/strict' );
const test = require( 'node:test' );

const { evaluateFreshness } = require( './ci-queue-sentinel-freshness' );

const nowMs = Date.parse( '2026-09-15T11:00:00Z' );
const minutesAgo = ( minutes ) => new Date( nowMs - minutes * 60000 ).toISOString();
const run = ( id, conclusion, finishedMinutesAgo, extra = {} ) => ( {
	id,
	status: 'completed',
	conclusion,
	html_url: `https://github.com/woocommerce/woocommerce/actions/runs/${ id }`,
	created_at: minutesAgo( finishedMinutesAgo + 1 ),
	run_started_at: minutesAgo( finishedMinutesAgo + 1 ),
	updated_at: minutesAgo( finishedMinutesAgo ),
	...extra,
} );

test( 'a recent success is fresh', () => {
	const result = evaluateFreshness( [ run( 1, 'success', 14 ) ], nowMs, 60, 15, '99' );

	assert.equal( result.stale, false );
	assert.equal( result.alert, false );
	assert.equal( result.lastSuccess.id, 1 );
	assert.equal( Math.round( result.ageMin ), 14 );
} );

test( 'silence past the window is stale and alerts', () => {
	// The 1 Sep wedge: the last good run was 33494158683, and every tick after
	// it was cancelled while the stuck run held the group.
	const runs = [ run( 33494158683, 'success', 61 ), run( 2, 'cancelled', 1 ) ];
	const result = evaluateFreshness( runs, nowMs, 60, 15, '99' );

	assert.equal( result.stale, true );
	assert.equal( result.alert, true );
	assert.equal( result.lastSuccess.id, 33494158683 );
} );

test( 'cancelled and failed runs never count as freshness', () => {
	const runs = [ run( 1, 'cancelled', 2 ), run( 2, 'failure', 3 ), run( 3, 'skipped', 4 ) ];

	assert.deepEqual( evaluateFreshness( runs, nowMs, 60, 15, '99' ), {
		stale: true,
		alert: true,
		ageMin: null,
		lastSuccess: null,
	} );
} );

test( 'the window boundary is not stale', () => {
	assert.equal( evaluateFreshness( [ run( 1, 'success', 60 ) ], nowMs, 60, 15, '99' ).stale, false );
	assert.equal( evaluateFreshness( [ run( 1, 'success', 60.1 ) ], nowMs, 60, 15, '99' ).stale, true );
} );

test( 'alerts at the top of a window rather than on every tick', () => {
	const alertsAt = ( ageMin ) => evaluateFreshness( [ run( 1, 'success', ageMin ) ], nowMs, 60, 15, '99' ).alert;

	// On-time ticks: the first two past the hour alert, the rest of the window
	// stays quiet, and the next window alerts again.
	assert.equal( alertsAt( 61 ), true );
	assert.equal( alertsAt( 76 ), true );
	assert.equal( alertsAt( 91 ), false );
	assert.equal( alertsAt( 106 ), false );
	assert.equal( alertsAt( 121 ), true );
	assert.equal( alertsAt( 151 ), false );
} );

test( 'a late tick still alerts on its first stale run', () => {
	const alertsAt = ( ageMin ) => evaluateFreshness( [ run( 1, 'success', ageMin ) ], nowMs, 60, 15, '99' ).alert;

	// Scheduled runs drift, so the previous tick cannot be assumed to sit one
	// cadence back. A first stale tick anywhere in the first two cadences of
	// the window still alerts, including one that skipped a whole window.
	assert.equal( alertsAt( 75 ), true );
	assert.equal( alertsAt( 79 ), true );
	assert.equal( alertsAt( 89 ), true );
	assert.equal( alertsAt( 130 ), true );
} );

test( 'a stale run still reports stale on the quiet ticks', () => {
	const result = evaluateFreshness( [ run( 1, 'success', 90 ) ], nowMs, 60, 15, '99' );

	assert.equal( result.stale, true );
	assert.equal( result.alert, false );
} );

test( 'measures from the newest success, whatever the list order', () => {
	const runs = [ run( 1, 'success', 200 ), run( 2, 'success', 5 ), run( 3, 'success', 90 ) ];
	const result = evaluateFreshness( runs, nowMs, 60, 15, '99' );

	assert.equal( result.lastSuccess.id, 2 );
	assert.equal( result.stale, false );
} );

test( 'ignores the current run, which cannot have succeeded yet', () => {
	const runs = [ run( 42, 'success', 0 ), run( 1, 'success', 200 ) ];

	assert.equal( evaluateFreshness( runs, nowMs, 60, 15, '42' ).lastSuccess.id, 1 );
	assert.equal( evaluateFreshness( runs, nowMs, 60, 15, 42 ).stale, true );
	assert.equal( evaluateFreshness( runs, nowMs, 60, 15, '43' ).stale, false );
} );

test( 'falls back to run_started_at when a run has no updated_at', () => {
	const noUpdate = run( 1, 'success', 90, { updated_at: undefined } );

	// run_started_at is a minute older than updated_at in the fixture.
	assert.equal( Math.round( evaluateFreshness( [ noUpdate ], nowMs, 60, 15, '99' ).ageMin ), 91 );
} );

test( 'an empty list alerts', () => {
	assert.deepEqual( evaluateFreshness( [], nowMs, 60, 15, '99' ), {
		stale: true,
		alert: true,
		ageMin: null,
		lastSuccess: null,
	} );
} );
