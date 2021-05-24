/**
 * Internal dependencies
 */
import { ACTION_TYPES } from './action-types';
import { PluginsState, SelectorKeysWithActions, PaymentGateway } from './types';
import { Actions } from './actions';

function updatePaymentGatewayList(
	state: PluginsState,
	paymentGateway: PaymentGateway,
	selector: SelectorKeysWithActions
): PluginsState {
	const targetIndex = state.paymentGateways.findIndex(
		( gateway ) => gateway.id === paymentGateway.id
	);

	if ( targetIndex === -1 ) {
		return {
			...state,
			paymentGateways: [ ...state.paymentGateways, paymentGateway ],
			requesting: {
				...state.requesting,
				[ selector ]: false,
			},
		};
	}

	return {
		...state,
		paymentGateways: [
			...state.paymentGateways.slice( 0, targetIndex ),
			paymentGateway,
			...state.paymentGateways.slice( targetIndex + 1 ),
		],
		requesting: {
			...state.requesting,
			[ selector ]: false,
		},
	};
}

const reducer = (
	state: PluginsState = {
		paymentGateways: [],
		requesting: {},
		errors: {},
	},
	payload?: Actions
): PluginsState => {
	if ( payload && 'type' in payload ) {
		switch ( payload.type ) {
			case ACTION_TYPES.GET_PAYMENT_GATEWAYS_REQUEST:
				return {
					...state,
					requesting: {
						...state.requesting,
						getPaymentGateways: true,
					},
				};
			case ACTION_TYPES.GET_PAYMENT_GATEWAY_REQUEST:
				return {
					...state,
					requesting: {
						...state.requesting,
						getPaymentGateway: true,
					},
				};
			case ACTION_TYPES.GET_PAYMENT_GATEWAYS_SUCCESS:
				return {
					...state,
					paymentGateways: payload.paymentGateways,
					requesting: {
						...state.requesting,
						getPaymentGateways: false,
					},
				};
			case ACTION_TYPES.GET_PAYMENT_GATEWAYS_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						getPaymentGateways: payload.error,
					},
					requesting: {
						...state.requesting,
						getPaymentGateways: false,
					},
				};
			case ACTION_TYPES.GET_PAYMENT_GATEWAY_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						getPaymentGateway: payload.error,
					},
					requesting: {
						...state.requesting,
						getPaymentGateway: false,
					},
				};
			case ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_REQUEST:
				return {
					...state,
					requesting: {
						...state.requesting,
						updatePaymentGateway: true,
					},
				};
			case ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_SUCCESS:
				return updatePaymentGatewayList(
					state,
					payload.paymentGateway,
					'updatePaymentGateway'
				);
			case ACTION_TYPES.GET_PAYMENT_GATEWAY_SUCCESS:
				return updatePaymentGatewayList(
					state,
					payload.paymentGateway,
					'getPaymentGateway'
				);

			case ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						updatePaymentGateway: payload.error,
					},
					requesting: {
						...state.requesting,
						updatePaymentGateway: false,
					},
				};
		}
	}
	return state;
};

export default reducer;
