/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Raw network failures (e.g. the browser is offline, or DNS cannot resolve)
 * arrive as a `TypeError: Failed to fetch` and have no `message`/`errors`
 * payload. Without special handling, these fall through silently and the
 * merchant sees nothing when a save fails.
 *
 * Returns true if the response looks like a browser-level network failure.
 *
 * @param {unknown} response The rejection value from apiFetch.
 * @return {boolean} Whether this looks like a silent network failure.
 */
function isSilentNetworkFailure( response ) {
	if ( ! response ) {
		return false;
	}

	if ( response instanceof TypeError ) {
		return true;
	}

	if ( typeof response !== 'object' ) {
		return false;
	}

	// Any of these properties means the API returned a structured error;
	// fall through to the existing handling below so the merchant sees the
	// real message rather than a generic offline copy.
	const hasStructuredPayload =
		( 'message' in response && response.message ) ||
		( 'errors' in response &&
			response.errors &&
			Object.keys( response.errors ).length ) ||
		'code' in response ||
		'error_data' in response;

	return (
		! hasStructuredPayload &&
		typeof window !== 'undefined' &&
		window.navigator?.onLine === false
	);
}

export function createNoticesFromResponse( response ) {
	const { createNotice } = dispatch( 'core/notices' );

	if ( isSilentNetworkFailure( response ) ) {
		// String matches Gutenberg's existing offline copy — reuses their translations.
		createNotice(
			'error',
			__( 'Updating failed. You are probably offline.', 'woocommerce' )
		);
		return;
	}

	if (
		response.error_data &&
		response.errors &&
		Object.keys( response.errors ).length
	) {
		// Loop over multi-error responses.
		Object.keys( response.errors ).forEach( ( errorKey ) => {
			createNotice( 'error', response.errors[ errorKey ].join( ' ' ) );
		} );
	} else if ( response.message ) {
		// Handle generic messages.
		createNotice( response.code ? 'error' : 'success', response.message );
	}
}
