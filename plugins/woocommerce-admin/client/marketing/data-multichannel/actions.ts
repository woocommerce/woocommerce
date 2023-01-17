/**
 * Internal dependencies
 */
import { TYPES } from './action-types';
import { ApiFetchError, Channel } from './types';

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

export type Action = ReturnType<
	typeof receiveChannelsSuccess | typeof receiveChannelsError
>;
