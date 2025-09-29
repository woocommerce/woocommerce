/**
 * Internal dependencies
 */
import { ACTION_TYPES } from './action-types';
import { PaymentsSettingsState } from './types';
import { Actions } from './actions';

const reducer = (
	state: PaymentsSettingsState = {
		providers: [],
		offlinePaymentGateways: [],
		suggestions: [],
		suggestionCategories: [],
		isFetching: false,
		isWooPayEligible: false,
		errors: {},
	},
	payload?: Actions
): PaymentsSettingsState => {
	if ( payload && 'type' in payload ) {
		switch ( payload.type ) {
			case ACTION_TYPES.GET_PAYMENT_PROVIDERS_REQUEST:
				return {
					...state,
					isFetching: true,
				};
			case ACTION_TYPES.GET_PAYMENT_PROVIDERS_SUCCESS:
				return {
					...state,
					isFetching: false,
					providers: payload.providers,
					offlinePaymentGateways: payload.offlinePaymentGateways,
					suggestions: payload.suggestions,
					suggestionCategories: payload.suggestionCategories,
				};
			case ACTION_TYPES.GET_PAYMENT_PROVIDERS_ERROR:
				return {
					...state,
					isFetching: false,
					errors: {
						...state.errors,
						getPaymentGatewaySuggestions: payload.error,
					},
				};
			case ACTION_TYPES.UPDATE_PROVIDER_ORDERING:
				return {
					...state,
				};
			case ACTION_TYPES.SET_IS_ELIGIBLE:
				return {
					...state,
					isWooPayEligible: payload.isEligible,
				};
		}
	}
	return state;
};

export default reducer;
