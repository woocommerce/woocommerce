/**
 * External dependencies
 */
import { IncomingMessage, get } from 'node:http';
import { Stream } from 'node:stream';

/**
 * Internal dependencies
 */
import { parseTestEnvConfig } from '../test-environment';

jest.mock( 'node:http' );

describe( 'Test Environment', () => {
	describe( 'parseTestEnvConfig', () => {
		it( 'should parse empty configs', async () => {
			const envVars = await parseTestEnvConfig( {} );

			expect( envVars ).toEqual( {} );
		} );

		describe( 'wpVersion', () => {
			// We're going to mock an implementation of the request to the WordPress.org API.
			// This simulates what happens when we call https.get() for it.
			jest.mocked( get ).mockImplementation( ( url, callback: any ) => {
				let response = {};
				const getStream = new Stream();

				if (
					url === 'http://api.wordpress.org/core/stable-check/1.0/'
				) {
					// Let the consumer set up listeners for the stream.
					callback( getStream as IncomingMessage );

					response = {
						'5.9': 'insecure',
						'6.0': 'insecure',
						'6.0.1': 'insecure',
						'6.1': 'insecure',
						'6.1.1': 'insecure',
						'6.1.2': 'outdated',
						'6.2': 'latest',
					};
				} else if (
					url
						.toString()
						.includes(
							'http://api.wordpress.org/core/version-check/1.7'
						)
				) {
					response = {
						offers: [
							{
								response: 'development',
								version: '6.3-beta1',
								download:
									'https://wordpress.org/wordpress-6.3-beta1.zip',
							},
						],
					};

					callback( getStream as IncomingMessage );
				} else {
					throw new Error( 'Invalid URL' );
				}

				getStream.emit( 'data', JSON.stringify( response ) );

				getStream.emit( 'end' ); // this will trigger the promise resolve

				return jest.fn() as any;
			} );

			it( 'should parse "master" and "trunk" branches', async () => {
				let envVars = await parseTestEnvConfig( {
					wpVersion: 'master',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'WordPress/WordPress#master',
					WP_VERSION: 'master',
				} );

				envVars = await parseTestEnvConfig( {
					wpVersion: 'trunk',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'WordPress/WordPress#master',
					WP_VERSION: 'master',
				} );
			} );

			it( 'should parse nightlies', async () => {
				const envVars = await parseTestEnvConfig( {
					wpVersion: 'nightly',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE:
						'https://wordpress.org/nightly-builds/wordpress-latest.zip',
					WP_VERSION: 'nightly',
				} );
			} );

			it( 'should parse latest', async () => {
				const envVars = await parseTestEnvConfig( {
					wpVersion: 'latest',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'https://wordpress.org/latest.zip',
					WP_VERSION: 'latest',
				} );
			} );

			it( 'should parse specific minor version', async () => {
				const envVars = await parseTestEnvConfig( {
					wpVersion: '5.9.0',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'https://wordpress.org/wordpress-5.9.zip',
					WP_VERSION: '5.9',
				} );
			} );

			it( 'should parse specific patch version', async () => {
				const envVars = await parseTestEnvConfig( {
					wpVersion: '6.0.1',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'https://wordpress.org/wordpress-6.0.1.zip',
					WP_VERSION: '6.0.1',
				} );
			} );

			it( 'should throw for version that does not exist', async () => {
				const expectation = () =>
					parseTestEnvConfig( {
						wpVersion: '1.0',
					} );

				expect( expectation ).rejects.toThrowError(
					/Failed to parse WP version/
				);
			} );

			it( 'should parse latest offset', async () => {
				const envVars = await parseTestEnvConfig( {
					wpVersion: 'latest-1',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'https://wordpress.org/wordpress-6.1.2.zip',
					WP_VERSION: '6.1.2',
				} );
			} );

			it( 'should throw for latest offset that does not exist', async () => {
				const expectation = () =>
					parseTestEnvConfig( {
						wpVersion: 'latest-10',
					} );

				expect( expectation ).rejects.toThrowError(
					/Failed to parse WP version/
				);
			} );

			it( 'should parse the prerelease offer', async () => {
				const envVars = await parseTestEnvConfig( {
					wpVersion: 'prerelease',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE:
						'https://wordpress.org/wordpress-6.3-beta1.zip',
					WP_VERSION: '6.3-beta1',
				} );
			} );
		} );
	} );
} );
