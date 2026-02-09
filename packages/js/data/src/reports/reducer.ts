/**
 * External dependencies
 */
import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Action } from './actions';
import { ReportState } from './types';

const initialState: ReportState = {
	itemErrors: {},
	items: {},
	statErrors: {},
	stats: {},
};
const reducer: Reducer< ReportState, Action > = (
	state = initialState,
	action
) => {
	switch ( action.type ) {
		case TYPES.SET_REPORT_ITEMS:
			return {
				...state,
				items: {
					...state.items,
					[ action.resourceName ]: action.items,
				},
			};
		case TYPES.SET_REPORT_STATS:
			return {
				...state,
				stats: {
					...state.stats,
					[ action.resourceName ]: action.stats,
				},
			};
		case TYPES.SET_ITEM_ERROR:
			return {
				...state,
				itemErrors: {
					...state.itemErrors,
					[ action.resourceName ]: action.error,
				},
			};
		case TYPES.SET_STAT_ERROR:
			return {
				...state,
				statErrors: {
					...state.statErrors,
					[ action.resourceName ]: action.error,
				},
			};
		default:
			return state;
	}
};

export type State = ReturnType< typeof reducer >;
export default reducer;
