/**
 * Internal dependencies
 */
import TYPES from './action-types';

const noticesReducer = (
	state = {
		errors: {},
		notices: {},
	},
	{ error, id, notices, type }
) => {
	switch ( type ) {
		case TYPES.GET_NOTICES_SUCCESS:
			state = {
				...state,
				notices,
			};
			break;
		case TYPES.GET_NOTICES_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					getNotices: error,
				},
			};
			break;
		case TYPES.DISMISS_NOTICE_SUCCESS:
			const updatedNotices = { ...notices };
			delete updatedNotices[ id ];
			state = {
				...state,
				notices: updatedNotices,
			};
			break;
		case TYPES.DISMISS_NOTICE_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					[ id ]: error,
				},
			};
			break;
	}
	return state;
};

export default noticesReducer;
