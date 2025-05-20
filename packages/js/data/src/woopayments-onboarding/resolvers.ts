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

export function* getOnboardingData() {
	yield getOnboardingDataRequest();

	try {
		const response: OnboardingDataResponse = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/settings/payments/woopayments/onboarding`,
		} );

		yield getOnboardingDataSuccess( response );
	} catch ( e ) {
		yield getOnboardingDataError( e );
	}
}
