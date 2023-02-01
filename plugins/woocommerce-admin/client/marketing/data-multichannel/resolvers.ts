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
	receiveCampaignsSuccess,
	receiveCampaignsError,
} from './actions';
import { RegisteredChannel, RecommendedChannel, Campaign } from './types';
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

export function* getCampaigns() {
	/**
	 * Page size.
	 *
	 * We set this to 100 because this is the maximum limit allowed by the API.
	 *
	 * We need this to support pagination in the UI.
	 * Currently API does not return total number of rows,
	 * so we use 100 to get "all" the rows.
	 */
	const perPage = 100;

	try {
		const data: Array< Campaign > = yield apiFetch( {
			path: `${ API_NAMESPACE }/campaigns?per_page=${ perPage }`,
		} );

		yield receiveCampaignsSuccess( data );
	} catch ( error ) {
		if ( isApiFetchError( error ) ) {
			yield receiveCampaignsError( error );
		}

		throw error;
	}
}
