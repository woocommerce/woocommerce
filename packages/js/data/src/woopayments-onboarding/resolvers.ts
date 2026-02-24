/**
 * External dependencies
 */
import { apiFetch, select } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE } from '../constants';
import { OnboardingDataResponse } from './types';
import { STORE_KEY } from './constants';
import {
	getOnboardingDataRequest,
	getOnboardingDataSuccess,
	getOnboardingDataError,
} from './actions';

export function* getOnboardingData( sessionEntryPoint?: string | null ) {
	// Check if we're already fetching to prevent concurrent requests.
	const isFetchingData: boolean = yield select(
		STORE_KEY,
		'isOnboardingDataRequestPending'
	);
	if ( isFetchingData ) {
		return;
	}

	yield getOnboardingDataRequest();

	try {
		const response: OnboardingDataResponse = yield apiFetch( {
			method: 'POST', // Use the not-so-semantic POST to avoid caching of response.
			path: `${ WC_ADMIN_NAMESPACE }/settings/payments/woopayments/onboarding`,
			data: sessionEntryPoint ? { source: sessionEntryPoint } : {},
		} );

		yield getOnboardingDataSuccess( response );
	} catch ( e ) {
		yield getOnboardingDataError( e );
	}
}
