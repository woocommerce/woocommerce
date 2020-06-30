/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import triggerFetch from '@wordpress/api-fetch';

/**
 * Dispatched a control action for triggering an api fetch call with no parsing.
 * Typically this would be used in scenarios where headers are needed.
 *
 * @param {Object} options The options for the API request.
 *
 * @return {Object} The control action descriptor.
 */
export const apiFetchWithHeaders = ( options ) => {
	return {
		type: 'API_FETCH_WITH_HEADERS',
		options,
	};
};

/**
 * Error thrown when JSON cannot be parsed.
 */
const invalidJsonError = {
	code: 'invalid_json',
	message: __(
		'The response is not a valid JSON response.',
		'woocommerce'
	),
};

/**
 * Default export for registering the controls with the store.
 *
 * @return {Object} An object with the controls to register with the store on
 *                  the controls property of the registration object.
 */
export const controls = {
	API_FETCH_WITH_HEADERS( { options } ) {
		return new Promise( ( resolve, reject ) => {
			triggerFetch( { ...options, parse: false } )
				.then( ( fetchResponse ) => {
					fetchResponse
						.json()
						.then( ( response ) => {
							resolve( {
								response,
								headers: fetchResponse.headers,
							} );
							triggerFetch.setNonce( fetchResponse.headers );
						} )
						.catch( () => {
							reject( invalidJsonError );
						} );
				} )
				.catch( ( errorResponse ) => {
					if ( typeof errorResponse.json === 'function' ) {
						// Parse error response before rejecting it.
						errorResponse
							.json()
							.then( ( error ) => {
								reject( error );
							} )
							.catch( () => {
								reject( invalidJsonError );
							} );
					} else {
						reject( errorResponse.message );
					}
				} );
		} );
	},
};
