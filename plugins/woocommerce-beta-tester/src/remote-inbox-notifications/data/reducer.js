/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	notifications: [],
	isLoading: true,
	notice: {
		status: 'success',
		message: '',
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_IS_LOADING:
			return {
				...state,
				isLoading: action.isLoading,
			};
		case TYPES.SET_NOTIFICATIONS:
			return {
				...state,
				notifications: action.notifications,
				isLoading: false,
			};
		case TYPES.SET_NOTICE:
			return {
				...state,
				notice: {
					...state.notice,
					...action.notice,
				},
			};
		case TYPES.DELETE_NOTIFICATION:
			return {
				...state,
				notifications: state.notifications.filter(
					( item ) => item.note_id !== action.id
				),
			};
		default:
			return state;
	}
};

export default reducer;
