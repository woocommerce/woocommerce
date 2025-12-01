/**
 * Internal dependencies
 */
import { Action } from './actions';
import { OnboardingState } from './types';

const initialState: OnboardingState = {
	steps: [],
	context: {},
	isFetching: false,
	errors: {},
};

const reducer = ( state = initialState, action: Action ): OnboardingState => {
	switch ( action.type ) {
		case 'GET_WOOPAYMENTS_ONBOARDING_DATA_REQUEST':
			return {
				...state,
				isFetching: true,
			};
		case 'GET_WOOPAYMENTS_ONBOARDING_DATA_SUCCESS':
			return {
				...state,
				steps: action.steps,
				context: action.context,
				isFetching: false,
			};
		case 'GET_WOOPAYMENTS_ONBOARDING_DATA_ERROR':
			return {
				...state,
				errors: {
					...state.errors,
					getOnboardingData: action.error,
				},
				isFetching: false,
			};
		default:
			return state;
	}
};

export default reducer;
