/** @jest-environment node */

/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';

/**
 * Internal dependencies
 */
import { readPhpDebugLog } from '../debug-log';

describe( 'readPhpDebugLog', () => {
	const marker = {
		queryParameter: 'e2e_log_marker',
		logPrefix: 'e2e-log-marker-',
	};
	const get = jest.fn();
	const request = { get } as Pick< APIRequestContext, 'get' >;

	beforeEach( () => {
		get.mockReset();
	} );

	it.each( [
		marker,
		{
			queryParameter: 'acceptance_log_marker',
			logPrefix: 'acceptance-log-marker-',
		},
	] )( 'reads the log written by $queryParameter', async ( logMarker ) => {
		let text = '';
		get.mockImplementationOnce( ( path, options ) => {
			expect( path ).toBe( '/' );
			const value = options.params[ logMarker.queryParameter ];
			expect( value ).toMatch( /^[0-9a-f-]{36}$/ );
			text = `${ logMarker.logPrefix }${ value }\nPHP Warning: example`;
			return { ok: () => true };
		} ).mockResolvedValueOnce( {
			status: () => 200,
			text: () => Promise.resolve( text ),
		} );

		const log = await readPhpDebugLog( request, logMarker );
		expect( log ).toBe( text );
		expect( get ).toHaveBeenNthCalledWith( 2, '/wp-content/debug.log' );
	} );

	it( 'stops if WordPress rejects the marker request', async () => {
		get.mockResolvedValueOnce( { ok: () => false, status: () => 503 } );

		await expect( readPhpDebugLog( request, marker ) ).rejects.toThrow(
			'PHP log marker request failed (HTTP 503).'
		);
		expect( get ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'fails when the debug log cannot be read', async () => {
		get.mockResolvedValueOnce( { ok: () => true } ).mockResolvedValueOnce( {
			status: () => 404,
		} );

		await expect( readPhpDebugLog( request, marker ) ).rejects.toThrow(
			'Could not read /wp-content/debug.log (HTTP 404).'
		);
	} );

	it( 'rejects a readable log that lacks the fresh marker', async () => {
		get.mockResolvedValueOnce( { ok: () => true } ).mockResolvedValueOnce( {
			status: () => 200,
			text: () => Promise.resolve( 'e2e-log-marker-old\n' ),
		} );

		await expect( readPhpDebugLog( request, marker ) ).rejects.toThrow(
			'/wp-content/debug.log did not contain the new marker.'
		);
	} );
} );
