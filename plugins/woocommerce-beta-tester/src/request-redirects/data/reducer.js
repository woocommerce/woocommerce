/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	redirectors: [],
	isLoading: true,
	notice: {
		status: 'success',
		message: '',
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.TOGGLE_REDIRECTOR:
			return {
				...state,
				redirectors: state.redirectors.map( ( redirector, index ) => {
					if ( index === action.index ) {
						redirector.enabled = ! redirector.enabled;
					}
					return redirector;
				} ),
			};
		case TYPES.SET_IS_LOADING:
			return {
				...state,
				isLoading: action.isLoading,
			};
		case TYPES.SET_REDIRECTORS:
			return {
				...state,
				redirectors: action.redirectors,
				isLoading: false,
			};
		case TYPES.DELETE_REDIRECTOR:
			return {
				...state,
				redirectors: state.redirectors.filter(
					( item, index ) => index !== action.index
				),
			};
		case TYPES.SAVE_REDIRECTOR:
			if (
				action.redirector.index !== null &&
				action.redirector.index !== undefined
			) {
				return {
					...state,
					redirectors: state.redirectors.map( ( redirector, index ) =>
						index === action.redirector.index
							? { ...redirector, ...action.redirector }
							: redirector
					),
				};
			}

			return {
				...state,
				redirectors: [ ...state.redirectors, action.redirector ],
			};
		default:
			return state;
	}
};

export default reducer;
