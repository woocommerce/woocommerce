/**
 * External dependencies
 */

import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Action } from './actions';
import { OptionsState } from './types';

const optionsReducer: Reducer< OptionsState, Action > = (
	state = { isUpdating: false, requestingErrors: {} },
	action
) => {
	switch ( action.type ) {
		case TYPES.RECEIVE_OPTIONS:
			state = {
				...state,
				...action.options,
			};
			break;
		case TYPES.SET_IS_UPDATING:
			state = {
				...state,
				isUpdating: action.isUpdating,
			};
			break;
		case TYPES.SET_REQUESTING_ERROR:
			state = {
				...state,
				requestingErrors: {
					[ action.name ]: action.error,
				},
			};
			break;
		case TYPES.SET_UPDATING_ERROR:
			state = {
				...state,
				error: action.error,
				updatingError: action.error,
				isUpdating: false,
			};
			break;
	}
	return state;
};

export type State = ReturnType< typeof optionsReducer >;
export default optionsReducer;
