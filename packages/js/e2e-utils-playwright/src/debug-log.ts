/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';

/**
 * Reads the debug log at the site origin after verifying that WordPress wrote to it.
 *
 * @param request               - Playwright request context with the store's base URL.
 * @param marker                - Settings for the site's test-only log marker handler.
 * @param marker.queryParameter - Query parameter containing the marker value.
 * @param marker.logPrefix      - Prefix the handler writes immediately before the value.
 * @return The verified log contents.
 */
export async function readPhpDebugLog(
	request: Pick< APIRequestContext, 'get' >,
	marker: { queryParameter: string; logPrefix: string }
): Promise< string > {
	const value = globalThis.crypto.randomUUID();
	const markerResponse = await request.get( '/', {
		params: { [ marker.queryParameter ]: value },
	} );
	if ( ! markerResponse.ok() ) {
		throw new Error(
			`PHP log marker request failed (HTTP ${ markerResponse.status() }).`
		);
	}

	const logResponse = await request.get( '/wp-content/debug.log' );
	const status = logResponse.status();
	if ( status !== 200 ) {
		throw new Error(
			`Could not read /wp-content/debug.log (HTTP ${ status }).`
		);
	}

	const log = await logResponse.text();
	if ( ! log.includes( `${ marker.logPrefix }${ value }` ) ) {
		throw new Error(
			'/wp-content/debug.log did not contain the new marker.'
		);
	}

	return log;
}
