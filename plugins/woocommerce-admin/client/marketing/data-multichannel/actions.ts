/**
 * Internal dependencies
 */
import { TYPES } from './action-types';
import { ApiFetchError, RegisteredChannel, RecommendedChannel } from './types';

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

export type Action = ReturnType<
	| typeof receiveRegisteredChannelsSuccess
	| typeof receiveRegisteredChannelsError
	| typeof receiveRecommendedChannelsSuccess
	| typeof receiveRecommendedChannelsError
>;
