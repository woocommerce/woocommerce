/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	receiveChannelsSuccess,
	receiveChannelsError,
	receiveRecommendedChannelsSuccess,
	receiveRecommendedChannelsError,
} from './actions';
import { Channel, RecommendedPlugin } from './types';
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

export function* getRecommendedChannels() {
	try {
		const data: RecommendedPlugin[] = yield apiFetch( {
			path: `${ API_NAMESPACE }/recommendations?category=channels`,
		} );

		yield receiveRecommendedChannelsSuccess( data );
	} catch ( error ) {
		if ( isApiFetchError( error ) ) {
			yield receiveRecommendedChannelsError( error );
		}

		throw error;
	}
}
