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

export const getCampaigns = ( state: State ) => {
	return state.campaigns;
};
