/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	filters: [],
	isLoading: true,
	notice: {
		status: 'success',
		message: '',
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.TOGGLE_FILTER:
			return {
				...state,
				filters: state.filters.map( ( filter, index ) => {
					if ( index === action.index ) {
						filter.enabled = ! filter.enabled;
					}
					return filter;
				} ),
			};
		case TYPES.SET_IS_LOADING:
			return {
				...state,
				isLoading: action.isLoading,
			};
		case TYPES.SET_FILTERS:
			return {
				...state,
				filters: action.filters,
				isLoading: false,
			};
		case TYPES.DELETE_FILTER:
			return {
				...state,
				filters: state.filters.filter(
					( item, index ) => index !== action.index
				),
			};
		case TYPES.SAVE_FILTER:
			return {
				...state,
				filters: [ ...state.filters, action.filter ],
			};
		default:
			return state;
	}
};

export default reducer;
