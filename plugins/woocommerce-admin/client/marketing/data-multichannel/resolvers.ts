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
import { fetchWithHeaders } from './controls';
import { RegisteredChannel, RecommendedChannel, Campaign } from './types';
import { API_NAMESPACE } from './constants';
import { isApiFetchError } from './guards';

const getIntHeaderValues = (
	response: {
		headers: Map< string, string >;
		data: unknown;
	},
	keys: string[]
) => {
	return keys.map( ( key ) => {
		const value = response.headers.get( key );
		if ( value === undefined ) {
			throw new Error( `'${ key }' header is missing.` );
		}
		return parseInt( value, 10 );
	} );
};

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

export function* getCampaigns( page: number, perPage: number ) {
	try {
		const resp: {
			headers: Map< string, string >;
			data: Array< Campaign >;
		} = yield fetchWithHeaders( {
			path: `${ API_NAMESPACE }/campaigns?page=${ page }&per_page=${ perPage }`,
			parse: false,
		} );

		const [ total ] = getIntHeaderValues( resp, [ 'x-wp-total' ] );

		yield receiveCampaignsSuccess( {
			payload: resp.data,
			error: false,
			meta: {
				page,
				perPage,
				total,
			},
		} );
	} catch ( error ) {
		// TODO: error is an HTTPResponse that hasn't been parsed.
		if ( isApiFetchError( error ) ) {
			yield receiveCampaignsError( error );
		}

		throw error;
	}
}
