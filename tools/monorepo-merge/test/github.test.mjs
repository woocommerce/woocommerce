import assert from 'node:assert/strict';
import { describe, it } from 'node:test';

import { createGraphQL } from '../src/github.mjs';

describe( 'createGraphQL', () => {
	it( 'reports an invalid token on HTTP 401', async ( t ) => {
		t.mock.method( globalThis, 'fetch', async () =>
			Response.json( { message: 'Bad credentials' }, { status: 401 } )
		);

		await assert.rejects( createGraphQL( 'bad' )( '{ viewer { id } }' ), {
			message: 'The given token is invalid',
		} );
	} );

	it( 'rejects with the GraphQL error messages', async ( t ) => {
		t.mock.method( globalThis, 'fetch', async () =>
			Response.json( {
				data: { repository: null },
				errors: [ { message: 'Could not resolve to a Repository' } ],
			} )
		);

		await assert.rejects( createGraphQL( 'token' )( '{ viewer { id } }' ), {
			message: 'Could not resolve to a Repository',
		} );
	} );
} );
