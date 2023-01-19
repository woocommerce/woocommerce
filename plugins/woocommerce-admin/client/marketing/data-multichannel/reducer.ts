/**
 * External dependencies
 */

import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { State } from './types';
import { Action } from './actions';
import { TYPES } from './action-types';

const initialState = {
	channels: {
		data: undefined,
		error: undefined,
	},
	recommendedChannels: {
		data: undefined,
		error: undefined,
	},
};

export const reducer: Reducer< State, Action > = (
	state = initialState,
	action
) => {
	switch ( action.type ) {
		case TYPES.RECEIVE_CHANNELS_SUCCESS:
			return {
				...state,
				channels: {
					data: action.payload,
				},
			};
		case TYPES.RECEIVE_CHANNELS_ERROR:
			return {
				...state,
				channels: {
					error: action.payload,
				},
			};
		case TYPES.RECEIVE_RECOMMENDED_CHANNELS_SUCCESS:
			return {
				...state,
				recommendedChannels: {
					data: action.payload,
				},
			};
		case TYPES.RECEIVE_RECOMMENDED_CHANNELS_ERROR:
			return {
				...state,
				recommendedChannels: {
					error: action.payload,
				},
			};
		default:
			return state;
	}
};
