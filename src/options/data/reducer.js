/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	options: [],
	isLoading: true,
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_IS_LOADING:
			return {
				...state,
				isLoading: action.isLoading,
			};
		case TYPES.SET_OPTIONS:
			return {
				...state,
				options: action.options,
				isLoading: false,
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
