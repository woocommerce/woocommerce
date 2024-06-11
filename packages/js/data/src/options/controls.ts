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
import { debounce } from './utilts';

export const batchFetch = ( optionName: string ) => {
	return {
		type: 'BATCH_FETCH',
		optionName,
	};
};

let optionNames: string[] = [];
const fetches: {
	[ key: string ]: Promise< unknown >;
} = {};

const debouncedFetch = debounce( async () => {
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
}, 100 ); // 100ms debounce time for batch fetches (to avoid multiple fetches for the same options while not affecting user experience too much. Typically, values between 50ms and 200ms should provide a good balance for most applications.

export const controls = {
	...dataControls,
	async BATCH_FETCH( { optionName }: Action ) {
		optionNames.push( optionName );

		// If the option name is already being fetched, return the promise
		if ( fetches.hasOwnProperty( optionName ) ) {
			return await fetches[ optionName ];
		}

		// Consolidate multiple fetches into a single fetch
		return await debouncedFetch();
	},
};
