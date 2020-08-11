/**
 * External dependencies
 */
import { controls as dataControls } from '@wordpress/data-controls';

import apiFetch from '@wordpress/api-fetch';

export const fetchWithHeaders = ( options ) => {
	return {
		type: 'FETCH_WITH_HEADERS',
		options,
	};
};

const controls = {
	...dataControls,
	FETCH_WITH_HEADERS( { options } ) {
		return apiFetch( { ...options, parse: false } )
			.then( ( response ) => {
				return Promise.all( [ response.headers, response.json() ] );
			} )
			.then( ( [ headers, data ] ) => ( { headers, data } ) );
	},
};

export default controls;
