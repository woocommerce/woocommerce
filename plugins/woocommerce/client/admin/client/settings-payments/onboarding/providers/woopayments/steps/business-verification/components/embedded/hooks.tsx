/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { AccountKycResult } from './types';
import { OnboardingFields } from '../../types';
import { fromDotNotation } from '../../utils';

/**
 * Make an API request to create an KYC account session.
 *
 * @param data         The form data.
 * @param apiURL       The API URL.
 * @param isPoEligible Whether the user is eligible for a PO account.
 */
export const createKycAccountSession = async (
	data: OnboardingFields,
	apiURL: string,
	isPoEligible: boolean
): Promise< AccountKycResult > => {
	return await apiFetch< AccountKycResult >( {
		path: addQueryArgs( apiURL, {
			self_assessment: fromDotNotation( data ),
			progressive: isPoEligible,
		} ),
		method: 'POST',
	} );
};
