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
		const value = '4d34912a-1697-419d-9aa1-d4196772d663';
		jest.spyOn( globalThis.crypto, 'randomUUID' ).mockReturnValueOnce(
			value
		);
		const text = `${ logMarker.logPrefix }${ value }\nPHP Warning: example`;
		get.mockResolvedValueOnce( { ok: () => true } ).mockResolvedValueOnce( {
			status: () => 200,
			text: () => Promise.resolve( text ),
		} );

		const log = await readPhpDebugLog( request, logMarker );
		expect( log ).toBe( text );
		expect( get ).toHaveBeenNthCalledWith( 1, '/', {
			params: { [ logMarker.queryParameter ]: value },
		} );
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
