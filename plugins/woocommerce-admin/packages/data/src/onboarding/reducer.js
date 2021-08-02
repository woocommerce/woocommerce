/**
 * Internal dependencies
 */
import TYPES from './action-types';

export const defaultState = {
	errors: {},
	freeExtensions: [],
	profileItems: {
		business_extensions: null,
		completed: null,
		industry: null,
		other_platform: null,
		other_platform_name: null,
		product_count: null,
		product_types: null,
		revenue: null,
		selling_venues: null,
		setup_client: null,
		skipped: null,
		theme: null,
		wccom_connected: null,
	},
	paymentMethods: [],
	requesting: {},
	tasksStatus: {},
};

const onboarding = (
	state = defaultState,
	{
		freeExtensions,
		type,
		profileItems,
		paymentMethods,
		replace,
		error,
		isRequesting,
		selector,
		tasksStatus,
	}
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
		case TYPES.GET_PAYMENT_METHODS_SUCCESS:
			return {
				...state,
				paymentMethods,
			};
		case TYPES.GET_FREE_EXTENSIONS_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					getFreeExtensions: error,
				},
			};
		case TYPES.GET_FREE_EXTENSIONS_SUCCESS:
			return {
				...state,
				freeExtensions,
			};
		default:
			return state;
	}
};

export default onboarding;
