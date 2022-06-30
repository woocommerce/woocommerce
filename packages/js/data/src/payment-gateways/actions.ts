/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { ACTION_TYPES } from './action-types';
import { API_NAMESPACE } from './constants';
import { PaymentGateway } from './types';

export function getPaymentGatewaysRequest(): {
	type: ACTION_TYPES.GET_PAYMENT_GATEWAYS_REQUEST;
} {
	return {
		type: ACTION_TYPES.GET_PAYMENT_GATEWAYS_REQUEST,
	};
}

export function getPaymentGatewaysSuccess(
	paymentGateways: PaymentGateway[]
): {
	type: ACTION_TYPES.GET_PAYMENT_GATEWAYS_SUCCESS;
	paymentGateways: PaymentGateway[];
} {
	return {
		type: ACTION_TYPES.GET_PAYMENT_GATEWAYS_SUCCESS,
		paymentGateways,
	};
}

export function getPaymentGatewaysError( error: unknown ): {
	type: ACTION_TYPES.GET_PAYMENT_GATEWAYS_ERROR;
	error: unknown;
} {
	return {
		type: ACTION_TYPES.GET_PAYMENT_GATEWAYS_ERROR,
		error,
	};
}

export function getPaymentGatewayRequest(): {
	type: ACTION_TYPES.GET_PAYMENT_GATEWAY_REQUEST;
} {
	return {
		type: ACTION_TYPES.GET_PAYMENT_GATEWAY_REQUEST,
	};
}

export function getPaymentGatewayError( error: unknown ): {
	type: ACTION_TYPES.GET_PAYMENT_GATEWAY_ERROR;
	error: unknown;
} {
	return {
		type: ACTION_TYPES.GET_PAYMENT_GATEWAY_ERROR,
		error,
	};
}

export function getPaymentGatewaySuccess( paymentGateway: PaymentGateway ): {
	type: ACTION_TYPES.GET_PAYMENT_GATEWAY_SUCCESS;
	paymentGateway: PaymentGateway;
} {
	return {
		type: ACTION_TYPES.GET_PAYMENT_GATEWAY_SUCCESS,
		paymentGateway,
	};
}

export function updatePaymentGatewaySuccess( paymentGateway: PaymentGateway ): {
	type: ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_SUCCESS;
	paymentGateway: PaymentGateway;
} {
	return {
		type: ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_SUCCESS,
		paymentGateway,
	};
}
export function updatePaymentGatewayRequest(): {
	type: ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_REQUEST;
} {
	return {
		type: ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_REQUEST,
	};
}

export function updatePaymentGatewayError( error: unknown ): {
	type: ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_ERROR;
	error: unknown;
} {
	return {
		type: ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_ERROR,
		error,
	};
}

type DeepPartial< T > = {
	[ P in keyof T ]?: DeepPartial< T[ P ] >;
};
export function* updatePaymentGateway(
	id: string,
	data: DeepPartial< PaymentGateway >
) {
	try {
		yield updatePaymentGatewayRequest();
		const response: PaymentGateway = yield apiFetch( {
			method: 'PUT',
			path: API_NAMESPACE + '/payment_gateways/' + id,
			body: JSON.stringify( data ),
		} );

		if ( response && response.id === id ) {
			// Update the already loaded payment gateway list with the new data
			yield updatePaymentGatewaySuccess( response );
			return response;
		}
	} catch ( e ) {
		yield updatePaymentGatewayError( e );
		throw e;
	}
}

export type Actions =
	| ReturnType< typeof updatePaymentGateway >
	| ReturnType< typeof updatePaymentGatewayRequest >
	| ReturnType< typeof updatePaymentGatewaySuccess >
	| ReturnType< typeof getPaymentGatewaysRequest >
	| ReturnType< typeof getPaymentGatewaysSuccess >
	| ReturnType< typeof getPaymentGatewaysError >
	| ReturnType< typeof getPaymentGatewayRequest >
	| ReturnType< typeof getPaymentGatewaySuccess >
	| ReturnType< typeof getPaymentGatewayError >
	| ReturnType< typeof updatePaymentGatewayRequest >
	| ReturnType< typeof updatePaymentGatewayError >;
