/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	OnboardingFields,
	FinalizeEmbeddedKycSessionResponse,
	EmbeddedKycSessionCreateResult,
} from '../types';
import { fromDotNotation } from './';

/**
 * Instruct the backend to finalize the embedded KYC session.
 *
 * @param apiUrl The API URL.
 * @param source Optional source for the entire onboarding session flow.
 */
export const finalizeEmbeddedKycSession = async (
	apiUrl: string,
	source?: string
) => {
	return await apiFetch< FinalizeEmbeddedKycSessionResponse >( {
		url: apiUrl,
		method: 'POST',
		data: {
			source,
		},
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
): Promise< void > => {
	// Store the sub-step completed status on the backend.
	if ( apiUrl ) {
		return apiFetch( {
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

	// If no API URL is provided, just return a resolved promise.
	return Promise.resolve();
};

/**
 * Create an embedded KYC session.
 *
 * @param data   The form data.
 * @param apiUrl The API URL.
 * @param source Optional source for the entire onboarding session flow.
 */
export const createEmbeddedKycSession = async (
	data: OnboardingFields,
	apiUrl: string,
	source?: string
): Promise< EmbeddedKycSessionCreateResult > => {
	const selfAssessmentData = fromDotNotation( data );
	const requestData: Record< string, unknown > = {};

	// Only pass the self assessment data if at least one field is set.
	if ( Object.keys( selfAssessmentData ).length > 0 ) {
		requestData.self_assessment = selfAssessmentData;
	}

	// If a source is provided, include it in the request data.
	if ( source ) {
		requestData.source = source;
	}

	return await apiFetch< EmbeddedKycSessionCreateResult >( {
		url: apiUrl,
		method: 'POST',
		data: requestData,
	} );
};
