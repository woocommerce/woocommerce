/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE } from '../constants';
import { OnboardingStepsResponse } from './types';
import {
	getOnboardingStepsRequest,
	getOnboardingStepsSuccess,
	getOnboardingStepsError,
} from './actions';

export function* getOnboardingSteps() {
	yield getOnboardingStepsRequest();

	try {
		const response: OnboardingStepsResponse = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/settings/payments/woopayments/onboarding`,
		} );

		yield getOnboardingStepsSuccess( response.steps );
	} catch ( e ) {
		yield getOnboardingStepsError( e );
	}
}
