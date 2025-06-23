/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE } from '../constants';
import { OnboardingDataResponse } from './types';
import {
	getOnboardingDataRequest,
	getOnboardingDataSuccess,
	getOnboardingDataError,
} from './actions';

export function* getOnboardingData( source?: string | null ) {
	yield getOnboardingDataRequest();

	try {
		const response: OnboardingDataResponse = yield apiFetch( {
			method: 'POST',
			path: `${ WC_ADMIN_NAMESPACE }/settings/payments/woopayments/onboarding`,
			data: source ? { source } : {},
		} );

		yield getOnboardingDataSuccess( response );
	} catch ( e ) {
		yield getOnboardingDataError( e );
	}
}
