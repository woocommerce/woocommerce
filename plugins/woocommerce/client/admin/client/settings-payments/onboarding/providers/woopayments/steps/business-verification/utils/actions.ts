/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	OnboardingFields,
	FinalizeOnboardingResponse,
	AccountKycResult,
} from '../types';
import { fromDotNotation } from './';

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
 * @param apiUrl   The API URL.
 * @param data     Steps data.
 */
export const completeSubStep = (
	stepName: string,
	apiUrl: string | undefined,
	data: Record<
		string,
		{
			status: string;
		}
	>
) => {
	// Send POST request to the href with the Business Verification completed status
	if ( apiUrl ) {
		apiFetch( {
			url: apiUrl,
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
 * @param data   The form data.
 * @param apiUrl The API URL.
 */
export const createKycAccountSession = async (
	data: OnboardingFields,
	apiUrl: string
): Promise< AccountKycResult > => {
	const selfAssessmentData = fromDotNotation( data );
	const requestData: Record< string, unknown > = {};

	// Only pass the self assessment data if at least one field is set.
	if ( Object.keys( selfAssessmentData ).length > 0 ) {
		requestData.self_assessment = selfAssessmentData;
	}

	return await apiFetch< AccountKycResult >( {
		url: apiUrl,
		method: 'POST',
		data: requestData,
	} );
};
