/**
 * External dependencies
 */
import {
	apiFetch,
	dispatch as depreciatedDispatch,
} from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

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

import { API_NAMESPACE, STORE_KEY } from './constants';
import { PaymentGateway } from './types';

// Can be removed in WP 5.9.
const dispatch =
	controls && controls.dispatch ? controls.dispatch : depreciatedDispatch;

export function* getPaymentGateways() {
	yield getPaymentGatewaysRequest();

	try {
		const response: Array< PaymentGateway > = yield apiFetch( {
			path: API_NAMESPACE + '/payment_gateways',
		} );
		yield getPaymentGatewaysSuccess( response );
		for ( let i = 0; i < response.length; i++ ) {
			yield dispatch(
				STORE_KEY,
				'finishResolution',
				'getPaymentGateway',
				[ response[ i ].id ]
			);
		}
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
