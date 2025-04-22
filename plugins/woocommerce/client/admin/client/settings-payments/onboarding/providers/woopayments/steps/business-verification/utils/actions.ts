/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	OnboardingFields,
	PoEligibleData,
	PoEligibleResponse,
	FinalizeOnboardingResponse,
	AccountKycResult,
} from '../types';
import { hasUndefinedValues, fromDotNotation } from './';

/**
 * Make an API request to determine if the user is eligible for a PO account.
 *
 * @param onboardingFields The form data, used to determine eligibility.
 */
export const isPoEligible = async (
	onboardingFields: OnboardingFields
): Promise< boolean > => {
	// Check if any required property is undefined
	if (
		hasUndefinedValues( {
			country: onboardingFields.country,
			business_type: onboardingFields.business_type,
			mcc: onboardingFields.mcc,
			annual_revenue: onboardingFields.annual_revenue,
			go_live_timeframe: onboardingFields.go_live_timeframe,
		} )
	) {
		return false;
	}

	const eligibilityData: PoEligibleData = {
		location: onboardingFields.country as string,
		self_assessment: {
			country: onboardingFields.country as string,
			type: onboardingFields.business_type as string,
			mcc: onboardingFields.mcc as string,
			annual_revenue: onboardingFields.annual_revenue as string,
			go_live_timeframe: onboardingFields.go_live_timeframe as string,
		},
	};

	const response: PoEligibleResponse = await apiFetch( {
		path: `${ WC_ADMIN_NAMESPACE }/settings/payments/woopayments/onboarding/step/business_verification/check/po_eligible`,
		method: 'POST',
		data: eligibilityData,
	} );

	return response.result === 'eligible';
};

/**
 * Make an API request to finalize the onboarding process.
 *
 * @param apiUrl The API URL.
 */
export const finalizeOnboarding = async ( apiUrl: string ) => {
	return await apiFetch< FinalizeOnboardingResponse >( {
		url: apiUrl,
		method: 'POST',
		data: {},
	} );
};

/**
 * Make an API request to create an KYC account session.
 *
 * @param stepName The sub-step name.
 * @param apiURL   The API URL.
 * @param data     Steps data.
 */
export const completeSubStep = (
	stepName: string,
	apiURL: string | undefined,
	data: Record<
		string,
		{
			status: string;
		}
	>
) => {
	// Send POST request to the href with the Business Verification completed status
	if ( apiURL ) {
		apiFetch( {
			url: apiURL,
			method: 'POST',
			data: {
				sub_steps: {
					...data,
					[ stepName ]: {
						status: 'completed',
					},
				},
			},
		} );
	}
};

/**
 * Make an API request to create an KYC account session.
 *
 * @param data       The form data.
 * @param apiURL     The API URL.
 * @param poEligible Whether the user is eligible for a PO account.
 */
export const createKycAccountSession = async (
	data: OnboardingFields,
	apiURL: string,
	poEligible: boolean
): Promise< AccountKycResult > => {
	const selfAssessmentData = fromDotNotation( data );
	const requestData: Record< string, unknown > = {
		progressive: poEligible,
	};

	// Only pass the self assessment data if at least one field is set.
	if ( Object.keys( selfAssessmentData ).length > 0 ) {
		requestData.self_assessment = selfAssessmentData;
	}

	return await apiFetch< AccountKycResult >( {
		url: apiURL,
		method: 'POST',
		data: requestData,
	} );
};
