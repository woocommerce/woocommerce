/**
 * Internal dependencies
 */
import TYPES from './action-types';

const onboarding = (
	state = {
		errors: {},
		profileItems: {},
		requesting: {},
		tasksStatus: {},
	},
	{ type, profileItems, replace, error, isRequesting, selector, tasksStatus }
) => {
	switch ( type ) {
		case TYPES.SET_PROFILE_ITEMS:
			return {
				...state,
				profileItems: replace
					? profileItems
					: { ...state.profileItems, ...profileItems },
			};
		case TYPES.SET_TASKS_STATUS:
			return {
				...state,
				tasksStatus: { ...state.tasksStatus, ...tasksStatus },
			};
		case TYPES.SET_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					[ selector ]: error,
				},
			};
		case TYPES.SET_IS_REQUESTING:
			return {
				...state,
				requesting: {
					...state.requesting,
					[ selector ]: isRequesting,
				},
			};
		default:
			return state;
	}
};

export default onboarding;
