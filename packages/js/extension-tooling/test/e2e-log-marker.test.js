/**
 * External dependencies
 */
const assert = require( 'node:assert/strict' );
const { spawnSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const test = require( 'node:test' );

test( 'writes the sanitized marker only for the test query parameter', () => {
	const result = spawnSync(
		'php',
		[
			'-d',
			'error_log=',
			path.join( __dirname, 'fixtures/e2e-log-marker-fixture.php' ),
		],
		{ encoding: 'utf8' }
	);

	assert.equal( result.status, 0, result.error?.message ?? result.stderr );
	assert.equal( result.stderr.trim(), 'e2e-log-marker-request-42' );
} );
