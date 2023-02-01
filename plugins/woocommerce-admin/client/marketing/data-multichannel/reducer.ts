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
	registeredChannels: {
		data: undefined,
		error: undefined,
	},
	recommendedChannels: {
		data: undefined,
		error: undefined,
	},
	campaigns: {
		data: undefined,
		error: undefined,
	},
};

export const reducer: Reducer< State, Action > = (
	state = initialState,
	action
) => {
	switch ( action.type ) {
		case TYPES.RECEIVE_REGISTERED_CHANNELS_SUCCESS:
			return {
				...state,
				registeredChannels: {
					data: action.payload,
				},
			};
		case TYPES.RECEIVE_REGISTERED_CHANNELS_ERROR:
			return {
				...state,
				registeredChannels: {
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

		case TYPES.RECEIVE_CAMPAIGNS_SUCCESS:
			return {
				...state,
				campaigns: {
					data: action.payload,
				},
			};

		case TYPES.RECEIVE_CAMPAIGNS_ERROR:
			return {
				...state,
				campaigns: {
					error: action.payload,
				},
			};

		default:
			return state;
	}
};
