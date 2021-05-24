/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	getPaymentGatewaysSuccess,
	getPaymentGatewaySuccess,
	getPaymentGatewaysError,
	getPaymentGatewayError,
	getPaymentGatewayRequest,
	getPaymentGatewaysRequest,
} from './actions';

import { API_NAMESPACE } from './constants';
import { PaymentGateway } from './types';

export function* getPaymentGateways() {
	yield getPaymentGatewaysRequest();

	try {
		const response: Array< PaymentGateway > = yield apiFetch( {
			path: API_NAMESPACE + '/payment_gateways',
		} );
		yield getPaymentGatewaysSuccess( response );
	} catch ( e ) {
		yield getPaymentGatewaysError( e );
	}
}

export function* getPaymentGateway( id: string ) {
	yield getPaymentGatewayRequest();

	try {
		const response: PaymentGateway = yield apiFetch( {
			path: API_NAMESPACE + '/payment_gateways/' + id,
		} );

		if ( response && response.id ) {
			yield getPaymentGatewaySuccess( response );
			return response;
		}
	} catch ( e ) {
		yield getPaymentGatewayError( e );
	}
}
