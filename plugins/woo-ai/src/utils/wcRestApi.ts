/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

type makeWCRestApiCallProps = {
	path: string;
	httpVerb: string;
	body?: Record< string, unknown >;
};

const makeWCRestApiCall = async ( {
	path,
	httpVerb,
	body,
}: makeWCRestApiCallProps ) => {
	try {
		console.log( 'path' );
		console.log( path );
		console.log( 'httpVerb' );
		console.log( httpVerb );
		console.log( 'body' );
		console.log( body );
		console.log( typeof body );
		console.log( 'apiFetch' );
		console.log( { path, method: httpVerb, body: JSON.stringify( body ) } );
		const response = await apiFetch( {
			path,
			method: httpVerb,
			// If body is not null or undefined, include it in the apiFetch call
			...( body && { data: body } ),
		} );
		return response;
	} catch ( error ) {
		console.error( 'Error making WC REST API call:', error );
		throw error; // Re-throw the error to be handled by the caller
	}
};

export default makeWCRestApiCall;
