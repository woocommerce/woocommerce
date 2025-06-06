/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { addQueryArgs } from '@wordpress/url';

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
		let path = `${ WC_ADMIN_NAMESPACE }/settings/payments/woopayments/onboarding`;

		// Add source parameter if provided
		if ( source ) {
			path = addQueryArgs( path, { source } );
		}

		const response: OnboardingDataResponse = yield apiFetch( {
			path,
		} );

		yield getOnboardingDataSuccess( response );
	} catch ( e ) {
		yield getOnboardingDataError( e );
	}
}
