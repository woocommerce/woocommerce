/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { receiveChannelsSuccess, receiveChannelsError } from './actions';
import { Channel } from './types';
import { API_NAMESPACE } from './constants';
import { isApiFetchError } from './guards';

export function* getChannels() {
	try {
		const data: Channel[] = yield apiFetch( {
			path: `${ API_NAMESPACE }/channels`,
		} );

		yield receiveChannelsSuccess( data );
	} catch ( error ) {
		if ( isApiFetchError( error ) ) {
			yield receiveChannelsError( error );
		}

		throw error;
	}
}
