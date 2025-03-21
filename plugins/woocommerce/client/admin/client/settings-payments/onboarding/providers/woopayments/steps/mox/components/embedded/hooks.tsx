/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { AccountSession } from './types';
import { NAMESPACE } from '../../data/constants';
import { OnboardingFields } from '../../types';
import { fromDotNotation } from '../../utils';

/**
 * Make an API request to create an KYC account session.
 *
 * @param data The form data.
 * @param isPoEligible Whether the user is eligible for a PO account.
 */
export const createKycAccountSession = async (
	data: OnboardingFields,
	isPoEligible: boolean
): Promise< AccountSession > => {
	return await apiFetch< AccountSession >( {
		path: addQueryArgs( `${ NAMESPACE }/onboarding/kyc/session`, {
			self_assessment: fromDotNotation( data ),
			capabilities: '', // To-Do: Replace with capabilities from the endpoint.
			progressive: isPoEligible,
		} ),
		method: 'GET',
	} );
};
