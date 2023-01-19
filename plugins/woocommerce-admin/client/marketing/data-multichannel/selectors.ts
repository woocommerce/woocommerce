/**
 * Internal dependencies
 */
import { State } from './types';

export const getChannels = ( state: State ) => {
	return state.channels;
};

export const getRecommendedChannels = ( state: State ) => {
	return state.recommendedChannels;
};
