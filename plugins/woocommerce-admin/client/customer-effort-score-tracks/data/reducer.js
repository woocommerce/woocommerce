/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	queue: [],
	cesModalData: undefined,
	showCESModal: false,
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_CES_SURVEY_QUEUE:
			return {
				...state,
				queue: action.queue,
			};
		case TYPES.HIDE_CES_MODAL:
			return {
				...state,
				showCESModal: false,
				cesModalData: undefined,
			};
		case TYPES.SHOW_CES_MODAL:
			const cesModalData = {
				action: action.surveyProps.action,
				label: action.surveyProps.label,
				onSubmitLabel: action.onSubmitLabel,
				firstQuestion: action.surveyProps.firstQuestion,
				secondQuestion: action.surveyProps.secondQuestion,
				onSubmitNoticeProps: action.onSubmitNoticeProps || {},
				props: action.props,
			};
			return {
				...state,
				showCESModal: true,
				cesModalData,
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
				title: action.title,
				firstQuestion: action.firstQuestion,
				secondQuestion: action.secondQuestion,
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
