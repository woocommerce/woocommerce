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
		pages: {},
		meta: {
			total: undefined,
		},
	},
	campaignTypes: {
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

		case TYPES.RECEIVE_CAMPAIGNS:
			const { meta } = action;
			const key = `${ meta.page }-${ meta.perPage }`;

			return {
				...state,
				campaigns: {
					pages: {
						...state.campaigns.pages,
						[ key ]: action.error
							? {
									error: action.payload,
							  }
							: {
									data: action.payload,
							  },
					},
					meta: {
						total: meta.total,
					},
				},
			};

		case TYPES.RECEIVE_CAMPAIGN_TYPES:
			return {
				...state,
				campaignTypes: action.error
					? {
							error: action.payload,
					  }
					: {
							data: action.payload,
					  },
			};

		default:
			return state;
	}
};
