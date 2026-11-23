import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { join } from 'node:path';
import { describe, it } from 'node:test';

import {
	MONOREPO_ROOT,
	createMessageCallback,
	validateArgs,
} from '../src/merge.mjs';

const SOURCE = 'woocommerce/example';
const DESTINATION_ERROR =
	'The "destination" argument must point to a path inside the monorepo';

function rewriteMessage( message ) {
	const script = [
		'import re',
		'import sys',
		'def rewrite(message):',
		`    ${ createMessageCallback( SOURCE ) }`,
		'sys.stdout.buffer.write(rewrite(sys.stdin.buffer.read()))',
	].join( '\n' );

	return execFileSync( 'python3', [ '-c', script ], {
		input: message,
		encoding: 'utf8',
	} );
}

describe( 'source validation', () => {
	it( 'accepts a repository name with dots', async () => {
		await assert.doesNotReject( () =>
			validateArgs(
				'woocommerce/example.com',
				'tools/__monorepo_merge_test__'
			)
		);
	} );

	it( 'rejects a repository name made only of dots', async () => {
		await assert.rejects(
			validateArgs( 'woocommerce/..', 'tools/__monorepo_merge_test__' ),
			{
				message:
					'The "source" argument must be in "organization/repository" format',
			}
		);
	} );
} );

describe( 'destination validation', () => {
	it( 'accepts a nested path and returns it relative to the monorepo', async () => {
		assert.equal(
			await validateArgs( SOURCE, './tools/__monorepo_merge_test__/' ),
			'tools/__monorepo_merge_test__'
		);
	} );

	it( 'rejects an absolute path', async () => {
		await assert.rejects(
			validateArgs( SOURCE, join( MONOREPO_ROOT, 'tools/example' ) ),
			{ message: DESTINATION_ERROR }
		);
	} );

	it( 'rejects the monorepo root', async () => {
		await assert.rejects( validateArgs( SOURCE, '.' ), {
			message: DESTINATION_ERROR,
		} );
	} );

	it( 'rejects a path that traverses outside the monorepo', async () => {
		await assert.rejects( validateArgs( SOURCE, '../outside' ), {
			message: DESTINATION_ERROR,
		} );
	} );

	it( 'rejects a sibling path with the monorepo name as its prefix', async () => {
		await assert.rejects( validateArgs( SOURCE, '../woocommerce-copy' ), {
			message: DESTINATION_ERROR,
		} );
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
			rewriteMessage( '(#12)(#34) #56 #78' ),
			'(https://github.com/woocommerce/example/pull/12)' +
				'(https://github.com/woocommerce/example/pull/34) ' +
				'woocommerce/example#56 woocommerce/example#78'
		);
	} );

	it( 'qualifies every reference in a parenthesized list', () => {
		assert.equal(
			rewriteMessage( 'Fix widget (#12, #34)' ),
			'Fix widget (woocommerce/example#12, woocommerce/example#34)'
		);
	} );

	it( 'qualifies references inside parenthesized text', () => {
		assert.equal(
			rewriteMessage( 'Tidy up (see #12).' ),
			'Tidy up (see woocommerce/example#12).'
		);
	} );

	it( 'leaves qualified references and URL fragments unchanged', () => {
		const message =
			'See other/repo#12, https://example.com/page#5 and abc#7.';

		assert.equal( rewriteMessage( message ), message );
	} );

	it( 'leaves malformed references unchanged', () => {
		const message = 'Malformed: (#), (#abc), # 12, and #.';

		assert.equal( rewriteMessage( message ), message );
	} );

	it( 'leaves nonmatching text unchanged', () => {
		const message = 'A commit message without issue references.';

		assert.equal( rewriteMessage( message ), message );
	} );
} );
