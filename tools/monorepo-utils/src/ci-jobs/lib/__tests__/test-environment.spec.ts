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
				if (
					url !== 'http://api.wordpress.org/core/stable-check/1.0/'
				) {
					throw new Error( 'Invalid URL' );
				}

				const getStream = new Stream();

				// Let the consumer set up listeners for the stream.
				callback( getStream as IncomingMessage );

				const wpVersions = {
					'5.9': 'insecure',
					'6.0': 'insecure',
					'6.0.1': 'insecure',
					'6.1': 'insecure',
					'6.1.1': 'insecure',
					'6.1.2': 'outdated',
					'6.2': 'latest',
				};

				getStream.emit( 'data', JSON.stringify( wpVersions ) );

				getStream.emit( 'end' ); // this will trigger the promise resolve

				return jest.fn() as any;
			} );

			it( 'should parse "master" and "trunk" branches', async () => {
				let envVars = await parseTestEnvConfig( {
					wpVersion: 'master',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'WordPress/WordPress#master',
				} );

				envVars = await parseTestEnvConfig( {
					wpVersion: 'trunk',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'WordPress/WordPress#master',
				} );
			} );

			it( 'should parse nightlies', async () => {
				const envVars = await parseTestEnvConfig( {
					wpVersion: 'nightly',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE:
						'https://wordpress.org/nightly-builds/wordpress-latest.zip',
				} );
			} );

			it( 'should parse latest', async () => {
				const envVars = await parseTestEnvConfig( {
					wpVersion: 'latest',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'https://wordpress.org/latest.zip',
				} );
			} );

			it( 'should parse specific minor version', async () => {
				const envVars = await parseTestEnvConfig( {
					wpVersion: '5.9.0',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'https://wordpress.org/wordpress-5.9.zip',
				} );
			} );

			it( 'should parse specific patch version', async () => {
				const envVars = await parseTestEnvConfig( {
					wpVersion: '6.0.1',
				} );

				expect( envVars ).toEqual( {
					WP_ENV_CORE: 'https://wordpress.org/wordpress-6.0.1.zip',
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
		} );
	} );
} );
