/**
 * Internal dependencies
 */
import TYPES from './action-types';

const onboarding = (
	state = {
		profileItems: {},
		errors: {},
		requesting: {},
	},
	{ type, profileItems, replace, error, isRequesting, selector }
) => {
	switch ( type ) {
		case TYPES.SET_PROFILE_ITEMS:
			state = {
				...state,
				profileItems: replace
					? profileItems
					: { ...state.profileItems, ...profileItems },
			};
			break;
		case TYPES.SET_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					[ selector ]: error,
				},
			};
			break;
		case TYPES.SET_IS_REQUESTING:
			state = {
				...state,
				requesting: {
					...state.requesting,
					[ selector ]: isRequesting,
				},
			};
			break;
	}
	return state;
};

export default onboarding;
