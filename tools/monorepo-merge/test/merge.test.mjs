import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { join } from 'node:path';
import { describe, it } from 'node:test';

import mergeModule from '../dist/commands/merge/index.js';
import constants from '../dist/const.js';

const { default: Merge } = mergeModule;
const { MONOREPO_ROOT } = constants;

const SOURCE = 'woocommerce/example';
const DESTINATION_ERROR =
	'The "destination" argument must point to a path inside the monorepo';

function rewriteMessage( message ) {
	const script = [
		'import re',
		'import sys',
		'def rewrite(message):',
		`    ${ Merge.createMessageCallback( SOURCE ) }`,
		'sys.stdout.buffer.write(rewrite(sys.stdin.buffer.read()))',
	].join( '\n' );

	return execFileSync( 'python3', [ '-c', script ], {
		input: message,
		encoding: 'utf8',
	} );
}

function createCommand() {
	const command = Object.create( Merge.prototype );
	command.error = ( message ) => {
		throw new Error( message );
	};

	return command;
}

describe( 'destination validation', () => {
	it( 'accepts a nested path inside the monorepo', async () => {
		await assert.doesNotReject( () =>
			createCommand().validateArgs(
				SOURCE,
				'tools/__monorepo_merge_test__'
			)
		);
	} );

	it( 'rejects an absolute path', async () => {
		await assert.rejects(
			createCommand().validateArgs(
				SOURCE,
				join( MONOREPO_ROOT, 'tools/example' )
			),
			{ message: DESTINATION_ERROR }
		);
	} );

	it( 'rejects the monorepo root', async () => {
		await assert.rejects( createCommand().validateArgs( SOURCE, '.' ), {
			message: DESTINATION_ERROR,
		} );
	} );

	it( 'rejects a path that traverses outside the monorepo', async () => {
		await assert.rejects(
			createCommand().validateArgs( SOURCE, '../outside' ),
			{ message: DESTINATION_ERROR }
		);
	} );

	it( 'rejects a sibling path with the monorepo name as its prefix', async () => {
		await assert.rejects(
			createCommand().validateArgs( SOURCE, '../woocommerce-copy' ),
			{ message: DESTINATION_ERROR }
		);
	} );
} );

describe( 'commit message rewriting', () => {
	it( 'rewrites parenthesized references as pull request links', () => {
		assert.equal(
			rewriteMessage( 'Fix the regression (#123).' ),
			'Fix the regression (https://github.com/woocommerce/example/pull/123).'
		);
	} );

	it( 'qualifies bare issue references with the source repository', () => {
		assert.equal(
			rewriteMessage( 'Follow up in #456.' ),
			'Follow up in woocommerce/example#456.'
		);
	} );

	it( 'rewrites adjacent valid references', () => {
		assert.equal(
			rewriteMessage( '(#12)(#34) #56#78' ),
			'(https://github.com/woocommerce/example/pull/12)' +
				'(https://github.com/woocommerce/example/pull/34) ' +
				'woocommerce/example#56woocommerce/example#78'
		);
	} );

	it( 'leaves malformed references unchanged', () => {
		const message = 'Malformed: (#), (#abc), (#12, and #.';

		assert.equal( rewriteMessage( message ), message );
	} );

	it( 'leaves nonmatching text unchanged', () => {
		const message = 'A commit message without issue references.';

		assert.equal( rewriteMessage( message ), message );
	} );
} );
