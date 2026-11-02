/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Returns the HTTP status of an apiFetch error, or null when there is none.
 * apiFetch rejects with a plain `{ code, message, data: { status } }` object.
 */
export function errorStatus( error: unknown ): number | null {
	if ( ! error || typeof error !== 'object' || ! ( 'data' in error ) ) {
		return null;
	}
	const data = ( error as { data?: unknown } ).data;
	if (
		data &&
		typeof data === 'object' &&
		typeof ( data as { status?: unknown } ).status === 'number'
	) {
		return ( data as { status: number } ).status;
	}
	return null;
}

/**
 * Returns the REST error code of an apiFetch error, or null when there is none.
 */
export function errorCode( error: unknown ): string | null {
	if (
		error &&
		typeof error === 'object' &&
		'code' in error &&
		typeof ( error as { code?: unknown } ).code === 'string'
	) {
		return ( error as { code: string } ).code;
	}
	return null;
}

/**
 * Returns a message to show for an Error, an apiFetch rejection or a string.
 * Falls back to a generic message when there is no text to show.
 */
export function errorMessage( error: unknown ): string {
	let message = '';
	if ( typeof error === 'string' ) {
		message = error;
	} else if (
		error &&
		typeof error === 'object' &&
		'message' in error &&
		typeof ( error as { message?: unknown } ).message === 'string'
	) {
		message = ( error as { message: string } ).message;
	}
	return message.trim() !== ''
		? message
		: __( 'Something went wrong. Please try again.', 'woocommerce' );
}
