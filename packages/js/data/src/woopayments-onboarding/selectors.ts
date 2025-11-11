/**
 * Internal dependencies
 */
import { OnboardingState } from './types';
import { WPDataSelector, WPDataSelectors } from '../types';

export const getOnboardingData = (
	state: OnboardingState,
	// This is only used to get the onboarding data from the store,
	// and is not used to determine the current step.
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	sessionEntryPoint?: string | null
): OnboardingState => state;

export const isOnboardingDataRequestPending = (
	state: OnboardingState
): boolean => state.isFetching;

export const getOnboardingDataError = ( state: OnboardingState ): unknown =>
	state.errors.getOnboardingData;

export type WooPaymentsOnboardingSelectors = {
	getOnboardingData: WPDataSelector< typeof getOnboardingData >;
	isOnboardingDataRequestPending: WPDataSelector<
		typeof isOnboardingDataRequestPending
	>;
	getOnboardingDataError: WPDataSelector< typeof getOnboardingDataError >;
} & WPDataSelectors;
