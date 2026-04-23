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

	// Browser-thrown fetch error — no useful payload.
	if ( response instanceof TypeError ) {
		return true;
	}

	// Defensive: rejection without a message, errors array, or code.
	// If the browser is offline at request time, navigator.onLine is a
	// reliable hint.
	const hasMessage =
		typeof response === 'object' &&
		response !== null &&
		'message' in response &&
		response.message;
	const hasErrors =
		typeof response === 'object' &&
		response !== null &&
		'errors' in response &&
		response.errors &&
		Object.keys( response.errors ).length;

	if (
		! hasMessage &&
		! hasErrors &&
		typeof window !== 'undefined' &&
		window.navigator?.onLine === false
	) {
		return true;
	}

	return false;
}

export function createNoticesFromResponse( response ) {
	const { createNotice } = dispatch( 'core/notices' );

	if ( isSilentNetworkFailure( response ) ) {
		// Surface a clear notice instead of letting the failure disappear.
		// String matches Gutenberg's existing offline copy so translators
		// don't have to retranslate and the merchant experience stays
		// consistent across editors.
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
