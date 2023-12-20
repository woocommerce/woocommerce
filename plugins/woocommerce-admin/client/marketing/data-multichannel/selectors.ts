/**
 * Internal dependencies
 */
import { State } from './types';

export const getRegisteredChannels = ( state: State ) => {
	return state.registeredChannels;
};

export const getRecommendedChannels = ( state: State ) => {
	return state.recommendedChannels;
};

/**
 * Get campaigns from state.
 *
 * @param state   State passed in from the data store.
 * @param page    Page number. First page is `1`.
 * @param perPage Page size, i.e. number of records in one page.
 */
export const getCampaigns = ( state: State, page: number, perPage: number ) => {
	const key = `${ page }-${ perPage }`;
	return {
		campaignsPage: state.campaigns.pages[ key ] || null,
		meta: state.campaigns.meta,
	};
};

export const getCampaignTypes = ( state: State ) => {
	return state.campaignTypes;
};
