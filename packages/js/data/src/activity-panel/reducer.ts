/**
 * External dependencies
 */
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Actions } from './actions';
import { ActivityPanelState } from './types';

const reducer: Reducer< ActivityPanelState, Actions > = (
	state = {},
	payload
) => {
	if ( payload && 'type' in payload ) {
		switch ( payload.type ) {
			case TYPES.GET_ACTIVITY_PANEL_COUNTS_SUCCESS:
				return {
					...state,
					counts: payload.counts,
				};
			case TYPES.GET_ACTIVITY_PANEL_COUNTS_ERROR:
				return {
					...state,
					error: payload.error,
				};
			default:
				return state;
		}
	}
	return state;
};

export type State = ReturnType< typeof reducer >;
export default reducer;
