/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Given a JS error or a fetch response error, parse and format it so it can be displayed to the user.
 *
 * @param  {Object}   error           Error object.
 * @param  {Function} [error.json]    If a json method is specified, it will try parsing the error first.
 * @param  {string}   [error.message] If a message is specified, it will be shown to the user.
 * @param  {string}   [error.type]    The context in which the error was triggered.
 * @return {Object}   Error object containing a message and type.
 */
export const formatError = async ( error ) => {
	if ( typeof error.json === 'function' ) {
		try {
			const parsedError = await error.json();
			return {
				message: parsedError.message,
				type: parsedError.type || 'api',
			};
		} catch ( e ) {
			return {
				message: e.message,
				type: 'general',
			};
		}
	}

	return {
		message: error.message,
		type: error.type || 'general',
	};
};

/**
 * Given an API response object, formats the error message into something more human readable.
 *
 * @param  {Object}   response           Response object.
 * @return {string}   Error message.
 */
export const formatStoreApiErrorMessage = ( response ) => {
	if ( response.data && response.code === 'rest_invalid_param' ) {
		const invalidParams = Object.values( response.data.params );
		if ( invalidParams[ 0 ] ) {
			return invalidParams[ 0 ];
		}
	}

	return (
		response?.message ||
		__(
			'Something went wrong. Please contact us to get assistance.',
			'woocommerce'
		)
	);
};
