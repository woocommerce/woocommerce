/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	options: [],
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_OPTIONS:
			return {
				...state,
				options: action.options,
			};

		case TYPES.DELETE_OPTION_BY_ID:
			return {
				...state,
				options: state.options.filter(
					( item ) => item.option_id !== action.optionId
				),
			};
		default:
			return state;
	}
};

export default reducer;
