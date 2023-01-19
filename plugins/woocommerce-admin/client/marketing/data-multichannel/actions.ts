/**
 * Internal dependencies
 */
import { TYPES } from './action-types';
import { ApiFetchError, Channel, RecommendedPlugin } from './types';

export const receiveChannelsSuccess = ( channels: Array< Channel > ) => {
	return {
		type: TYPES.RECEIVE_CHANNELS_SUCCESS,
		payload: channels,
	};
};

export const receiveChannelsError = ( error: ApiFetchError ) => {
	return {
		type: TYPES.RECEIVE_CHANNELS_ERROR,
		payload: error,
		error: true,
	};
};

export const receiveRecommendedChannelsSuccess = (
	channels: Array< RecommendedPlugin >
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

export type Action = ReturnType<
	| typeof receiveChannelsSuccess
	| typeof receiveChannelsError
	| typeof receiveRecommendedChannelsSuccess
	| typeof receiveRecommendedChannelsError
>;
