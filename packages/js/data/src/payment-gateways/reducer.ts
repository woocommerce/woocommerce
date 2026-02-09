/**
 * Internal dependencies
 */
import { ACTION_TYPES } from './action-types';
import { PluginsState, PaymentGateway } from './types';
import { Actions } from './actions';

function updatePaymentGatewayList(
	state: PluginsState,
	paymentGateway: PaymentGateway
): PluginsState {
	const targetIndex = state.paymentGateways.findIndex(
		( gateway ) => gateway.id === paymentGateway.id
	);

	if ( targetIndex === -1 ) {
		return {
			...state,
			paymentGateways: [ ...state.paymentGateways, paymentGateway ],
			isUpdating: false,
		};
	}

	return {
		...state,
		paymentGateways: [
			...state.paymentGateways.slice( 0, targetIndex ),
			paymentGateway,
			...state.paymentGateways.slice( targetIndex + 1 ),
		],
		isUpdating: false,
	};
}

const reducer = (
	state: PluginsState = {
		paymentGateways: [],
		isUpdating: false,
		errors: {},
	},
	payload?: Actions
): PluginsState => {
	if ( payload && 'type' in payload ) {
		switch ( payload.type ) {
			case ACTION_TYPES.GET_PAYMENT_GATEWAYS_REQUEST:
			case ACTION_TYPES.GET_PAYMENT_GATEWAY_REQUEST:
				return state;
			case ACTION_TYPES.GET_PAYMENT_GATEWAYS_SUCCESS:
				return {
					...state,
					paymentGateways: payload.paymentGateways,
				};
			case ACTION_TYPES.GET_PAYMENT_GATEWAYS_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						getPaymentGateways: payload.error,
					},
				};
			case ACTION_TYPES.GET_PAYMENT_GATEWAY_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						getPaymentGateway: payload.error,
					},
				};
			case ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_REQUEST:
				return {
					...state,
					isUpdating: true,
				};
			case ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_SUCCESS:
				return updatePaymentGatewayList(
					state,
					payload.paymentGateway
				);
			case ACTION_TYPES.GET_PAYMENT_GATEWAY_SUCCESS:
				return updatePaymentGatewayList(
					state,
					payload.paymentGateway
				);

			case ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						updatePaymentGateway: payload.error,
					},
					isUpdating: false,
				};
		}
	}
	return state;
};

export default reducer;
