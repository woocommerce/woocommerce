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
	receiveCampaigns,
	receiveCampaignTypes,
} from './actions';
import { awaitResponseJson } from './controls';
import {
	RegisteredChannel,
	RecommendedChannel,
	Campaign,
	CampaignType,
	ApiFetchError,
} from './types';
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

/**
 * Get total number of records from the HTTP response header "x-wp-total".
 *
 * If the header is not present, then the function will return `undefined`.
 */
const getTotalFromResponse = ( response: Response ) => {
	const total = response.headers.get( 'x-wp-total' );

	if ( total === null ) {
		return undefined;
	}

	return parseInt( total, 10 );
};

/**
 * Get campaigns from API backend.
 *
 * @param page    Page number. First page is `1`.
 * @param perPage Page size, i.e. number of records in one page.
 */
export function* getCampaigns( page: number, perPage: number ) {
	try {
		const response: Response = yield apiFetch( {
			path: `${ API_NAMESPACE }/campaigns?page=${ page }&per_page=${ perPage }`,
			parse: false,
		} );

		const total = getTotalFromResponse( response );
		const payload: Campaign[] = yield awaitResponseJson( response );

		yield receiveCampaigns( {
			payload,
			error: false,
			meta: {
				page,
				perPage,
				total,
			},
		} );
	} catch ( error ) {
		if ( error instanceof Response ) {
			const total = getTotalFromResponse( error );
			const payload: ApiFetchError = yield awaitResponseJson( error );

			yield receiveCampaigns( {
				payload,
				error: true,
				meta: {
					page,
					perPage,
					total,
				},
			} );
		}

		throw error;
	}
}

export function* getCampaignTypes() {
	try {
		const data: CampaignType[] = yield apiFetch( {
			path: `${ API_NAMESPACE }/campaign-types`,
		} );

		yield receiveCampaignTypes( data );
	} catch ( error ) {
		if ( isApiFetchError( error ) ) {
			yield receiveCampaignTypes( error );
		}
	}
}
