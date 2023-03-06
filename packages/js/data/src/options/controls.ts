/**
 * External dependencies
 */
import { controls as dataControls } from '@wordpress/data-controls';
import { Action } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE } from '../constants';

let optionNames: string[] = [];
const fetches: {
	[ key: string ]: Promise< unknown >;
} = {};

export const batchFetch = ( optionName: string ) => {
	return {
		type: 'BATCH_FETCH',
		optionName,
	};
};

export const controls = {
	...dataControls,
	BATCH_FETCH( { optionName }: Action ) {
		optionNames.push( optionName );

		return new Promise( ( resolve ) => {
			setTimeout( function () {
				if (
					fetches.hasOwnProperty( optionName ) &&
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore TODO - this type bug needs to be fixed.
					fetches[ optionName ]
				) {
					return fetches[ optionName ].then( ( result ) => {
						resolve( result );
					} );
				}

				// Get unique option names.
				const names = [ ...new Set( optionNames ) ].join( ',' );
				// Send request for a group of options.
				const url = WC_ADMIN_NAMESPACE + '/options?options=' + names;
				const fetch = apiFetch( { path: url } );
				fetch.then( ( result ) => resolve( result ) );
				optionNames.forEach( ( option ) => {
					fetches[ option ] = fetch;
					fetches[ option ].then( () => {
						// Delete the fetch after to allow wp data to handle cache invalidation.
						delete fetches[ option ];
					} );
				} );

				// Clear option names after we've sent the request for a group of options.
				optionNames = [];
			}, 1 );
		} );
	},
};
