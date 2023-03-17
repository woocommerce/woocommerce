/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	queue: [],
	cesModalData: undefined,
	showCESModal: false,
	showProductMVPFeedbackModal: false,
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_CES_SURVEY_QUEUE:
			return {
				...state,
				queue: [ ...state.queue, ...action.queue ],
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
				title: action.surveyProps.title,
				onSubmitLabel: action.onSubmitLabel,
				firstQuestion: action.surveyProps.firstQuestion,
				secondQuestion: action.surveyProps.secondQuestion,
				onSubmitNoticeProps: action.onSubmitNoticeProps || {},
				trackProps: action.trackProps || {},
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
				description: action.description,
				noticeLabel: action.noticeLabel,
				firstQuestion: action.firstQuestion,
				secondQuestion: action.secondQuestion,
				icon: action.icon,
				pagenow: action.pageNow,
				adminpage: action.adminPage,
				onSubmitLabel: action.onSubmitLabel,
				props: action.props,
			};
			return {
				...state,
				queue: [ ...state.queue, newTrack ],
			};
		case TYPES.SHOW_PRODUCT_MVP_FEEDBACK_MODAL:
			return {
				...state,
				showProductMVPFeedbackModal: true,
			};
		case TYPES.HIDE_PRODUCT_MVP_FEEDBACK_MODAL:
			return {
				...state,
				showProductMVPFeedbackModal: false,
			};

		default:
			return state;
	}
};

export default reducer;
