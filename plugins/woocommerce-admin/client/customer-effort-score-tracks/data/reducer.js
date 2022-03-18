/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	queue: [],
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_CES_SURVEY_QUEUE:
			return {
				...state,
				queue: action.queue,
			};
		case TYPES.ADD_CES_SURVEY:
			// Prevent duplicate
			const hasDuplicate = state.queue.filter(
				( track ) => track.action === action.action
			);
			if ( hasDuplicate.length ) {
				return state;
			}
			const newTrack = {
				action: action.action,
				label: action.label,
				pagenow: action.pageNow,
				adminpage: action.adminPage,
				onSubmitLabel: action.onSubmitLabel,
				props: action.props,
			};
			return {
				...state,
				queue: [ ...state.queue, newTrack ],
			};

		default:
			return state;
	}
};

export default reducer;
