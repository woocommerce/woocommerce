/**
 * External dependencies
 */
import { controls as dataControls } from '@wordpress/data-controls';
import apiFetch from '@wordpress/api-fetch';
/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE } from '../constants';
import { debounce } from './utils';

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

const debouncedFetch = async ( optionName: string ) => {
	// Wrap the debounced function in a promise because debounce function doesn't wait for the promise to resolve
	return new Promise(
		async ( resolve, reject ) =>
			debounce( () => {
				// If the option name is already being fetched, return the promise
				if ( fetches.hasOwnProperty( optionName ) ) {
					return fetches[ optionName ]
						.then( resolve )
						.catch( reject );
				}

				if ( optionNames.length === 0 ) {
					// Previous batch fetch might fail
					optionNames.push( optionName );
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

				fetch.then( resolve ).catch( reject );
			}, 100 )() // 100ms debounce time for batch fetches (to avoid multiple fetches for the same options while not affecting user experience too much. Typically, values between 50ms and 200ms should provide a good balance for most applications.
	);
};

export const controls = {
	...dataControls,
	async BATCH_FETCH( { optionName }: { optionName: string } ) {
		optionNames.push( optionName );

		// Consolidate multiple fetches into a single fetch
		return await debouncedFetch( optionName );
	},
};
