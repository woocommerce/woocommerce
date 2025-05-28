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
 * Instruct the backend to finalize the embedded KYC session.
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
 * Make an API request to mark a sub-step as completed.
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
	// Store the sub-step completed status on the backend.
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
 * Create an embedded KYC session.
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
