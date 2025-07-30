/**
 * External dependencies
 */
import { apiFetch, select } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

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
import { STORE_KEY } from './constants';

const resolveSelect = controls?.resolveSelect ?? select;

export function* getPaymentProviders( businessCountry?: string ) {
	yield getPaymentProvidersRequest();

	try {
		const paymentProvidersResponse: PaymentProvidersResponse =
			yield apiFetch( {
				method: 'POST', // Use the not-so-semantic POST to avoid caching of response.
				path: WC_ADMIN_NAMESPACE + '/settings/payments/providers',
				data: businessCountry ? { location: businessCountry } : {},
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

function* getPaymentProvidersIfNeeded( businessCountry?: string ) {
	// Just make sure the payment providers resolver has been called.
	yield resolveSelect( STORE_KEY, 'getPaymentProviders', businessCountry );
}

export function* getOfflinePaymentGateways( businessCountry?: string ) {
	yield getPaymentProvidersIfNeeded( businessCountry );
}

export function* getSuggestions( businessCountry?: string ) {
	yield getPaymentProvidersIfNeeded( businessCountry );
}

export function* getSuggestionCategories( businessCountry?: string ) {
	yield getPaymentProvidersIfNeeded( businessCountry );
}

export function* getWooPayEligibility() {
	const response: WooPayEligibilityResponse = yield apiFetch( {
		method: 'POST',
		path: `${ WC_ADMIN_NAMESPACE }/settings/payments/woopayments/woopay-eligibility`,
	} );

	return response;
}

export function* getIsWooPayEligible() {
	const response: WooPayEligibilityResponse = yield getWooPayEligibility();
	yield setIsWooPayEligible( response.is_eligible );
}
