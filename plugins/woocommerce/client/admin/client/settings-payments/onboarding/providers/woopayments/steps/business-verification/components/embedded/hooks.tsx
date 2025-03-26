/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';

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
 * @param isPoEligible Whether the user is eligible for a PO account.
 */
export const createKycAccountSession = async (
	data: OnboardingFields,
	isPoEligible: boolean
): Promise< AccountKycResult > => {
	return await apiFetch< AccountKycResult >( {
		path: addQueryArgs(
			`${ WC_ADMIN_NAMESPACE }/settings/payments/woopayments/onboarding/step/business_verification/session/start`,
			{
				self_assessment: fromDotNotation( data ),
				progressive: isPoEligible,
			}
		),
		method: 'POST',
	} );
};
