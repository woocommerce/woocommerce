import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { join } from 'node:path';
import { describe, it } from 'node:test';

const BIN = join( import.meta.dirname, '../bin/monorepo-merge.mjs' );
const CONFIRM = join( import.meta.dirname, '../src/confirm.mjs' );

function askTwice( input ) {
	const script = `import { confirm } from ${ JSON.stringify( CONFIRM ) };
		console.log( JSON.stringify( [ await confirm( 'One?' ), await confirm( 'Two?' ) ] ) );`;
	const { stdout, status } = spawnSync(
		process.execPath,
		[ '--input-type=module', '-e', script ],
		// A prompt that never resolves should fail the test rather than hang the suite.
		{ input, encoding: 'utf8', timeout: 10000 }
	);
	assert.equal( status, 0 );

	// Answered prompts print no newline, so the result shares the last line with them.
	return JSON.parse( stdout.match( /\[[^\]]*\]\s*$/ )[ 0 ] );
}

describe( 'confirm', () => {
	it( 'reads piped answers for consecutive prompts', () => {
		assert.deepEqual( askTwice( 'y\nn\n' ), [ true, false ] );
	} );

	it( 'reads a last answer without a trailing newline', () => {
		assert.deepEqual( askTwice( 'y\ny' ), [ true, true ] );
	} );

	it( 'treats the end of input as no', () => {
		assert.deepEqual( askTwice( 'y\n' ), [ true, false ] );
		assert.deepEqual( askTwice( '' ), [ false, false ] );
	} );

	it( 'cancels a command cleanly when input ends at the first prompt', () => {
		const { stderr, status } = spawnSync(
			process.execPath,
			[ BIN, 'transfer-issues', 'woocommerce/example' ],
			{ input: '', encoding: 'utf8', timeout: 10000 }
		);

		assert.equal( status, 1 );
		assert.equal(
			stderr.trim(),
			'Error: Cancelled. Nothing was transferred.'
		);
	} );
} );
