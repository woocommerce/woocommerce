/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	receiveRegisteredChannelsSuccess,
	receiveRegisteredChannelsError,
	receiveRecommendedChannelsSuccess,
	receiveRecommendedChannelsError,
} from './actions';
import { RegisteredChannel, RecommendedChannel } from './types';
import { API_NAMESPACE } from './constants';
import { isApiFetchError } from './guards';

export function* getRegisteredChannels() {
	try {
		const data: RegisteredChannel[] = yield apiFetch( {
			path: `${ API_NAMESPACE }/channels`,
		} );

		yield receiveRegisteredChannelsSuccess( data );
	} catch ( error ) {
		if ( isApiFetchError( error ) ) {
			yield receiveRegisteredChannelsError( error );
		}

		throw error;
	}
}

export function* getRecommendedChannels() {
	try {
		const data: RecommendedChannel[] = yield apiFetch( {
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
