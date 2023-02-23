/**
 * Internal dependencies
 */
import { TYPES } from './action-types';
import {
	ApiFetchError,
	RegisteredChannel,
	RecommendedChannel,
	Campaign,
} from './types';

export const receiveRegisteredChannelsSuccess = (
	channels: Array< RegisteredChannel >
) => {
	return {
		type: TYPES.RECEIVE_REGISTERED_CHANNELS_SUCCESS,
		payload: channels,
	};
};

export const receiveRegisteredChannelsError = ( error: ApiFetchError ) => {
	return {
		type: TYPES.RECEIVE_REGISTERED_CHANNELS_ERROR,
		payload: error,
		error: true,
	};
};

export const receiveRecommendedChannelsSuccess = (
	channels: Array< RecommendedChannel >
) => {
	return {
		type: TYPES.RECEIVE_RECOMMENDED_CHANNELS_SUCCESS,
		payload: channels,
	};
};

export const receiveRecommendedChannelsError = ( error: ApiFetchError ) => {
	return {
		type: TYPES.RECEIVE_RECOMMENDED_CHANNELS_ERROR,
		payload: error,
		error: true,
	};
};

export const receiveCampaignsSuccess = ( action: {
	payload: Array< Campaign >;
	error: boolean;
	meta: {
		page: number;
		perPage: number;
		total: number;
	};
} ) => {
	return {
		type: TYPES.RECEIVE_CAMPAIGNS_SUCCESS,
		...action,
	};
};

export const receiveCampaignsError = ( error: ApiFetchError ) => {
	return {
		type: TYPES.RECEIVE_CAMPAIGNS_ERROR,
		payload: error,
		error: true,
	};
};

export type Action = ReturnType<
	| typeof receiveRegisteredChannelsSuccess
	| typeof receiveRegisteredChannelsError
	| typeof receiveRecommendedChannelsSuccess
	| typeof receiveRecommendedChannelsError
	| typeof receiveCampaignsSuccess
	| typeof receiveCampaignsError
>;
