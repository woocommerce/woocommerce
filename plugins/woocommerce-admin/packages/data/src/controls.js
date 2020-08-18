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
				return Promise.all( [
					response.headers,
					response.status,
					response.json(),
				] );
			} )
			.then( ( [ headers, status, data ] ) => ( {
				headers,
				status,
				data,
			} ) );
	},
};

export default controls;
