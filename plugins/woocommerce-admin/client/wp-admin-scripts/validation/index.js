/**
 * External dependencies
 */
import Ajv from 'ajv'
import apiFetch from '@wordpress/api-fetch';

export const validator = new Ajv( {
	strict: false,
	allErrors: true,
	coerceTypes: true,
	$data: true,
} );

apiFetch( {
    // @todo Replace with localized variable for path.
    path: '/wc/v3/products/',
    method: 'OPTIONS',
} ).then( ( results ) => {
    const compiled = validator.compile(results.schema);
    // Send compiled to server
        // Authorization from non-logged in users?
        // Persistence as cache or file?
} );
