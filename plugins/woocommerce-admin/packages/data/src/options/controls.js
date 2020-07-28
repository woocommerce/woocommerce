/**
 * External dependencies
 */
import { controls as dataControls } from '@wordpress/data-controls';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE } from '../constants';

let optionNames = [];
const fetches = {};

export const batchFetch = ( optionName ) => {
	return {
		type: 'BATCH_FETCH',
		optionName,
	};
};

export const controls = {
	...dataControls,
	BATCH_FETCH( { optionName } ) {
		optionNames.push( optionName );

		return new Promise( ( resolve ) => {
			setTimeout( function () {
				const names = optionNames.join( ',' );
				if ( fetches[ names ] ) {
					return fetches[ names ].then( ( result ) => {
						resolve( result[ optionName ] );
					} );
				}

				const url = WC_ADMIN_NAMESPACE + '/options?options=' + names;
				fetches[ names ] = apiFetch( { path: url } );
				fetches[ names ].then( ( result ) => resolve( result ) );

				// Clear option names after all resolved;
				setTimeout( () => {
					optionNames = [];
					// Delete the fetch after to allow wp data to handle cache invalidation.
					delete fetches[ names ];
				}, 1 );
			}, 1 );
		} );
	},
};
