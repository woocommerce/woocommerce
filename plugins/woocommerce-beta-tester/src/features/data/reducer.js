/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	features: {},
	modifiedFeatures: [],
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_MODIFIED_FEATURES:
			return {
				...state,
				modifiedFeatures: action.modifiedFeatures,
			};
		case TYPES.SET_FEATURES:
			return {
				...state,
				features: action.features,
			};
		default:
			return state;
	}
};

export default reducer;
