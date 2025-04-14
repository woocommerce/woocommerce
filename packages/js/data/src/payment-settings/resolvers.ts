/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	getPaymentProvidersSuccess,
	getPaymentProvidersError,
	getPaymentProvidersRequest,
	setIsWooPayEligible,
} from './actions';
import { PaymentProvidersResponse, WooPayEligibilityResponse } from './types';
import { WC_ADMIN_NAMESPACE } from '../constants';

export function* getPaymentProviders( country?: string ) {
	yield getPaymentProvidersRequest();

	try {
		const paymentProvidersResponse: PaymentProvidersResponse =
			yield apiFetch( {
				path:
					WC_ADMIN_NAMESPACE +
					'/settings/payments/providers?' +
					( country ? `location=${ country }` : '' ),
			} );
		yield getPaymentProvidersSuccess(
			paymentProvidersResponse.providers,
			paymentProvidersResponse.offline_payment_methods,
			paymentProvidersResponse.suggestions,
			paymentProvidersResponse.suggestion_categories
		);
	} catch ( e ) {
		yield getPaymentProvidersError( e );
	}
}

export function* getOfflinePaymentGateways( country?: string ) {
	yield getPaymentProviders( country );
}

export function* getWooPayEligibility() {
	const response: WooPayEligibilityResponse = yield apiFetch( {
		path: `${ WC_ADMIN_NAMESPACE }/settings/payments/woopay-eligibility`,
	} );

	return response;
}

export function* getIsWooPayEligible() {
	const response: WooPayEligibilityResponse = yield getWooPayEligibility();
	yield setIsWooPayEligible( response.is_eligible );
}
