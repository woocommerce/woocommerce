/**
 * Internal dependencies
 */
import { OnboardingDataResponse } from './types';

export function getOnboardingDataRequest() {
	return {
		type: 'GET_WOOPAYMENTS_ONBOARDING_DATA_REQUEST' as const,
	};
}

export function getOnboardingDataSuccess( data: OnboardingDataResponse ) {
	return {
		type: 'GET_WOOPAYMENTS_ONBOARDING_DATA_SUCCESS' as const,
		steps: data.steps,
		context: data.context,
	};
}

export function getOnboardingDataError( error: unknown ) {
	return {
		type: 'GET_WOOPAYMENTS_ONBOARDING_DATA_ERROR' as const,
		error,
	};
}

export type Action =
	| ReturnType< typeof getOnboardingDataRequest >
	| ReturnType< typeof getOnboardingDataSuccess >
	| ReturnType< typeof getOnboardingDataError >;
