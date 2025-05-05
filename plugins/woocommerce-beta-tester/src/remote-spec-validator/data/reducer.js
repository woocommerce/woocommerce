/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	message: {
		type: null,
		text: null,
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_MESSAGE:
			return {
				...state,
				message: action.message,
			};
		default:
			return state;
	}
};

export default reducer;
