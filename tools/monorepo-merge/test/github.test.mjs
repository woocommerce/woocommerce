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

	it( 'keeps a malformed token out of error messages', async () => {
		await assert.rejects(
			async () =>
				createGraphQL( 'ghp_secret\nvalue' )( '{ viewer { id } }' ),
			( error ) => ! error.message.includes( 'ghp_secret' )
		);
	} );

	it( "includes GitHub's explanation for other HTTP failures", async ( t ) => {
		t.mock.method( globalThis, 'fetch', async () =>
			Response.json(
				{
					message:
						'Resource protected by organization SAML enforcement',
				},
				{ status: 403 }
			)
		);

		await assert.rejects( createGraphQL( 'token' )( '{ viewer { id } }' ), {
			message:
				'GitHub API request failed with HTTP 403: Resource protected by organization SAML enforcement',
		} );
	} );

	it( 'marks a GraphQL rate limit as a failure of every request', async ( t ) => {
		t.mock.method( globalThis, 'fetch', async () =>
			Response.json( {
				errors: [
					{
						type: 'RATE_LIMITED',
						message: 'API rate limit exceeded',
					},
				],
			} )
		);

		await assert.rejects( createGraphQL( 'token' )( '{ viewer { id } }' ), {
			message: 'API rate limit exceeded',
			requestFailed: true,
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
