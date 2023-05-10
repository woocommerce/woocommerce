/**
 * Internal dependencies
 */
import { TYPES } from './action-types';
import { isApiFetchError } from './guards';
import {
	ApiFetchError,
	RegisteredChannel,
	RecommendedChannel,
	Campaign,
	CampaignType,
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

type CampaignsSuccessResponse = {
	payload: Array< Campaign >;
	error: false;
	meta: {
		page: number;
		perPage: number;
		total?: number;
	};
};

type CampaignsFailResponse = {
	payload: ApiFetchError;
	error: true;
	meta: {
		page: number;
		perPage: number;
		total?: number;
	};
};

type CampaignsResponse = CampaignsSuccessResponse | CampaignsFailResponse;

/**
 * Create a "RECEIVE_CAMPAIGNS" action object.
 */
export const receiveCampaigns = ( response: CampaignsResponse ) => {
	return {
		type: TYPES.RECEIVE_CAMPAIGNS,
		...response,
	};
};

export const receiveCampaignTypes = (
	data: Array< CampaignType > | ApiFetchError
) => {
	if ( isApiFetchError( data ) ) {
		return {
			type: TYPES.RECEIVE_CAMPAIGN_TYPES,
			payload: data,
			error: true as const,
		};
	}

	return {
		type: TYPES.RECEIVE_CAMPAIGN_TYPES,
		payload: data,
		error: false as const,
	};
};

export type Action = ReturnType<
	| typeof receiveRegisteredChannelsSuccess
	| typeof receiveRegisteredChannelsError
	| typeof receiveRecommendedChannelsSuccess
	| typeof receiveRecommendedChannelsError
	| typeof receiveCampaigns
	| typeof receiveCampaignTypes
>;
