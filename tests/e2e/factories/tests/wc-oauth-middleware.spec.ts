import { createSignatureMiddleware } from '../src/wc-oauth-middleware';

describe( 'createSignatureMiddleware', () => {
	const middlewareFunction = createSignatureMiddleware( 'test', 'secret' );

	it( 'should use basic auth for SSL', () => {
		middlewareFunction(
			{ url: 'https://test-api', method: 'GET', headers: {} },
			( options ) => {
				expect( options.headers ).toHaveProperty( 'Authorization' );
				expect( options.headers.Authorization ).toMatch(
					'Basic dGVzdDpzZWNyZXQ='
				);
				return Promise.resolve();
			}
		);
	} );

	it( 'should use oauth for non-SSL', () => {
		middlewareFunction(
			{ url: 'http://test-api', method: 'GET', headers: {} },
			( options ) => {
				expect( options.headers ).toHaveProperty( 'Authorization' );
				expect( options.headers.Authorization ).toMatch( /^OAuth / );

				// Parse the authorization header so we can validate its structure.
				const rawArgs = options.headers.Authorization.split( ', ' );
				const oauthArgs: { [ key: string ]: string } = {};
				for ( const arg of rawArgs ) {
					const m = arg.match( /([A-Za-z0-9_]+)="([^"]+)"/ );

					expect( m ).not.toBeNull();
					expect( m ).toHaveProperty( '1' );
					// @ts-ignore: Our assertion above prevents cases where it might be null.
					expect( m[ 1 ] ).toMatch( /^oauth_/ );
					expect( m ).toHaveProperty( '2' );

					// @ts-ignore: Our assertions above ensure that this object has the entries we expect.
					oauthArgs[ m[ 1 ] ] = m[ 2 ];
				}

				expect( oauthArgs ).toHaveProperty( 'oauth_consumer_key' );
				expect( oauthArgs.oauth_consumer_key ).toMatch( 'test' );
				expect( oauthArgs ).toHaveProperty( 'oauth_nonce' );
				expect( oauthArgs ).toHaveProperty( 'oauth_signature' );
				expect( oauthArgs ).toHaveProperty( 'oauth_signature_method' );
				expect( oauthArgs.oauth_signature_method ).toMatch(
					'HMAC-SHA256'
				);
				expect( oauthArgs ).toHaveProperty( 'oauth_timestamp' );
				expect( oauthArgs ).toHaveProperty( 'oauth_version' );
				expect( oauthArgs.oauth_version ).toMatch( '1.0' );

				// We're going to make the assumption here that the OAuth1.0a library has generated a valid signature.

				return Promise.resolve();
			}
		);
	} );
} );
