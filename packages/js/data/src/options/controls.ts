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

const delay = ( timeout: number ) =>
	new Promise( ( resolve ) => setTimeout( resolve, timeout ) );

export const controls = {
	...dataControls,
	async BATCH_FETCH( { optionName }: Action ) {
		optionNames.push( optionName );

		// Wait for 1ms to allow batching of option names
		await delay( 1 );

		// If the option name is already being fetched, return the promise
		if ( fetches.hasOwnProperty( optionName ) ) {
			return await fetches[ optionName ];
		}

		// Get unique option names
		const uniqueOptionNames = [ ...new Set( optionNames ) ];
		const names = uniqueOptionNames.join( ',' );

		// Send request for a group of options
		const fetch = apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/options?options=${ names }`,
		} );

		uniqueOptionNames.forEach( async ( option ) => {
			fetches[ option ] = fetch;
			try {
				await fetch;
			} catch ( error ) {
				// ignore error, the error will be thrown by the parent fetch
			} finally {
				// Delete the fetch after completion to allow wp data to handle cache invalidation
				delete fetches[ option ];
			}
		} );

		// Clear option names after we've sent the request for a group of options
		optionNames = [];

		return await fetch;
	},
};
